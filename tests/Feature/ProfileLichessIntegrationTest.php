<?php
/**
 * Verifie la liaison Lichess visible dans le profil membre.
 */

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Repositories\UserRepository;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

final class ProfileLichessIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    protected function tearDown(): void
    {
        Auth::logout();
        Auth::forgetGuards();
        Session::flush();
        unset($_SESSION['identifiant_utilisateur']);
        $this->defaultCookies = [];
        $this->unencryptedCookies = [];

        parent::tearDown();
    }

    public function test_le_profil_affiche_les_statistiques_lichess_publiques_du_membre(): void
    {
        $utilisateur = (new UserRepository())->creer([
            'nom' => 'Dupont',
            'prenom' => 'Lina',
            'date_naissance' => '1998-06-03',
            'courriel' => 'lina-lichess@example.test',
            'numero_licence' => '',
            'mot_de_passe' => 'Motdepasse2026!',
            'description_profil' => '',
            'pseudo_chess' => '',
            'pseudo_lichess' => 'testlichess',
        ]);

        Http::fake([
            'https://lichess.org/api/user/testlichess' => Http::response([
                'username' => 'TestLichess',
                'url' => 'https://lichess.org/@/TestLichess',
                'perfs' => [
                    'rapid' => [
                        'rating' => 1524,
                        'games' => 28,
                        'prog' => 8,
                    ],
                ],
            ], 200),
        ]);

        $modele = User::query()->findOrFail((string) $utilisateur['identifiant']);

        $this->actingAs($modele)
            ->get('/profil')
            ->assertOk()
            ->assertSeeText('Lichess')
            ->assertSeeText('@TestLichess')
            ->assertSeeText('1524');
    }

    public function test_la_mise_a_jour_du_profil_persiste_le_pseudo_lichess(): void
    {
        $utilisateur = (new UserRepository())->creer([
            'nom' => 'Dupont',
            'prenom' => 'Lina',
            'date_naissance' => '1998-06-03',
            'courriel' => 'maj-lichess@example.test',
            'numero_licence' => '',
            'mot_de_passe' => 'Motdepasse2026!',
            'description_profil' => '',
            'pseudo_chess' => '',
            'pseudo_lichess' => '',
        ]);
        $modele = User::query()->findOrFail((string) $utilisateur['identifiant']);
        $jetonCsrf = 'jeton-profil-lichess';

        $this->actingAs($modele)
            ->withSession([
                '_token' => $jetonCsrf,
            ])
            ->post('/profil', [
                'action' => 'update_profile',
                '_token' => $jetonCsrf,
                'jeton_csrf' => $jetonCsrf,
                'last_name' => 'Dupont',
                'first_name' => 'Lina',
                'birth_date' => '1998-06-03',
                'numero_licence' => '',
                'chess_username' => '',
                'lichess_username' => 'lina_club',
                'profile_description' => 'Profil de test',
            ])
            ->assertRedirect('/profil');

        $this->assertDatabaseHas('compte_membre', [
            'identifiant' => (string) $utilisateur['identifiant'],
            'pseudo_lichess' => 'lina_club',
        ]);
    }
}
