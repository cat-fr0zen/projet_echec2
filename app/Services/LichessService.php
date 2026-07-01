<?php
/**
 * Service d'integration "lecture seule" avec l'API publique Lichess.
 *
 * Le but est similaire a l'integration Chess.com deja presente :
 * - lire un profil public Lichess
 * - afficher quelques statistiques utiles dans le profil membre
 * - ne jamais demander de secret ni d'acces prive
 */

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ApiCacheRepository;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

final class LichessService
{
    private const URL_PROFIL = '%s/user/%s';
    private const DUREE_CACHE_SUCCES = 43200;
    private const DUREE_CACHE_ERREUR = 900;
    private string $baseUrl;

    public function __construct(
        private ?string $dossierCache,
        private string $agentUtilisateur = 'association-echecs-site/1.0',
        private ?ApiCacheRepository $cacheBase = null,
        ?string $baseUrl = null,
        private ?int $cacheTtlSecondes = null
    ) {
        $this->baseUrl = rtrim((string) ($baseUrl !== null && $baseUrl !== '' ? $baseUrl : 'https://lichess.org/api'), '/');

        if ($this->dossierCache !== null && $this->dossierCache !== '' && ! is_dir($this->dossierCache)) {
            @mkdir($this->dossierCache, 0755, true);
        }
    }

    /**
     * Retourne un instantane normalise pour un pseudo Lichess.
     *
     * @return array<string, mixed>
     */
    public function recupererInstantaneJoueur(string $pseudo): array
    {
        $pseudoNormalise = $this->normaliserPseudo($pseudo);

        if ($pseudoNormalise === '') {
            return $this->ajouterAliasCompatibilite([
                'statut' => 'absent',
                'pseudo' => '',
                'message' => "Aucun pseudo Lichess n'est enregistre pour ce profil.",
            ]);
        }

        $instantaneCache = $this->lireCacheBase($pseudoNormalise) ?? $this->lireCache($pseudoNormalise);

        if ($instantaneCache !== null) {
            return $this->ajouterAliasCompatibilite($instantaneCache);
        }

        $pseudoEncode = rawurlencode($pseudoNormalise);
        $reponseProfil = $this->effectuerRequeteJson(sprintf(self::URL_PROFIL, $this->baseUrl, $pseudoEncode));

        if (($reponseProfil['code_statut'] ?? 0) !== 200 || ! is_array($reponseProfil['donnees'] ?? null)) {
            $instantane = $this->construireInstantaneErreur($pseudoNormalise, (int) ($reponseProfil['code_statut'] ?? 0));
            $this->ecrireCacheComplet($pseudoNormalise, $instantane, $this->dureeCacheErreur());

            return $this->ajouterAliasCompatibilite($instantane);
        }

        $instantane = $this->construireInstantaneSucces($pseudoNormalise, $reponseProfil['donnees']);
        $this->ecrireCacheComplet($pseudoNormalise, $instantane, $this->dureeCacheSucces());

        return $this->ajouterAliasCompatibilite($instantane);
    }

    public function normaliserPseudo(?string $pseudo): string
    {
        return mb_strtolower(trim((string) $pseudo));
    }

