<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : AdhesionRenewalFlowTest.
 */

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\AdhesionRenewalReminderMail;
use App\Models\CommandeLocale;
use App\Models\User;
use App\Repositories\OrderRepository;
use App\Repositories\UserRepository;
use App\Services\AdhesionRenewalService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

final class AdhesionRenewalFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_la_commande_annuelle_du_1er_septembre_retrograde_les_adherents_et_envoie_un_rappel(): void
    {
        Mail::fake();

        $administrateur = $this->creerMembre('admin-renouvellement@example.test');
        $membre = $this->creerMembre('membre-renouvellement@example.test');
        $depotUtilisateurs = new UserRepository;

        $depotUtilisateurs->mettreAJourAcces(
            (string) $membre['identifiant'],
            User::ROLE_ADHERENT,
            User::STATUT_COMPTE_ACTIF,
            User::STATUT_ADHESION_ACTIVE
        );

        $depotUtilisateurs->mettreAJourAcces(
            (string) $administrateur['identifiant'],
            User::ROLE_ADMIN,
            User::STATUT_COMPTE_ACTIF,
            User::STATUT_ADHESION_AUCUNE
        );

        $this->artisan('adhesions:renouvellement-annuel', [
            '--date' => '2026-09-01',
        ])->expectsOutputToContain('Saison cible : 2026-2027')
            ->expectsOutputToContain('Comptes retrogrades : 1')
            ->assertExitCode(0);

        $membreMisAJour = $depotUtilisateurs->trouverParIdentifiant((string) $membre['identifiant']);

        self::assertNotNull($membreMisAJour);
        self::assertSame(User::ROLE_CONNECTE, $membreMisAJour['role']);
        self::assertSame(User::STATUT_ADHESION_AUCUNE, $membreMisAJour['statut_adhesion']);
        self::assertSame('', (string) ($membreMisAJour['saison_adhesion'] ?? ''));
        self::assertSame('2026-2027', (string) ($membreMisAJour['saison_relance_adhesion'] ?? ''));

        Mail::assertSent(AdhesionRenewalReminderMail::class, function (AdhesionRenewalReminderMail $mail) use ($membre): bool {
            return $mail->hasTo((string) $membre['courriel'])
                && $mail->saisonCible === '2026-2027';
        });
    }

    public function test_la_commande_annuelle_reste_idempotente_pour_une_meme_saison(): void
    {
        Mail::fake();

        $administrateur = $this->creerMembre('admin-idempotent@example.test');
        $membre = $this->creerMembre('membre-idempotent@example.test');
        $depotUtilisateurs = new UserRepository;

        $depotUtilisateurs->mettreAJourAcces(
            (string) $membre['identifiant'],
            User::ROLE_ADHERENT,
            User::STATUT_COMPTE_ACTIF,
            User::STATUT_ADHESION_ACTIVE
        );

        $depotUtilisateurs->mettreAJourAcces(
            (string) $administrateur['identifiant'],
            User::ROLE_ADMIN,
            User::STATUT_COMPTE_ACTIF,
            User::STATUT_ADHESION_AUCUNE
        );

        $this->artisan('adhesions:renouvellement-annuel', [
            '--date' => '2026-09-01',
        ])->assertExitCode(0);

        $this->artisan('adhesions:renouvellement-annuel', [
            '--date' => '2026-09-01',
        ])->expectsOutputToContain('Rappels envoyes : 0')
            ->assertExitCode(0);

        Mail::assertSent(AdhesionRenewalReminderMail::class, 1);
    }

    public function test_valider_une_commande_d_adhesion_reactive_le_compte_du_membre_connecte(): void
    {
        $administrateur = $this->creerMembre('admin-boutique-adhesion@example.test');
        $membre = $this->creerMembre('membre-boutique-adhesion@example.test');
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
            'reference_produit' => 'ADH-2026',
            'produit' => 'Adhesion saison 2026/2027',
            'categorie' => 'adhesion',
            'quantite' => 1,
            'prix_unitaire_euros' => 95,
            'prix_total_euros' => 95,
            'code_mode_paiement' => CommandeLocale::MODE_PAIEMENT_SUR_PLACE,
            'code_statut_paiement' => CommandeLocale::STATUT_PAIEMENT_A_FINALISER,
        ]);

        $jetonCsrf = 'jeton-admin-adhesion';

        $this->withSession([
            'identifiant_utilisateur' => (string) $administrateur['identifiant'],
            '_token' => $jetonCsrf,
        ])->post('/admin', [
            '_token' => $jetonCsrf,
            'jeton_csrf' => $jetonCsrf,
            'action' => 'update_order_status',
            'identifiant_commande' => (string) ($commande['identifiant'] ?? ''),
            'statut_commande' => CommandeLocale::STATUT_VALIDEE,
        ])->assertRedirect('/admin');

        $membreMisAJour = $depotUtilisateurs->trouverParIdentifiant((string) $membre['identifiant']);

        self::assertNotNull($membreMisAJour);
        self::assertSame(User::ROLE_ADHERENT, $membreMisAJour['role']);
        self::assertSame(User::STATUT_ADHESION_ACTIVE, $membreMisAJour['statut_adhesion']);
        self::assertSame(
            AdhesionRenewalService::saisonPourDate(new \DateTimeImmutable('now')),
            (string) ($membreMisAJour['saison_adhesion'] ?? '')
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function creerMembre(string $courriel): array
    {
        return (new UserRepository())->creer([
            'nom' => 'Renouvellement',
            'prenom' => 'Test',
            'date_naissance' => '2000-02-03',
            'courriel' => $courriel,
            'numero_licence' => '',
            'mot_de_passe' => 'Motdepasse2026!',
            'description_profil' => '',
            'pseudo_chess' => '',
        ]);
    }
}
