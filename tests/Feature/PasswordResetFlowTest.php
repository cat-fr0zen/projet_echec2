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
                'identifiant_reinitialisation' => 'reset-request@example.test',
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
                'identifiant_reinitialisation' => 'reset-update@example.test',
                'mot_de_passe' => 'NouveauMotdepasse2026!',
                'mot_de_passe_confirmation' => 'NouveauMotdepasse2026!',
            ])->assertRedirect('/');

        self::assertTrue(Hash::check('NouveauMotdepasse2026!', (string) $modele->fresh()->mot_de_passe_hache));
    }

    public function test_la_demande_de_reinitialisation_par_licence_fonctionne_si_l_email_est_unique(): void
    {
        $this->creerUtilisateur('reset-unique-license@example.test', 'FFE-RESET-02');
        Mail::fake();
        $jetonCsrf = 'jeton-reset-unique-license';

        $this->withSession(['_token' => $jetonCsrf])
            ->post('/mot-de-passe/oublie', [
                '_token' => $jetonCsrf,
                'identifiant_reinitialisation' => 'FFE-RESET-02',
            ])->assertRedirect();

        Mail::assertSent(ResetPasswordLinkMail::class, function (ResetPasswordLinkMail $mail): bool {
            return $mail->hasTo('reset-unique-license@example.test');
        });
    }

    public function test_la_demande_de_reinitialisation_par_email_est_refusee_si_plusieurs_comptes_partagent_cet_email(): void
    {
        $this->creerUtilisateur('reset-ambiguous@example.test', 'FFE-AMB-01');
        $this->creerUtilisateur('reset-ambiguous@example.test', 'FFE-AMB-02');
        Mail::fake();
        $jetonCsrf = 'jeton-reset-shared-email';

        $this->from('/mot-de-passe/oublie')
            ->withSession(['_token' => $jetonCsrf])
            ->post('/mot-de-passe/oublie', [
                '_token' => $jetonCsrf,
                'identifiant_reinitialisation' => 'reset-ambiguous@example.test',
            ])
            ->assertRedirect('/mot-de-passe/oublie')
            ->assertSessionHasErrors([
                'identifiant_reinitialisation' => "La reinitialisation automatique n'est pas disponible pour un email partage. Contactez l'administrateur du club.",
            ]);

        Mail::assertNothingSent();
    }

    public function test_la_demande_de_reinitialisation_par_licence_est_refusee_si_l_email_est_partage(): void
    {
        $this->creerUtilisateur('reset-ambiguous-license@example.test', 'FFE-AMB-LIC-01');
        $this->creerUtilisateur('reset-ambiguous-license@example.test', 'FFE-AMB-LIC-02');
        Mail::fake();
        $jetonCsrf = 'jeton-reset-shared-license';

        $this->from('/mot-de-passe/oublie')
            ->withSession(['_token' => $jetonCsrf])
            ->post('/mot-de-passe/oublie', [
                '_token' => $jetonCsrf,
                'identifiant_reinitialisation' => 'FFE-AMB-LIC-02',
            ])
            ->assertRedirect('/mot-de-passe/oublie')
            ->assertSessionHasErrors([
                'identifiant_reinitialisation' => "La reinitialisation automatique n'est pas disponible pour un email partage. Contactez l'administrateur du club.",
            ]);

        Mail::assertNothingSent();
    }

    /**
     * @return array<string, mixed>
     */
    private function creerUtilisateur(string $courriel, string $numeroLicence = ''): array
    {
        return (new UserRepository)->creer([
            'nom' => 'Test',
            'prenom' => 'Reset',
            'date_naissance' => '1991-02-03',
            'courriel' => $courriel,
            'numero_licence' => $numeroLicence,
            'mot_de_passe' => 'Motdepasse2026!',
            'description_profil' => '',
            'pseudo_chess' => '',
        ]);
    }
}
