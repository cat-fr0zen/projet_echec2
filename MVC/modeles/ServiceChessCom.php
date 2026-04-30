<?php

declare(strict_types=1);

final class ServiceChessCom
{
    private const URL_PROFIL = 'https://api.chess.com/pub/player/%s';
    private const URL_STATISTIQUES = 'https://api.chess.com/pub/player/%s/stats';
    private const DUREE_CACHE_SUCCES = 43200;
    private const DUREE_CACHE_ERREUR = 900;

    public function __construct(
        private string $dossierCache,
        private string $agentUtilisateur = 'association-echecs-site/1.0'
    ) {
        if (!is_dir($this->dossierCache)) {
            mkdir($this->dossierCache, 0777, true);
        }
    }

    public function recupererInstantaneJoueur(string $pseudo): array
    {
        $pseudoNormalise = $this->normaliserPseudo($pseudo);

        if ($pseudoNormalise === '') {
            return $this->ajouterAliasCompatibilite([
                'statut' => 'absent',
                'pseudo' => '',
                'message' => "Aucun pseudo Chess.com n'est enregistré pour ce profil.",
            ]);
        }

        $instantaneCache = $this->lireCache($pseudoNormalise);

        if ($instantaneCache !== null) {
            return $this->ajouterAliasCompatibilite($instantaneCache);
        }

        $pseudoEncode = rawurlencode($pseudoNormalise);
        $reponseProfil = $this->effectuerRequeteJson(sprintf(self::URL_PROFIL, $pseudoEncode));

        if (($reponseProfil['code_statut'] ?? 0) !== 200 || !is_array($reponseProfil['donnees'] ?? null)) {
            $instantane = $this->construireInstantaneErreur($pseudoNormalise, (int) ($reponseProfil['code_statut'] ?? 0));
            $this->ecrireCache($pseudoNormalise, $instantane, self::DUREE_CACHE_ERREUR);

            return $this->ajouterAliasCompatibilite($instantane);
        }

        $reponseStatistiques = $this->effectuerRequeteJson(sprintf(self::URL_STATISTIQUES, $pseudoEncode));

        if (($reponseStatistiques['code_statut'] ?? 0) !== 200 || !is_array($reponseStatistiques['donnees'] ?? null)) {
            $instantane = $this->construireInstantaneErreur($pseudoNormalise, (int) ($reponseStatistiques['code_statut'] ?? 0));
            $this->ecrireCache($pseudoNormalise, $instantane, self::DUREE_CACHE_ERREUR);

            return $this->ajouterAliasCompatibilite($instantane);
        }

        $instantane = $this->construireInstantaneSucces(
            $pseudoNormalise,
            $reponseProfil['donnees'],
            $reponseStatistiques['donnees']
        );

        $this->ecrireCache($pseudoNormalise, $instantane, self::DUREE_CACHE_SUCCES);

        return $this->ajouterAliasCompatibilite($instantane);
    }

    public function normaliserPseudo(?string $pseudo): string
    {
        return mb_strtolower(trim((string) $pseudo));
    }

    private function construireInstantaneSucces(string $pseudo, array $donneesProfil, array $donneesStatistiques): array
    {
        $dateRecuperation = gmdate('c');

        return [
            'statut' => 'lie',
            'pseudo' => $pseudo,
            'url_profil' => (string) ($donneesProfil['url'] ?? ('https://www.chess.com/member/' . rawurlencode($pseudo))),
            'joueur' => [
                'pseudo' => (string) ($donneesProfil['username'] ?? $pseudo),
                'nom_affichage' => (string) ($donneesProfil['name'] ?? $donneesProfil['username'] ?? $pseudo),
                'titre' => (string) ($donneesProfil['title'] ?? ''),
                'avatar' => (string) ($donneesProfil['avatar'] ?? ''),
                'pays' => $this->extrairePays((string) ($donneesProfil['country'] ?? '')),
                'abonnes' => $this->versEntierNullable($donneesProfil['followers'] ?? null),
                'classement_fide' => $this->versEntierNullable($donneesProfil['fide'] ?? null),
                'derniere_presence_libelle' => $this->formatterDernierePresence($donneesProfil['last_online'] ?? null),
            ],
            'classements' => $this->extraireClassements($donneesStatistiques),
            'note_statistiques' => "Données publiques Chess.com affichées en lecture seule.",
            'date_recuperation' => $dateRecuperation,
            'date_recuperation_libelle' => $this->formatterDateRecuperation($dateRecuperation),
            'source_cache' => 'direct',
            'message' => '',
        ];
    }

