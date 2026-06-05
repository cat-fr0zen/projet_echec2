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

    public function test_la_page_cours_affiche_des_blocs_compacts_et_les_niveaux_de_livret(): void
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
            ->assertSeeText('Livret A')
            ->assertSeeText('Livret E')
            ->assertDontSeeText('Ouvrir les livrets')
            ->assertDontSeeText('Voir les contenus');
    }
}