    /**
     * @param  array<string, mixed>  $donneesProfil
     * @return array<string, mixed>
     */
    private function construireInstantaneSucces(string $pseudo, array $donneesProfil): array
    {
        $dateRecuperation = gmdate('c');
        $profilPublic = is_array($donneesProfil['profile'] ?? null) ? $donneesProfil['profile'] : [];

        return [
            'statut' => 'lie',
            'pseudo' => $pseudo,
            'url_profil' => (string) ($donneesProfil['url'] ?? ('https://lichess.org/@/' . rawurlencode($pseudo))),
            'joueur' => [
                'pseudo' => (string) ($donneesProfil['username'] ?? $pseudo),
                'nom_affichage' => (string) ($donneesProfil['username'] ?? $pseudo),
                'titre' => (string) ($donneesProfil['title'] ?? ''),
                'avatar' => (string) ($donneesProfil['profile']['image'] ?? ''),
                'pays' => (string) ($profilPublic['country'] ?? ''),
                'abonnes' => $this->versEntierNullable($donneesProfil['nbFollowers'] ?? null),
                'classement_fide' => $this->versEntierNullable($profilPublic['fideRating'] ?? null),
                'derniere_presence_libelle' => $this->formatterDernierePresence($donneesProfil['seenAt'] ?? null),
                'patron' => (bool) ($donneesProfil['patron'] ?? false),
            ],
            'classements' => $this->extraireClassements($donneesProfil),
            'note_statistiques' => 'Donnees publiques Lichess affichees en lecture seule.',
            'date_recuperation' => $dateRecuperation,
            'date_recuperation_libelle' => $this->formatterDateRecuperation($dateRecuperation),
            'source_cache' => 'direct',
            'message' => '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function construireInstantaneErreur(string $pseudo, int $codeStatut): array
    {
        $message = match ($codeStatut) {
            404 => "Le pseudo Lichess renseigne n'a pas ete trouve dans les donnees publiques.",
            429 => 'Lichess limite temporairement les requetes. Reessaie un peu plus tard.',
            default => 'Les statistiques publiques Lichess sont temporairement indisponibles pour ce profil.',
        };

        return [
            'statut' => 'erreur',
            'pseudo' => $pseudo,
            'url_profil' => 'https://lichess.org/@/' . rawurlencode($pseudo),
            'joueur' => [
                'pseudo' => $pseudo,
                'nom_affichage' => $pseudo,
                'titre' => '',
                'avatar' => '',
                'pays' => '',
                'abonnes' => null,
                'classement_fide' => null,
                'derniere_presence_libelle' => '',
                'patron' => false,
            ],
            'classements' => [],
            'note_statistiques' => 'La liaison reste facultative et repose uniquement sur les donnees publiques de Lichess.',
            'date_recuperation' => gmdate('c'),
            'date_recuperation_libelle' => "Derniere tentative : a l'instant",
            'source_cache' => 'direct',
            'message' => $message,
        ];
    }

    /**
     * @param  array<string, mixed>  $donneesProfil
     * @return array<int, array<string, mixed>>
     */
    private function extraireClassements(array $donneesProfil): array
    {
        $perfs = is_array($donneesProfil['perfs'] ?? null) ? $donneesProfil['perfs'] : [];
        $correspondances = [
            'rapid' => 'Rapide',
            'blitz' => 'Blitz',
            'bullet' => 'Bullet',
            'classical' => 'Classique',
            'puzzle' => 'Puzzle',
        ];

        $classements = [];

        foreach ($correspondances as $cleTechnique => $libelle) {
            if (! isset($perfs[$cleTechnique]) || ! is_array($perfs[$cleTechnique])) {
                continue;
            }

            $entree = $perfs[$cleTechnique];
            $classementActuel = $this->versEntierNullable($entree['rating'] ?? null);
            $progression = $this->versEntierNullable($entree['prog'] ?? null);
            $parties = $this->versEntierNullable($entree['games'] ?? null);

            if ($classementActuel === null && $parties === null) {
                continue;
            }

            $classements[] = [
                'cle' => $cleTechnique,
                'libelle' => $libelle,
                'classement' => $classementActuel,
                'meilleur_classement' => null,
                'meilleure_date_libelle' => '',
                'parties' => $parties,
                'victoires' => null,
                'defaites' => null,
                'nulles' => null,
                'progression' => $progression,
            ];
        }

        return $classements;
    }

    /**
     * @return array{code_statut:int, donnees:?array}
     */
    private function effectuerRequeteJson(string $url): array
    {
        try {
            $reponse = Http::acceptJson()
                ->withUserAgent($this->agentUtilisateur)
                ->timeout(6)
                ->connectTimeout(3)
                ->retry(2, 200, throw: false)
                ->withOptions([
                    'verify' => true,
                ])
                ->get($url);

            $donnees = $reponse->json();

            if (! $reponse->successful()) {
                Log::warning('lichess.http_status', [
                    'url' => $url,
                    'status' => $reponse->status(),
                ]);
            }

            return [
                'code_statut' => $reponse->status(),
                'donnees' => is_array($donnees) ? $donnees : null,
            ];
        } catch (Throwable $exception) {
            Log::warning('lichess.http_failure', [
                'url' => $url,
                'message' => $exception->getMessage(),
            ]);

            return [
                'code_statut' => 0,
                'donnees' => null,
            ];
        }
    }

    private function formatterDernierePresence(mixed $valeur): string
    {
        $horodatage = $this->versEntierNullable($valeur);

        if ($horodatage === null || $horodatage <= 0) {
            return '';
        }

        try {
            $libelle = (new DateTimeImmutable('@' . (int) floor($horodatage / 1000)))
                ->setTimezone(new DateTimeZone('Europe/Paris'))
                ->format('d/m/Y a H:i');

            return sprintf('Derniere presence : %s', $libelle);
        } catch (Exception) {
            return '';
        }
    }

    private function formatterDateRecuperation(string $valeur): string
    {
        try {
            $date = new DateTimeImmutable($valeur);
        } catch (Exception) {
            return '';
        }

        return 'Donnees recuperees le ' . $date->setTimezone(new DateTimeZone('Europe/Paris'))->format('d/m/Y a H:i');
    }

    private function versEntierNullable(mixed $valeur): ?int
    {
        if ($valeur === null || $valeur === '') {
            return null;
        }

        if (! is_numeric($valeur)) {
            return null;
        }

        return (int) $valeur;
    }

    private function cheminCache(string $pseudo): ?string
    {
        if ($this->dossierCache === null || $this->dossierCache === '') {
            return null;
        }

        $nomSecurise = preg_replace('/[^a-z0-9_-]+/i', '-', $pseudo) ?: 'joueur';

        return rtrim($this->dossierCache, '/\\') . DIRECTORY_SEPARATOR . $nomSecurise . '.json';
    }

    /**
     * @return array<string, mixed>|null
     */
    private function lireCache(string $pseudo): ?array
    {
        $cheminCache = $this->cheminCache($pseudo);

        if ($cheminCache === null || ! is_file($cheminCache)) {
            return null;
        }

        $contenu = @file_get_contents($cheminCache);

        if (! is_string($contenu) || trim($contenu) === '') {
            return null;
        }

        $donnees = json_decode($contenu, true);

        if (! is_array($donnees)) {
            return null;
        }

        $expireLe = (int) ($donnees['_expire_le'] ?? 0);
        $instantane = $donnees['instantane'] ?? null;

        if ($expireLe < time() || ! is_array($instantane)) {
            return null;
        }

        $instantane['source_cache'] = 'cache';

        return $instantane;
    }

    /**
     * @param  array<string, mixed>  $instantane
     */
    private function ecrireCache(string $pseudo, array $instantane, int $dureeSecondes): void
    {
        $cheminCache = $this->cheminCache($pseudo);

        if ($cheminCache === null) {
            return;
        }

        $chargeUtile = [
            '_expire_le' => time() + $dureeSecondes,
            'instantane' => $instantane,
        ];

        file_put_contents(
            $cheminCache,
            json_encode($chargeUtile, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            LOCK_EX
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function lireCacheBase(string $pseudo): ?array
    {
        if ($this->cacheBase === null) {
            return null;
        }

        $instantane = $this->cacheBase->lire('lichess', 'lichess:user:' . $pseudo);

        if ($instantane === null) {
            return null;
        }

        $instantane['source_cache'] = 'cache';

        return $instantane;
    }

    /**
     * @param array<string, mixed> $instantane
     */
    private function ecrireCacheComplet(string $pseudo, array $instantane, int $dureeSecondes): void
    {
        if ($this->cacheBase !== null) {
            $this->cacheBase->ecrire(
                'lichess',
                'lichess:user:' . $pseudo,
                $instantane,
                $dureeSecondes
            );
        }

        $this->ecrireCache($pseudo, $instantane, $dureeSecondes);
    }

    private function dureeCacheSucces(): int
    {
        return $this->cacheTtlSecondes !== null && $this->cacheTtlSecondes > 0
            ? $this->cacheTtlSecondes
            : self::DUREE_CACHE_SUCCES;
    }

    private function dureeCacheErreur(): int
    {
        return max(300, min($this->dureeCacheSucces(), self::DUREE_CACHE_ERREUR));
    }

    /**
     * @param  array<string, mixed>  $instantane
     * @return array<string, mixed>
     */
    private function ajouterAliasCompatibilite(array $instantane): array
    {
        $joueur = is_array($instantane['joueur'] ?? null) ? $instantane['joueur'] : [];
        $classements = is_array($instantane['classements'] ?? null) ? $instantane['classements'] : [];

        $joueurCompat = [
            ...$joueur,
            'username' => $joueur['pseudo'] ?? '',
            'display_name' => $joueur['nom_affichage'] ?? '',
            'title' => $joueur['titre'] ?? '',
            'country' => $joueur['pays'] ?? '',
            'followers' => $joueur['abonnes'] ?? null,
            'fide' => $joueur['classement_fide'] ?? null,
            'last_online_label' => $joueur['derniere_presence_libelle'] ?? '',
        ];

        $classementsCompat = array_map(
            static fn (array $classement): array => [
                ...$classement,
                'label' => $classement['libelle'] ?? '',
                'rating' => $classement['classement'] ?? null,
                'best_rating' => $classement['meilleur_classement'] ?? null,
                'best_date_label' => $classement['meilleure_date_libelle'] ?? '',
                'games' => $classement['parties'] ?? null,
                'wins' => $classement['victoires'] ?? null,
                'losses' => $classement['defaites'] ?? null,
                'draws' => $classement['nulles'] ?? null,
            ],
            $classements
        );

        return [
            ...$instantane,
            'status' => match ((string) ($instantane['statut'] ?? 'absent')) {
                'lie' => 'linked',
                'erreur' => 'error',
                default => 'missing',
            },
            'profile_url' => $instantane['url_profil'] ?? '',
            'player' => $joueurCompat,
            'ratings' => $classementsCompat,
            'stats_note' => $instantane['note_statistiques'] ?? '',
            'fetched_at_label' => $instantane['date_recuperation_libelle'] ?? '',
        ];
    }
}
