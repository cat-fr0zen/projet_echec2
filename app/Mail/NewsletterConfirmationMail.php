<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

final class NewsletterConfirmationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $urlAccueil,
        public string $urlDesabonnement
    ) {}

    public function build(): self
    {
        return $this
            ->subject('Abonnement newsletter confirme')
            ->view('emails.newsletter.confirmation');
    }
}
