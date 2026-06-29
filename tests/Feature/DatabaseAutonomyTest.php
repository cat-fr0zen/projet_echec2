<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : DatabaseAutonomyTest.
 */

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CommandeLocale;
use App\Models\User;
use App\Repositories\OrderRepository;
use App\Repositories\UserRepository;
use App\Services\AdhesionRenewalService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class DatabaseAutonomyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_la_base_promeut_un_compte_connecte_quand_une_adhesion_active_est_ecrite_directement(): void
    {
        $this->creerCompte('admin-autonomie@example.test');
        $membre = $this->creerCompte('membre-autonomie@example.test');

        DB::table('compte_membre')
            ->where('identifiant', (string) $membre['identifiant'])
            ->update([
                'code_role' => User::ROLE_CONNECTE,
                'code_statut_adhesion' => User::STATUT_ADHESION_ACTIVE,
                'saison_adhesion_active' => null,
                'saison_relance_adhesion' => '2025-2026',
                'adhesion_renouvelee_le' => null,
            ]);

        $membreMisAJour = (new UserRepository())->trouverParIdentifiant((string) $membre['identifiant']);

        self::assertNotNull($membreMisAJour);
        self::assertSame(User::ROLE_ADHERENT, $membreMisAJour['role']);
        self::assertSame(User::STATUT_ADHESION_ACTIVE, $membreMisAJour['statut_adhesion']);
        self::assertSame(
            AdhesionRenewalService::saisonPourDate(new \DateTimeImmutable('now')),
            (string) ($membreMisAJour['saison_adhesion'] ?? '')
        );
        self::assertSame('', (string) ($membreMisAJour['saison_relance_adhesion'] ?? ''));
        self::assertNotSame('', (string) ($membreMisAJour['adhesion_renouvelee_le'] ?? ''));
    }

    public function test_la_base_retrograde_un_adherent_si_ladhesion_est_retirée_directement(): void
    {
        $this->creerCompte('admin-retrograde@example.test');
        $membre = $this->creerCompte('membre-retrograde@example.test');
        $depotUtilisateurs = new UserRepository;

        $depotUtilisateurs->mettreAJourAcces(
            (string) $membre['identifiant'],
            User::ROLE_ADHERENT,
            User::STATUT_COMPTE_ACTIF,
            User::STATUT_ADHESION_ACTIVE
        );

        DB::table('compte_membre')
            ->where('identifiant', (string) $membre['identifiant'])
            ->update([
                'code_role' => User::ROLE_ADHERENT,
                'code_statut_adhesion' => User::STATUT_ADHESION_AUCUNE,
                'saison_relance_adhesion' => '2026-2027',
            ]);

        $membreMisAJour = $depotUtilisateurs->trouverParIdentifiant((string) $membre['identifiant']);

        self::assertNotNull($membreMisAJour);
        self::assertSame(User::ROLE_CONNECTE, $membreMisAJour['role']);
        self::assertSame(User::STATUT_ADHESION_AUCUNE, $membreMisAJour['statut_adhesion']);
        self::assertSame('', (string) ($membreMisAJour['saison_adhesion'] ?? ''));
        self::assertSame('2026-2027', (string) ($membreMisAJour['saison_relance_adhesion'] ?? ''));
    }

    public function test_la_base_reactive_directement_le_compte_quand_une_commande_adhesion_est_validee(): void
    {
        $this->creerCompte('admin-commande-directe@example.test');
        $membre = $this->creerCompte('membre-commande-directe@example.test');
        $depotUtilisateurs = new UserRepository;
        $depotCommandes = new OrderRepository;

        $depotUtilisateurs->mettreAJourAcces(
            (string) $membre['identifiant'],
            User::ROLE_CONNECTE,
            User::STATUT_COMPTE_ACTIF,
            User::STATUT_ADHESION_AUCUNE
        );

        $commande = $depotCommandes->creer([
            'identifiant_utilisateur' => (string) $membre['identifiant'],
            'reference_produit' => 'ADH-DIRECT-01',
            'produit' => 'Adhesion directe',
            'categorie' => 'adhesion',
            'quantite' => 1,
            'prix_unitaire_euros' => 95,
            'prix_total_euros' => 95,
            'code_mode_paiement' => CommandeLocale::MODE_PAIEMENT_SUR_PLACE,
            'code_statut_paiement' => CommandeLocale::STATUT_PAIEMENT_A_FINALISER,
        ]);

        DB::table('commande_locale')
            ->where('identifiant', (string) ($commande['identifiant'] ?? ''))
            ->update([
                'code_statut' => CommandeLocale::STATUT_VALIDEE,
            ]);

        $membreMisAJour = $depotUtilisateurs->trouverParIdentifiant((string) $membre['identifiant']);

        self::assertNotNull($membreMisAJour);
        self::assertSame(User::ROLE_ADHERENT, $membreMisAJour['role']);
        self::assertSame(User::STATUT_ADHESION_ACTIVE, $membreMisAJour['statut_adhesion']);
        self::assertSame(
            AdhesionRenewalService::saisonPourDate(new \DateTimeImmutable('now')),
            (string) ($membreMisAJour['saison_adhesion'] ?? '')
        );
    }

    public function test_la_base_ignore_une_commande_validee_hors_adhesion(): void
    {
        $this->creerCompte('admin-commande-standard@example.test');
        $membre = $this->creerCompte('membre-commande-standard@example.test');
        $depotUtilisateurs = new UserRepository;
        $depotCommandes = new OrderRepository;

        $depotUtilisateurs->mettreAJourAcces(
            (string) $membre['identifiant'],
            User::ROLE_CONNECTE,
            User::STATUT_COMPTE_ACTIF,
            User::STATUT_ADHESION_AUCUNE
        );

        $commande = $depotCommandes->creer([
            'identifiant_utilisateur' => (string) $membre['identifiant'],
            'reference_produit' => 'TEXT-DIRECT-01',
            'produit' => 'Polo direct',
            'categorie' => 'textile',
            'quantite' => 1,
            'prix_unitaire_euros' => 32,
            'prix_total_euros' => 32,
            'code_mode_paiement' => CommandeLocale::MODE_PAIEMENT_SUR_PLACE,
            'code_statut_paiement' => CommandeLocale::STATUT_PAIEMENT_A_FINALISER,
        ]);

        DB::table('commande_locale')
            ->where('identifiant', (string) ($commande['identifiant'] ?? ''))
            ->update([
                'code_statut' => CommandeLocale::STATUT_VALIDEE,
            ]);

        $membreMisAJour = $depotUtilisateurs->trouverParIdentifiant((string) $membre['identifiant']);

        self::assertNotNull($membreMisAJour);
        self::assertSame(User::ROLE_CONNECTE, $membreMisAJour['role']);
        self::assertSame(User::STATUT_ADHESION_AUCUNE, $membreMisAJour['statut_adhesion']);
    }

    /**
     * @return array<string, mixed>
     */
    private function creerCompte(string $courriel): array
    {
        return (new UserRepository())->creer([
            'nom' => 'Autonomie',
            'prenom' => 'Base',
            'date_naissance' => '1999-01-02',
            'courriel' => $courriel,
            'numero_licence' => '',
            'mot_de_passe' => 'Motdepasse2026!',
            'description_profil' => '',
            'pseudo_chess' => '',
            'pseudo_lichess' => '',
        ]);
    }
}
