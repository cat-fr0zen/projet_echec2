<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : NewsletterActualiteMail.
 */

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

final class NewsletterActualiteMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $sujetEmail,
        public string $titreEvenement,
        public string $messageIntro,
        public string $urlEvenement,
        public string $urlDesabonnement
    ) {}

    public function build(): self
    {
        return $this
            ->subject($this->sujetEmail)
            ->view('emails.newsletter.actualite');
    }
}
