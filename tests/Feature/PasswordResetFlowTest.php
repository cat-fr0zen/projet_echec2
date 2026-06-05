<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\ResetPasswordLinkMail;
use App\Models\User;
use App\Repositories\UserRepository;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

final class PasswordResetFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_la_demande_de_reinitialisation_envoie_un_email_si_le_compte_existe(): void
    {
        $this->creerUtilisateur('reset-request@example.test');
        Mail::fake();
        $jetonCsrf = 'jeton-reset-request';

        $this->withSession(['_token' => $jetonCsrf])
            ->post('/mot-de-passe/oublie', [
                '_token' => $jetonCsrf,
                'courriel' => 'reset-request@example.test',
            ])->assertRedirect();

        Mail::assertSent(ResetPasswordLinkMail::class, function (ResetPasswordLinkMail $mail): bool {
            return $mail->hasTo('reset-request@example.test');
        });
    }

    public function test_un_mot_de_passe_peut_etre_reinitialise_via_le_broker_laravel(): void
    {
        $utilisateur = $this->creerUtilisateur('reset-update@example.test');
        $modele = User::query()->findOrFail((string) $utilisateur['identifiant']);
        $token = Password::broker()->createToken($modele);
        $jetonCsrf = 'jeton-reset-update';

        $this->withSession(['_token' => $jetonCsrf])
            ->post('/mot-de-passe/reinitialiser', [
                '_token' => $jetonCsrf,
                'token' => $token,
                'courriel' => 'reset-update@example.test',
                'mot_de_passe' => 'NouveauMotdepasse2026!',
                'mot_de_passe_confirmation' => 'NouveauMotdepasse2026!',
            ])->assertRedirect('/');

        self::assertTrue(Hash::check('NouveauMotdepasse2026!', (string) $modele->fresh()->mot_de_passe_hache));
    }

    /**
     * @return array<string, mixed>
     */
    private function creerUtilisateur(string $courriel): array
    {
        return (new UserRepository)->creer([
            'nom' => 'Test',
            'prenom' => 'Reset',
            'date_naissance' => '1991-02-03',
            'courriel' => $courriel,
            'numero_licence' => '',
            'mot_de_passe' => 'Motdepasse2026!',
            'description_profil' => '',
            'pseudo_chess' => '',
        ]);
    }
}
