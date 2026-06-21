<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : SharedEmailAccountsTest.
 */

declare(strict_types=1);

namespace Tests\Feature;

use App\Repositories\UserRepository;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SharedEmailAccountsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_un_meme_email_peut_etre_reutilise_si_les_comptes_concernes_ont_des_licences_distinctes(): void
    {
        (new UserRepository())->creer([
            'nom' => 'Parent',
            'prenom' => 'Alpha',
            'date_naissance' => '1980-01-02',
            'courriel' => 'famille@example.test',
            'numero_licence' => 'FFE-PARENT-01',
            'mot_de_passe' => 'Motdepasse2026!',
            'description_profil' => '',
            'pseudo_chess' => '',
        ]);

        $jetonCsrf = 'jeton-shared-email-ok';

        $reponse = $this->withSession(['_token' => $jetonCsrf])
            ->post('/', [
                'action' => 'inscription',
                '_token' => $jetonCsrf,
                'jeton_csrf' => $jetonCsrf,
                'page_redirection' => 'accueil',
                'nom' => 'Enfant',
                'prenom' => 'Bravo',
                'date_naissance' => '2013-04-05',
                'courriel' => 'famille@example.test',
                'numero_licence' => 'FFE-ENFANT-02',
                'mot_de_passe' => 'Motdepasse2026!',
                'description_profil' => '',
                'pseudo_chess' => '',
            ])
            ->assertRedirect('/profil')
            ->assertSessionHas('identifiant_utilisateur');

        $this->assertDatabaseCount('compte_membre', 2);
        $this->assertDatabaseHas('compte_membre', [
            'courriel_normalise' => 'famille@example.test',
            'numero_licence_federale' => 'FFE-PARENT-01',
        ]);
        $this->assertDatabaseHas('compte_membre', [
            'courriel_normalise' => 'famille@example.test',
            'numero_licence_federale' => 'FFE-ENFANT-02',
        ]);
    }

    public function test_un_email_deja_utilise_reste_bloque_si_un_des_comptes_n_a_pas_de_numero_de_licence(): void
    {
        (new UserRepository())->creer([
            'nom' => 'Parent',
            'prenom' => 'SansLicence',
            'date_naissance' => '1980-01-02',
            'courriel' => 'famille-bloquee@example.test',
            'numero_licence' => '',
            'mot_de_passe' => 'Motdepasse2026!',
            'description_profil' => '',
            'pseudo_chess' => '',
        ]);

        $jetonCsrf = 'jeton-shared-email-ko';

        $reponse = $this->withSession(['_token' => $jetonCsrf])
            ->post('/', [
                'action' => 'inscription',
                '_token' => $jetonCsrf,
                'jeton_csrf' => $jetonCsrf,
                'page_redirection' => 'accueil',
                'nom' => 'Enfant',
                'prenom' => 'Charlie',
                'date_naissance' => '2014-07-08',
                'courriel' => 'famille-bloquee@example.test',
                'numero_licence' => 'FFE-ENFANT-03',
                'mot_de_passe' => 'Motdepasse2026!',
                'description_profil' => '',
                'pseudo_chess' => '',
            ])
            ->assertRedirect('/');

        $reponse->assertSessionHas('etat_formulaire.erreurs');
        $erreurs = session('etat_formulaire.erreurs', []);

        $this->assertIsArray($erreurs);
        $this->assertContains(
            'Cet email est deja utilise par un compte sans numero de licence. Ajoute d abord un numero de licence a ce compte ou utilise un autre email.',
            $erreurs
        );
        $this->assertDatabaseCount('compte_membre', 1);
    }

    public function test_si_plusieurs_comptes_partagent_un_email_la_connexion_par_email_est_refusee_mais_la_licence_fonctionne(): void
    {
        (new UserRepository())->creer([
            'nom' => 'Parent',
            'prenom' => 'Delta',
            'date_naissance' => '1980-01-02',
            'courriel' => 'famille-connexion@example.test',
            'numero_licence' => 'FFE-DELTA-01',
            'mot_de_passe' => 'Motdepasse2026!',
            'description_profil' => '',
            'pseudo_chess' => '',
        ]);

        (new UserRepository())->creer([
            'nom' => 'Enfant',
            'prenom' => 'Echo',
            'date_naissance' => '2012-05-06',
            'courriel' => 'famille-connexion@example.test',
            'numero_licence' => 'FFE-ECHO-02',
            'mot_de_passe' => 'Motdepasse2026!',
            'description_profil' => '',
            'pseudo_chess' => '',
        ]);

        $jetonEmail = 'jeton-shared-login-email';

        $this->withSession(['_token' => $jetonEmail])
            ->post('/', [
                'action' => 'connexion',
                '_token' => $jetonEmail,
                'jeton_csrf' => $jetonEmail,
                'page_redirection' => 'accueil',
                'identifiant_connexion' => 'famille-connexion@example.test',
                'mot_de_passe' => 'Motdepasse2026!',
            ])
            ->assertRedirect('/');

        $this->assertSame(
            ['Plusieurs comptes partagent cet email. Connectez-vous avec le numero de licence du compte concerne.'],
            session('etat_formulaire.erreurs')
        );

        $jetonLicence = 'jeton-shared-login-licence';

        $this->withSession(['_token' => $jetonLicence])
            ->post('/', [
                'action' => 'connexion',
                '_token' => $jetonLicence,
                'jeton_csrf' => $jetonLicence,
                'page_redirection' => 'accueil',
                'identifiant_connexion' => 'FFE-ECHO-02',
                'mot_de_passe' => 'Motdepasse2026!',
            ])
            ->assertRedirect('/profil')
            ->assertSessionHas('identifiant_utilisateur');
    }
}
