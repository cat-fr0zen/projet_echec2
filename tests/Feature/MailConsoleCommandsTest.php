<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\SmtpTestMail;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

final class MailConsoleCommandsTest extends TestCase
{
    public function test_la_commande_de_verification_mail_affiche_la_configuration_resolue(): void
    {
        $this->artisan('mail:config-check')
            ->expectsOutputToContain('Configuration mail resolue')
            ->expectsOutputToContain('MAIL_MAILER:')
            ->expectsOutputToContain('MAIL_PROVIDER:')
            ->assertExitCode(0);
    }

    public function test_la_commande_de_test_mail_declenche_un_envoi(): void
    {
        Mail::fake();

        $this->artisan('mail:test-envoi', [
            'destinataire' => 'destinataire@example.test',
        ])->expectsOutputToContain('Email de test envoye')
            ->assertExitCode(0);

        Mail::assertSent(SmtpTestMail::class, function (SmtpTestMail $mail): bool {
            return $mail->hasTo('destinataire@example.test');
        });
    }
}
