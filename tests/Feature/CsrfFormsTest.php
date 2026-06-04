<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Repositories\UserRepository;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CsrfFormsTest extends TestCase
{
    use RefreshDatabase;

    public function test_les_formulaires_publics_exposent_le_token_laravel_standard(): void
    {
        $this->seed(DatabaseSeeder::class);

        $reponse = $this->get('/');

        $reponse->assertOk();
        $reponse->assertSee('name="_token"', false);
        $reponse->assertSee('name="jeton_csrf"', false);
    }

    public function test_les_formulaires_admin_exposent_le_token_laravel_standard(): void
    {
        $this->seed(DatabaseSeeder::class);

        $administrateur = (new UserRepository())->creer([
            'nom' => 'Admin',
            'prenom' => 'Alice',
            'date_naissance' => '2000-01-02',
            'courriel' => 'admin@example.test',
            'numero_licence' => '',
            'mot_de_passe' => 'motdepasse-solide',
            'description_profil' => 'Compte de test',
            'pseudo_chess' => '',
        ]);

        $_SESSION['identifiant_utilisateur'] = (string) $administrateur['identifiant'];

        $reponse = $this->get('/admin');

        unset($_SESSION['identifiant_utilisateur']);

        $reponse->assertOk();
        $reponse->assertSee('name="_token"', false);
        $reponse->assertSee('name="jeton_csrf"', false);
    }
}
