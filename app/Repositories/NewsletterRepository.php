<?php

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

    public function listerActifs(): array
    {
        $rows = DB::table('newsletter_abonnement')
            ->where('code_statut', self::STATUT_ACTIF)
            ->orderBy('cree_le')
            ->get()
            ->all();

        return array_map(fn (object $row): array => $this->normaliserAbonnement((array) $row), $rows);
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
}
