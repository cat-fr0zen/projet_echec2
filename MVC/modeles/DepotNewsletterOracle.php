<?php

declare(strict_types=1);

final class DepotNewsletterOracle
{
    public const STATUT_ACTIF = 'actif';
    public const STATUT_DESABONNE = 'desabonne';
    public const CONSENTEMENT_VERSION = 'newsletter-2026-05';

    public function __construct(private BaseDeDonneesOracle $base)
    {
    }

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

            $jeton = $this->genererJetonDesabonnement();

            $this->base->executer(
                'UPDATE newsletter_abonnement
                    SET statut = :statut,
                        jeton_desabonnement = :jeton_desabonnement,
                        consentement_version = :consentement_version,
                        adresse_ip_hachee = :adresse_ip_hachee,
                        agent_utilisateur = :agent_utilisateur,
                        source_inscription = :source_inscription,
                        confirme_le = SYSDATE,
                        desabonne_le = NULL
                  WHERE courriel_normalise = :courriel_normalise',
                [
                    'statut' => self::STATUT_ACTIF,
                    'jeton_desabonnement' => $jeton,
                    'consentement_version' => self::CONSENTEMENT_VERSION,
                    'adresse_ip_hachee' => $adresseIpHachee,
                    'agent_utilisateur' => $this->limiter($agentUtilisateur, 255),
                    'source_inscription' => $this->limiter($sourceInscription, 80),
                    'courriel_normalise' => $courrielNormalise,
                ]
            );

            return $this->trouverParCourriel($courrielNormalise) ?? [];
        }

        $identifiant = 'newsletter_' . bin2hex(random_bytes(8));
        $jeton = $this->genererJetonDesabonnement();

        $this->base->executer(
            'INSERT INTO newsletter_abonnement (
                identifiant_abonnement,
                courriel,
                courriel_normalise,
                statut,
                jeton_desabonnement,
                consentement_version,
                adresse_ip_hachee,
                agent_utilisateur,
                source_inscription,
                cree_le,
                confirme_le
            ) VALUES (
                :identifiant_abonnement,
                :courriel,
                :courriel_normalise,
                :statut,
                :jeton_desabonnement,
                :consentement_version,
                :adresse_ip_hachee,
                :agent_utilisateur,
                :source_inscription,
                SYSDATE,
                SYSDATE
            )',
            [
                'identifiant_abonnement' => $identifiant,
                'courriel' => $courrielNormalise,
                'courriel_normalise' => $courrielNormalise,
                'statut' => self::STATUT_ACTIF,
                'jeton_desabonnement' => $jeton,
                'consentement_version' => self::CONSENTEMENT_VERSION,
                'adresse_ip_hachee' => $adresseIpHachee,
                'agent_utilisateur' => $this->limiter($agentUtilisateur, 255),
                'source_inscription' => $this->limiter($sourceInscription, 80),
            ]
        );

        return $this->trouverParCourriel($courrielNormalise) ?? [];
    }

    public function desabonner(string $jeton): bool
    {
        $jeton = trim($jeton);

        if ($jeton === '' || strlen($jeton) > 80) {
            return false;
        }

        return $this->base->executer(
            'UPDATE newsletter_abonnement
                SET statut = :statut,
                    desabonne_le = SYSDATE
              WHERE jeton_desabonnement = :jeton
                AND statut = :statut_actif',
            [
                'statut' => self::STATUT_DESABONNE,
                'jeton' => $jeton,
                'statut_actif' => self::STATUT_ACTIF,
            ]
        ) > 0;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listerActifs(): array
    {
        $lignes = $this->base->lireTout(
            'SELECT
                identifiant_abonnement,
                courriel,
                courriel_normalise,
                statut,
                jeton_desabonnement,
                consentement_version,
                TO_CHAR(cree_le, \'YYYY-MM-DD"T"HH24:MI:SS"Z"\') cree_le,
                TO_CHAR(confirme_le, \'YYYY-MM-DD"T"HH24:MI:SS"Z"\') confirme_le
            FROM newsletter_abonnement
            WHERE statut = :statut
            ORDER BY cree_le',
            ['statut' => self::STATUT_ACTIF]
        );

        return array_map([$this, 'normaliserAbonnement'], $lignes);
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
        $this->base->executer(
            'INSERT INTO newsletter_envoi (
                identifiant_envoi,
                identifiant_abonnement,
                type_evenement,
                titre_evenement,
                url_evenement,
                sujet,
                statut_envoi,
                erreur_envoi,
                envoye_le
            ) VALUES (
                :identifiant_envoi,
                :identifiant_abonnement,
                :type_evenement,
                :titre_evenement,
                :url_evenement,
                :sujet,
                :statut_envoi,
                :erreur_envoi,
                SYSDATE
            )',
            [
                'identifiant_envoi' => 'newsletter_envoi_' . bin2hex(random_bytes(8)),
                'identifiant_abonnement' => $identifiantAbonnement,
                'type_evenement' => $typeEvenement,
                'titre_evenement' => $this->limiter($titreEvenement, 220),
                'url_evenement' => $this->limiter($urlEvenement, 500),
                'sujet' => $this->limiter($sujet, 220),
                'statut_envoi' => $statutEnvoi,
                'erreur_envoi' => $this->limiter($erreurEnvoi, 1000),
            ]
        );
    }

    private function trouverParCourriel(string $courriel): ?array
    {
        $ligne = $this->base->lireUne(
            'SELECT
                identifiant_abonnement,
                courriel,
                courriel_normalise,
                statut,
                jeton_desabonnement,
                consentement_version,
                TO_CHAR(cree_le, \'YYYY-MM-DD"T"HH24:MI:SS"Z"\') cree_le,
                TO_CHAR(confirme_le, \'YYYY-MM-DD"T"HH24:MI:SS"Z"\') confirme_le,
                TO_CHAR(desabonne_le, \'YYYY-MM-DD"T"HH24:MI:SS"Z"\') desabonne_le
            FROM newsletter_abonnement
            WHERE courriel_normalise = :courriel_normalise',
            ['courriel_normalise' => $this->normaliserCourriel($courriel)]
        );

        return $ligne !== null ? $this->normaliserAbonnement($ligne) : null;
    }

    private function normaliserAbonnement(array $ligne): array
    {
        return [
            'identifiant_abonnement' => (string) ($ligne['identifiant_abonnement'] ?? ''),
            'courriel' => (string) ($ligne['courriel'] ?? ''),
            'courriel_normalise' => (string) ($ligne['courriel_normalise'] ?? ''),
            'statut' => (string) ($ligne['statut'] ?? self::STATUT_ACTIF),
            'jeton_desabonnement' => (string) ($ligne['jeton_desabonnement'] ?? ''),
            'consentement_version' => (string) ($ligne['consentement_version'] ?? self::CONSENTEMENT_VERSION),
            'cree_le' => (string) ($ligne['cree_le'] ?? ''),
            'confirme_le' => (string) ($ligne['confirme_le'] ?? ''),
            'desabonne_le' => (string) ($ligne['desabonne_le'] ?? ''),
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
