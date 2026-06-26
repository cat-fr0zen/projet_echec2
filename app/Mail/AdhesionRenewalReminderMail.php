<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : AdhesionRenewalReminderMail.
 */

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

final class AdhesionRenewalReminderMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $nomDestinataire,
        public string $saisonCible,
        public string $urlBoutique,
        public string $urlProfil
    ) {}

    public function build(): self
    {
        return $this
            ->subject("Renouvellement adhesion {$this->saisonCible}")
            ->view('emails.adhesion.renewal-reminder');
    }
}
