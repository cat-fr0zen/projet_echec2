<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Repositories\UserRepository;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LaravelAuthenticationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_la_connexion_authentifie_aussi_le_guard_laravel(): void
    {
        $utilisateur = $this->creerUtilisateur('auth-flow@example.test');
        $jetonCsrf = 'jeton-auth-flow';

        $this->withSession(['_token' => $jetonCsrf])
            ->post('/', [
                'action' => 'connexion',
                '_token' => $jetonCsrf,
                'jeton_csrf' => $jetonCsrf,
                'page_redirection' => 'accueil',
                'identifiant_connexion' => 'auth-flow@example.test',
                'mot_de_passe' => 'Motdepasse2026!',
            ])
            ->assertRedirect('/profil')
            ->assertSessionHas('identifiant_utilisateur', (string) $utilisateur['identifiant']);

        $this->assertAuthenticatedAs(User::query()->findOrFail((string) $utilisateur['identifiant']));
    }

    public function test_le_middleware_resynchronise_la_session_legacy_depuis_auth_laravel(): void
    {
        $utilisateur = $this->creerUtilisateur('auth-sync@example.test');
        $modele = User::query()->findOrFail((string) $utilisateur['identifiant']);

        $this->actingAs($modele)
            ->get('/profil')
            ->assertOk()
            ->assertSessionHas('identifiant_utilisateur', (string) $utilisateur['identifiant']);
    }

    public function test_la_deconnexion_vide_a_la_fois_l_auth_laravel_et_la_session_legacy(): void
    {
        $utilisateur = $this->creerUtilisateur('auth-logout@example.test');
        $modele = User::query()->findOrFail((string) $utilisateur['identifiant']);
        $jetonCsrf = 'jeton-logout-flow';

        $this->actingAs($modele)
            ->withSession([
                '_token' => $jetonCsrf,
                'identifiant_utilisateur' => (string) $utilisateur['identifiant'],
            ])
            ->post('/', [
                'action' => 'deconnexion',
                '_token' => $jetonCsrf,
                'jeton_csrf' => $jetonCsrf,
                'page_redirection' => 'accueil',
            ])
            ->assertRedirect('/');

        $this->assertGuest();
        self::assertNull(session('identifiant_utilisateur'));
    }

    /**
     * @return array<string, mixed>
     */
    private function creerUtilisateur(string $courriel): array
    {
        return (new UserRepository)->creer([
            'nom' => 'Test',
            'prenom' => 'Auth',
            'date_naissance' => '1992-03-04',
            'courriel' => $courriel,
            'numero_licence' => '',
            'mot_de_passe' => 'Motdepasse2026!',
            'description_profil' => '',
            'pseudo_chess' => '',
        ]);
    }
}
