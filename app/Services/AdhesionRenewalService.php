<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : AdhesionRenewalService.
 */

declare(strict_types=1);

namespace App\Services;

use App\Mail\AdhesionRenewalReminderMail;
use App\Models\CommandeLocale;
use App\Models\User;
use App\Repositories\UserRepository;
use DateTimeImmutable;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class AdhesionRenewalService
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    /**
     * Calcule la saison d'adhesion qui englobe la date fournie.
     */
    public static function saisonPourDate(DateTimeImmutable $date): string
    {
        $annee = (int) $date->format('Y');
        $mois = (int) $date->format('n');
        $debut = $mois >= 9 ? $annee : $annee - 1;

        return sprintf('%d-%d', $debut, $debut + 1);
    }

    public static function estJourDeRenouvellement(DateTimeImmutable $date): bool
    {
        return $date->format('m-d') === '09-01';
    }

    /**
     * @return array{date_reference: string, saison_cible: string, comptes_evalues: int, comptes_retrogrades: int, rappels_envoyes: int, execute: bool}
     */
    public function executerRemiseAJourAnnuelle(DateTimeImmutable $dateReference, bool $forcer = false): array
    {
        $saisonCible = self::saisonPourDate($dateReference);
        $resultat = [
            'date_reference' => $dateReference->format('Y-m-d'),
            'saison_cible' => $saisonCible,
            'comptes_evalues' => 0,
            'comptes_retrogrades' => 0,
            'rappels_envoyes' => 0,
            'execute' => false,
        ];

        if (! $forcer && ! self::estJourDeRenouvellement($dateReference)) {
            return $resultat;
        }

        foreach ($this->userRepository->listerTous() as $utilisateur) {
            if (! $this->doitEtreRemisAJourPourNouvelleSaison($utilisateur, $saisonCible)) {
                continue;
            }

            $resultat['comptes_evalues']++;

            $utilisateurMisAJour = $this->userRepository->desactiverAdhesionPourNouvelleSaison(
                (string) ($utilisateur['identifiant'] ?? ''),
                $saisonCible
            );

            if ($utilisateurMisAJour === null) {
                continue;
            }

            $resultat['comptes_retrogrades']++;

            if ($this->envoyerRappelRenouvellement($utilisateur, $saisonCible)) {
                $resultat['rappels_envoyes']++;
            }
        }

        $resultat['execute'] = true;

        return $resultat;
    }

    /**
     * Reactive l'adhesion quand une commande d'adhesion du compte est validee par l'admin.
     *
     * @param  array<string, mixed>  $commande
     * @return array<string, mixed>|null
     */
    public function activerDepuisCommandeValidee(array $commande, ?DateTimeImmutable $dateReference = null): ?array
    {
        $statut = (string) ($commande['statut'] ?? '');
        $categorie = (string) ($commande['categorie'] ?? '');
        $identifiantUtilisateur = (string) ($commande['identifiant_utilisateur'] ?? '');

        if (
            $statut !== CommandeLocale::STATUT_VALIDEE
            || $categorie !== 'adhesion'
            || $identifiantUtilisateur === ''
        ) {
            return null;
        }

        $dateReference ??= new DateTimeImmutable('now');

        return $this->userRepository->activerAdhesionPourSaison(
            $identifiantUtilisateur,
            self::saisonPourDate($dateReference),
            $dateReference
        );
    }

    /**
     * @param  array<string, mixed>  $utilisateur
     */
    private function doitEtreRemisAJourPourNouvelleSaison(array $utilisateur, string $saisonCible): bool
    {
        if ((string) ($utilisateur['statut_compte'] ?? '') !== User::STATUT_COMPTE_ACTIF) {
            return false;
        }

        $role = (string) ($utilisateur['role'] ?? '');
        $statutAdhesion = (string) ($utilisateur['statut_adhesion'] ?? '');
        $saisonActive = (string) ($utilisateur['saison_adhesion'] ?? '');

        if ($saisonActive === $saisonCible && $statutAdhesion === User::STATUT_ADHESION_ACTIVE) {
            return false;
        }

        return $role === User::ROLE_ADHERENT || $statutAdhesion === User::STATUT_ADHESION_ACTIVE;
    }

    /**
     * @param  array<string, mixed>  $utilisateur
     */
    private function envoyerRappelRenouvellement(array $utilisateur, string $saisonCible): bool
    {
        $courriel = trim((string) ($utilisateur['courriel'] ?? ''));

        if (! filter_var($courriel, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $nomComplet = trim((string) ($utilisateur['prenom'] ?? '') . ' ' . (string) ($utilisateur['nom'] ?? ''));
        $urlBoutique = rtrim((string) config('app.url', ''), '/') . '/boutique';
        $urlProfil = rtrim((string) config('app.url', ''), '/') . '/profil';

        try {
            Mail::to($courriel)->send(new AdhesionRenewalReminderMail(
                $nomComplet,
                $saisonCible,
                $urlBoutique,
                $urlProfil
            ));

            return true;
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }
}