    private function construireInstantaneErreur(string $pseudo, int $codeStatut): array
    {
        $message = match ($codeStatut) {
            404 => "Le pseudo Chess.com renseigné n'a pas été trouvé dans les données publiques.",
            410 => "Les données publiques Chess.com de ce profil ne sont plus disponibles.",
            429 => "Chess.com limite temporairement les requêtes. Réessaie un peu plus tard.",
            default => "Les statistiques publiques Chess.com sont temporairement indisponibles pour ce profil.",
        };

        return [
            'statut' => 'erreur',
            'pseudo' => $pseudo,
            'url_profil' => 'https://www.chess.com/member/' . rawurlencode($pseudo),
            'joueur' => [
                'pseudo' => $pseudo,
                'nom_affichage' => $pseudo,
                'titre' => '',
                'avatar' => '',
                'pays' => '',
                'abonnes' => null,
                'classement_fide' => null,
                'derniere_presence_libelle' => '',
            ],
            'classements' => [],
            'note_statistiques' => "La liaison reste facultative et repose uniquement sur les données publiques de Chess.com.",
            'date_recuperation' => gmdate('c'),
            'date_recuperation_libelle' => "Dernière tentative : à l'instant",
            'source_cache' => 'direct',
            'message' => $message,
        ];
    }

    private function extraireClassements(array $donneesStatistiques): array
    {
        $correspondances = [
            'chess_rapid' => 'Rapide',
            'chess_blitz' => 'Blitz',
            'chess_bullet' => 'Bullet',
            'chess_daily' => 'Daily',
            'fide' => 'FIDE',
            'tactics' => 'Tactiques',
            'puzzle_rush' => 'Puzzle Rush',
        ];

        $classements = [];

        foreach ($correspondances as $cleTechnique => $libelle) {
            if (!isset($donneesStatistiques[$cleTechnique]) || !is_array($donneesStatistiques[$cleTechnique])) {
                continue;
            }

            $entree = $donneesStatistiques[$cleTechnique];
            $classementActuel = $this->versEntierNullable($entree['last']['rating'] ?? $entree['rating'] ?? null);
            $meilleurClassement = $this->versEntierNullable($entree['best']['rating'] ?? $entree['highest']['rating'] ?? null);
            $meilleureDateLibelle = $this->formatterDateUnix($entree['best']['date'] ?? $entree['highest']['date'] ?? null);
            $victoires = $this->versEntierNullable($entree['record']['win'] ?? null);
            $defaites = $this->versEntierNullable($entree['record']['loss'] ?? null);
            $nulles = $this->versEntierNullable($entree['record']['draw'] ?? null);
            $parties = null;

            if ($victoires !== null || $defaites !== null || $nulles !== null) {
                $parties = (int) (($victoires ?? 0) + ($defaites ?? 0) + ($nulles ?? 0));
            }

            $classements[] = [
                'cle' => $cleTechnique,
                'libelle' => $libelle,
                'classement' => $classementActuel,
                'meilleur_classement' => $meilleurClassement,
                'meilleure_date_libelle' => $meilleureDateLibelle,
                'parties' => $parties,
                'victoires' => $victoires,
                'defaites' => $defaites,
                'nulles' => $nulles,
            ];
        }

        return $classements;
    }

