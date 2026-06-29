<?php

/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : AccessibilityResponsiveSmokeTest.
 */

declare(strict_types=1);

namespace Tests\Feature;

use App\Repositories\UserRepository;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AccessibilityResponsiveSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_la_page_d_accueil_expose_les_landmarks_et_dialogues_principaux(): void
    {
        $reponse = $this->get('/');

        $reponse->assertOk();
        $reponse->assertSee('href="#main-content"', false);
        $reponse->assertSee('id="main-content"', false);
        $reponse->assertSee('data-burger-toggle', false);
        $reponse->assertSee('aria-controls="burger-panel"', false);
        $reponse->assertSee('aria-haspopup="dialog"', false);
        $reponse->assertSee('id="auth-modal"', false);
        $reponse->assertSee('data-consent-root', false);
        $reponse->assertSee('data-legal-modal="cookie-register"', false);
    }

    public function test_la_boutique_affiche_un_bloc_resultats_annonce_aux_technologies_d_assistance(): void
    {
        $membre = (new UserRepository)->creer([
            'nom' => 'Boutique',
            'prenom' => 'Camille',
            'date_naissance' => '1992-04-03',
            'courriel' => 'accessibilite-boutique@example.test',
            'numero_licence' => '',
            'mot_de_passe' => 'motdepasse-solide',
            'description_profil' => 'Compte boutique de test',
            'pseudo_chess' => '',
        ]);

        $_SESSION['identifiant_utilisateur'] = (string) $membre['identifiant'];

        $reponse = $this->get('/boutique');

        unset($_SESSION['identifiant_utilisateur']);

        $reponse->assertOk();
        $reponse->assertSee('id="boutique-results-title"', false);
        $reponse->assertSee('aria-labelledby="boutique-results-title"', false);
        $reponse->assertSee('role="status"', false);
        $reponse->assertSee('aria-live="polite"', false);
    }

    public function test_l_administration_utilise_des_boutons_de_filtre_compatibles_clavier(): void
    {
        $administrateur = (new UserRepository)->creer([
            'nom' => 'Admin',
            'prenom' => 'Alice',
            'date_naissance' => '1985-01-02',
            'courriel' => 'accessibilite-admin@example.test',
            'numero_licence' => '',
            'mot_de_passe' => 'motdepasse-solide',
            'description_profil' => 'Compte admin de test',
            'pseudo_chess' => '',
        ]);

        $_SESSION['identifiant_utilisateur'] = (string) $administrateur['identifiant'];

        $reponse = $this->get('/admin');

        unset($_SESSION['identifiant_utilisateur']);

        $reponse->assertOk();
        $reponse->assertSee('data-admin-tab-trigger="newsletter"', false);
        $reponse->assertSee('aria-pressed="true"', false);
        $reponse->assertSee('tabindex="0"', false);
    }
}
