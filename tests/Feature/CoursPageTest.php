<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Repositories\UserRepository;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CoursPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_la_page_cours_affiche_un_hub_avec_trois_entrees_principales(): void
    {
        $utilisateur = (new UserRepository())->creer([
            'nom' => 'Cours',
            'prenom' => 'Test',
            'date_naissance' => '2001-05-06',
            'courriel' => 'cours-page@example.test',
            'numero_licence' => '',
            'mot_de_passe' => 'Motdepasse2026!',
            'description_profil' => '',
            'pseudo_chess' => '',
        ]);

        $this->withSession([
            'identifiant_utilisateur' => (string) $utilisateur['identifiant'],
        ])->get('/guide')
            ->assertOk()
            ->assertSeeText('Cours')
            ->assertSeeText('Livrets')
            ->assertSeeText('Methodologie / strategie')
            ->assertSeeText('Pedagogie')
            ->assertSeeText('Progression')
            ->assertSee('href="/cours-livrets"', false)
            ->assertSee('href="/cours-seances"', false)
            ->assertSee('href="/cours-progression"', false)
            ->assertDontSeeText('Livrets A a E')
            ->assertDontSee('id="cours-cours"', false)
            ->assertDontSee('id="cours-methodologie"', false)
            ->assertDontSee('id="cours-strategie"', false);
    }

    public function test_la_page_livrets_affiche_les_entrees_vers_les_niveaux_a_a_e(): void
    {
        $utilisateur = (new UserRepository())->creer([
            'nom' => 'Livret',
            'prenom' => 'Test',
            'date_naissance' => '2001-05-06',
            'courriel' => 'livret-page@example.test',
            'numero_licence' => '',
            'mot_de_passe' => 'Motdepasse2026!',
            'description_profil' => '',
            'pseudo_chess' => '',
        ]);

        $this->withSession([
            'identifiant_utilisateur' => (string) $utilisateur['identifiant'],
        ])->get('/cours-livrets')
            ->assertOk()
            ->assertSeeText('Livrets')
            ->assertSeeText('Choisir un niveau')
            ->assertSeeText('Retour a la page Cours')
            ->assertSee('href="/cours-livret-a"', false)
            ->assertSee('href="/cours-livret-e"', false);
    }

    public function test_un_livret_a_sa_propre_page_dediee(): void
    {
        $utilisateur = (new UserRepository())->creer([
            'nom' => 'Livret',
            'prenom' => 'Test',
            'date_naissance' => '2001-05-06',
            'courriel' => 'livret-detail-page@example.test',
            'numero_licence' => '',
            'mot_de_passe' => 'Motdepasse2026!',
            'description_profil' => '',
            'pseudo_chess' => '',
        ]);

        $this->withSession([
            'identifiant_utilisateur' => (string) $utilisateur['identifiant'],
        ])->get('/cours-livret-a')
            ->assertOk()
            ->assertSeeText('Livret A')
            ->assertSeeText('Retour a la page Livrets')
            ->assertSeeText('Choisir un autre livret')
            ->assertSee('href="/cours-livret-b"', false)
            ->assertSee('aria-current="page"', false)
            ->assertSee('id="cours-livret-a"', false);
    }

    public function test_la_page_progression_affiche_les_liens_vers_methodologie_et_strategie(): void
    {
        $utilisateur = (new UserRepository())->creer([
            'nom' => 'Progression',
            'prenom' => 'Test',
            'date_naissance' => '2001-05-06',
            'courriel' => 'progression-page@example.test',
            'numero_licence' => '',
            'mot_de_passe' => 'Motdepasse2026!',
            'description_profil' => '',
            'pseudo_chess' => '',
        ]);

        $this->withSession([
            'identifiant_utilisateur' => (string) $utilisateur['identifiant'],
        ])->get('/cours-progression')
            ->assertOk()
            ->assertSeeText('Methodologie / strategie')
            ->assertSeeText('Deux pages dediees')
            ->assertSee('href="/cours-methodologie"', false)
            ->assertSee('href="/cours-strategie"', false);
    }

    public function test_la_page_cours_dediee_affiche_sa_rubrique_documentaire(): void
    {
        $utilisateur = (new UserRepository())->creer([
            'nom' => 'Seance',
            'prenom' => 'Test',
            'date_naissance' => '2001-05-06',
            'courriel' => 'seance-page@example.test',
            'numero_licence' => '',
            'mot_de_passe' => 'Motdepasse2026!',
            'description_profil' => '',
            'pseudo_chess' => '',
        ]);

        $this->withSession([
            'identifiant_utilisateur' => (string) $utilisateur['identifiant'],
        ])->get('/cours-seances')
            ->assertOk()
            ->assertSeeText('Cours')
            ->assertSeeText('Le fil des seances')
            ->assertSee('id="cours-cours"', false);
    }
}