    private function effectuerRequeteJson(string $url): array
    {
        $enTetes = [
            'Accept: application/json',
            'User-Agent: ' . $this->agentUtilisateur,
        ];

        $contexte = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 6,
                'ignore_errors' => true,
                'header' => implode("\r\n", $enTetes),
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $contenu = @file_get_contents($url, false, $contexte);
        $enTetesReponse = $http_response_header ?? [];
        $codeStatut = $this->extraireCodeStatut($enTetesReponse);
        $donnees = null;

        if (is_string($contenu) && trim($contenu) !== '') {
            $donneesDecodees = json_decode($contenu, true);
            $donnees = is_array($donneesDecodees) ? $donneesDecodees : null;
        }

        return [
            'code_statut' => $codeStatut,
            'donnees' => $donnees,
        ];
    }

    private function extraireCodeStatut(array $enTetesReponse): int
    {
        if ($enTetesReponse === []) {
            return 0;
        }

        $ligneStatut = (string) ($enTetesReponse[0] ?? '');

        if (preg_match('/\s(\d{3})\s/', $ligneStatut, $correspondances) === 1) {
            return (int) $correspondances[1];
        }

        return 0;
    }

    private function extrairePays(string $urlPays): string
    {
        if ($urlPays === '') {
            return '';
        }

        $urlEpuree = rtrim($urlPays, '/');
        $codePays = strtoupper((string) basename($urlEpuree));

        return $codePays !== '' ? $codePays : '';
    }

    private function formatterDernierePresence(mixed $valeur): string
    {
        return $this->formatterDateHeureUnix($valeur, 'Dernière présence : %s');
    }

    private function formatterDateRecuperation(string $valeur): string
    {
        try {
            $date = new DateTimeImmutable($valeur);
        } catch (Exception) {
            return '';
        }

        return 'Données récupérées le ' . $date->setTimezone(new DateTimeZone('Europe/Paris'))->format('d/m/Y à H:i');
    }

    private function formatterDateUnix(mixed $valeur): string
    {
        $horodatage = $this->versEntierNullable($valeur);

        if ($horodatage === null || $horodatage <= 0) {
            return '';
        }

        try {
            return (new DateTimeImmutable('@' . $horodatage))
                ->setTimezone(new DateTimeZone('Europe/Paris'))
                ->format('d/m/Y');
        } catch (Exception) {
            return '';
        }
    }

    private function formatterDateHeureUnix(mixed $valeur, string $modele): string
    {
        $horodatage = $this->versEntierNullable($valeur);

        if ($horodatage === null || $horodatage <= 0) {
            return '';
        }

        try {
            $libelle = (new DateTimeImmutable('@' . $horodatage))
                ->setTimezone(new DateTimeZone('Europe/Paris'))
                ->format('d/m/Y à H:i');

            return sprintf($modele, $libelle);
        } catch (Exception) {
            return '';
        }
    }

    private function versEntierNullable(mixed $valeur): ?int
    {
        if ($valeur === null || $valeur === '') {
            return null;
        }

        if (!is_numeric($valeur)) {
            return null;
        }

        return (int) $valeur;
    }

    private function cheminCache(string $pseudo): string
    {
        $nomSecurise = preg_replace('/[^a-z0-9_-]+/i', '-', $pseudo) ?: 'joueur';

        return rtrim($this->dossierCache, '/\\') . DIRECTORY_SEPARATOR . $nomSecurise . '.json';
    }

    private function lireCache(string $pseudo): ?array
    {
        $cheminCache = $this->cheminCache($pseudo);

        if (!is_file($cheminCache)) {
            return null;
        }

        $contenu = @file_get_contents($cheminCache);

        if (!is_string($contenu) || trim($contenu) === '') {
            return null;
        }

        $donnees = json_decode($contenu, true);

        if (!is_array($donnees)) {
            return null;
        }

        $expireLe = (int) ($donnees['_expire_le'] ?? $donnees['_expires_at'] ?? 0);
        $instantane = $donnees['instantane'] ?? $donnees['snapshot'] ?? null;

        if ($expireLe < time() || !is_array($instantane)) {
            return null;
        }

        $instantane['source_cache'] = 'cache';

        return $instantane;
    }

    private function ecrireCache(string $pseudo, array $instantane, int $dureeSecondes): void
    {
        $cheminCache = $this->cheminCache($pseudo);
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
