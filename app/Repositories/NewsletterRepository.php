<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : NewsletterRepository.
 */

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class NewsletterRepository
{
    public const STATUT_ACTIF = 'actif';
    public const STATUT_DESABONNE = 'desabonne';
    public const CONSENTEMENT_VERSION = 'newsletter-2026-05';

    public function inscrire(
        string $courriel,
        string $adresseIpHachee,
        string $agentUtilisateur,
        string $sourceInscription = 'footer'
    ): array {
        $courrielNormalise = $this->normaliserCourriel($courriel);

        if ($courrielNormalise === '') {
            throw new InvalidArgumentException('Email newsletter invalide.');
        }

        $abonnementExistant = $this->trouverParCourriel($courrielNormalise);

        if ($abonnementExistant !== null) {
            if (($abonnementExistant['statut'] ?? '') === self::STATUT_ACTIF) {
                return $abonnementExistant;
            }

            DB::table('newsletter_abonnement')
                ->where('courriel_normalise', $courrielNormalise)
                ->update([
                    'code_statut' => self::STATUT_ACTIF,
                    'jeton_desabonnement' => $this->genererJetonDesabonnement(),
                    'consentement_version' => self::CONSENTEMENT_VERSION,
                    'adresse_ip_hachee' => $adresseIpHachee,
                    'agent_utilisateur' => $this->limiter($agentUtilisateur, 255),
                    'source_inscription' => $this->limiter($sourceInscription, 80),
                    'confirme_le' => date('Y-m-d H:i:s'),
                    'desabonne_le' => null,
                ]);

            return $this->trouverParCourriel($courrielNormalise) ?? [];
        }

        $identifiant = 'newsletter_' . bin2hex(random_bytes(8));

        DB::table('newsletter_abonnement')->insert([
            'identifiant_abonnement' => $identifiant,
            'courriel' => $courrielNormalise,
            'courriel_normalise' => $courrielNormalise,
            'code_statut' => self::STATUT_ACTIF,
            'jeton_desabonnement' => $this->genererJetonDesabonnement(),
            'consentement_version' => self::CONSENTEMENT_VERSION,
            'adresse_ip_hachee' => $adresseIpHachee,
            'agent_utilisateur' => $this->limiter($agentUtilisateur, 255),
            'source_inscription' => $this->limiter($sourceInscription, 80),
            'cree_le' => date('Y-m-d H:i:s'),
            'confirme_le' => date('Y-m-d H:i:s'),
        ]);

        return $this->trouverParCourriel($courrielNormalise) ?? [];
    }

    public function desabonner(string $jeton): bool
    {
        $jeton = trim($jeton);

        if ($jeton === '' || strlen($jeton) > 80) {
            return false;
        }

        return DB::table('newsletter_abonnement')
            ->where('jeton_desabonnement', $jeton)
            ->where('code_statut', self::STATUT_ACTIF)
            ->update([
                'code_statut' => self::STATUT_DESABONNE,
                'desabonne_le' => date('Y-m-d H:i:s'),
            ]) > 0;
    }

    public function trouverParJetonDesabonnement(string $jeton): ?array
    {
        $jeton = trim($jeton);

        if ($jeton === '' || strlen($jeton) > 80) {
            return null;
        }

        $row = DB::table('newsletter_abonnement')
            ->where('jeton_desabonnement', $jeton)
            ->first();

        return $row !== null ? $this->normaliserAbonnement((array) $row) : null;
    }

    public function listerActifs(): array
    {
        $rows = DB::table('newsletter_abonnement')
            ->where('code_statut', self::STATUT_ACTIF)
            ->orderBy('cree_le')
            ->get()
            ->all();

        return array_map(fn (object $row): array => $this->normaliserAbonnement((array) $row), $rows);
    }

    public function obtenirResumeAdmin(): array
    {
        return [
            'abonnes_total' => (int) DB::table('newsletter_abonnement')->count(),
            'abonnes_actifs' => (int) DB::table('newsletter_abonnement')
                ->where('code_statut', self::STATUT_ACTIF)
                ->count(),
            'abonnes_desabonnes' => (int) DB::table('newsletter_abonnement')
                ->where('code_statut', self::STATUT_DESABONNE)
                ->count(),
            'envois_total' => (int) DB::table('newsletter_envoi')->count(),
            'dernier_envoi_le' => (string) (DB::table('newsletter_envoi')->max('envoye_le') ?? ''),
        ];
    }

    public function listerAbonnementsAdmin(int $limite = 50): array
    {
        $rows = DB::table('newsletter_abonnement as abonnement')
            ->leftJoin(
                'ref_statut_newsletter_abonnement as statut',
                'statut.code_statut',
                '=',
                'abonnement.code_statut'
            )
            ->select([
                'abonnement.identifiant_abonnement',
                'abonnement.courriel',
                'abonnement.courriel_normalise',
                'abonnement.code_statut',
                'abonnement.jeton_desabonnement',
                'abonnement.consentement_version',
                'abonnement.cree_le',
                'abonnement.confirme_le',
                'abonnement.desabonne_le',
                'abonnement.source_inscription',
                'statut.libelle_statut as statut_libelle',
            ])
            ->orderByDesc('abonnement.cree_le')
            ->limit($this->bornerLimite($limite))
            ->get()
            ->all();

        return array_map(fn (object $row): array => $this->normaliserAbonnementAdmin((array) $row), $rows);
    }

    public function listerDerniersEnvois(int $limite = 50): array
    {
        $rows = DB::table('newsletter_envoi as envoi')
            ->join(
                'newsletter_abonnement as abonnement',
                'abonnement.identifiant_abonnement',
                '=',
                'envoi.identifiant_abonnement'
            )
            ->leftJoin(
                'ref_type_evenement_newsletter as type_evenement',
                'type_evenement.code_type_evenement',
                '=',
                'envoi.code_type_evenement'
            )
            ->leftJoin(
                'ref_statut_envoi_newsletter as statut_envoi',
                'statut_envoi.code_statut_envoi',
                '=',
                'envoi.code_statut_envoi'
            )
            ->select([
                'envoi.identifiant_envoi',
                'envoi.identifiant_abonnement',
                'envoi.code_type_evenement',
                'envoi.titre_evenement',
                'envoi.url_evenement',
                'envoi.sujet',
                'envoi.code_statut_envoi',
                'envoi.erreur_envoi',
                'envoi.envoye_le',
                'abonnement.courriel',
                'type_evenement.libelle_type_evenement as type_evenement_libelle',
                'statut_envoi.libelle_statut_envoi as statut_envoi_libelle',
            ])
            ->orderByDesc('envoi.envoye_le')
            ->limit($this->bornerLimite($limite))
            ->get()
            ->all();

        return array_map(fn (object $row): array => $this->normaliserEnvoiAdmin((array) $row), $rows);
    }

    public function enregistrerEnvoi(
        string $identifiantAbonnement,
        string $typeEvenement,
        string $titreEvenement,
        string $urlEvenement,
        string $sujet,
        string $statutEnvoi,
        string $erreurEnvoi = ''
    ): void {
        DB::table('newsletter_envoi')->insert([
            'identifiant_envoi' => 'newsletter_envoi_' . bin2hex(random_bytes(8)),
            'identifiant_abonnement' => $identifiantAbonnement,
            'code_type_evenement' => $typeEvenement,
            'titre_evenement' => $this->limiter($titreEvenement, 220),
            'url_evenement' => $this->limiter($urlEvenement, 500),
            'sujet' => $this->limiter($sujet, 220),
            'code_statut_envoi' => $statutEnvoi,
            'erreur_envoi' => $this->limiter($erreurEnvoi, 1000),
            'envoye_le' => date('Y-m-d H:i:s'),
        ]);
    }

    private function trouverParCourriel(string $courriel): ?array
    {
        $row = DB::table('newsletter_abonnement')
            ->where('courriel_normalise', $this->normaliserCourriel($courriel))
            ->first();

        return $row !== null ? $this->normaliserAbonnement((array) $row) : null;
    }

    private function normaliserAbonnement(array $row): array
    {
        return [
            'identifiant_abonnement' => (string) ($row['identifiant_abonnement'] ?? ''),
            'courriel' => (string) ($row['courriel'] ?? ''),
            'courriel_normalise' => (string) ($row['courriel_normalise'] ?? ''),
            'statut' => (string) ($row['code_statut'] ?? $row['statut'] ?? self::STATUT_ACTIF),
            'jeton_desabonnement' => (string) ($row['jeton_desabonnement'] ?? ''),
            'consentement_version' => (string) ($row['consentement_version'] ?? self::CONSENTEMENT_VERSION),
            'cree_le' => (string) ($row['cree_le'] ?? ''),
            'confirme_le' => (string) ($row['confirme_le'] ?? ''),
            'desabonne_le' => (string) ($row['desabonne_le'] ?? ''),
        ];
    }

    private function normaliserAbonnementAdmin(array $row): array
    {
        return [
            ...$this->normaliserAbonnement($row),
            'source_inscription' => (string) ($row['source_inscription'] ?? ''),
            'statut_libelle' => (string) ($row['statut_libelle'] ?? ucfirst((string) ($row['code_statut'] ?? 'actif'))),
        ];
    }

    private function normaliserEnvoiAdmin(array $row): array
    {
        return [
            'identifiant_envoi' => (string) ($row['identifiant_envoi'] ?? ''),
            'identifiant_abonnement' => (string) ($row['identifiant_abonnement'] ?? ''),
            'courriel' => (string) ($row['courriel'] ?? ''),
            'code_type_evenement' => (string) ($row['code_type_evenement'] ?? ''),
            'type_evenement_libelle' => (string) ($row['type_evenement_libelle'] ?? ucfirst((string) ($row['code_type_evenement'] ?? ''))),
            'titre_evenement' => (string) ($row['titre_evenement'] ?? ''),
            'url_evenement' => (string) ($row['url_evenement'] ?? ''),
            'sujet' => (string) ($row['sujet'] ?? ''),
            'code_statut_envoi' => (string) ($row['code_statut_envoi'] ?? ''),
            'statut_envoi_libelle' => (string) ($row['statut_envoi_libelle'] ?? ucfirst((string) ($row['code_statut_envoi'] ?? ''))),
            'erreur_envoi' => (string) ($row['erreur_envoi'] ?? ''),
            'envoye_le' => (string) ($row['envoye_le'] ?? ''),
        ];
    }

    private function normaliserCourriel(string $courriel): string
    {
        $courriel = mb_strtolower(trim($courriel));

        return filter_var($courriel, FILTER_VALIDATE_EMAIL) ? $courriel : '';
    }

    private function genererJetonDesabonnement(): string
    {
        return bin2hex(random_bytes(32));
    }

    private function limiter(string $valeur, int $limite): string
    {
        return function_exists('mb_substr') ? mb_substr($valeur, 0, $limite) : substr($valeur, 0, $limite);
    }

    private function bornerLimite(int $limite, int $maximum = 100): int
    {
        return max(1, min($limite, $maximum));
    }
}
