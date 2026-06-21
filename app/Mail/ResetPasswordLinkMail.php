<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : ResetPasswordLinkMail.
 */

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

final class ResetPasswordLinkMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $urlReinitialisation,
        public string $prenomDestinataire
    ) {}

    public function build(): self
    {
        return $this
            ->subject('Réinitialisation de votre mot de passe')
            ->view('emails.auth.reset-password-link');
    }
}
