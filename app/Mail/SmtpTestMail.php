<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : SmtpTestMail.
 */

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

final class SmtpTestMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $provider,
        public string $host,
        public int $port
    ) {}

    public function build(): self
    {
        return $this
            ->subject('Test email Cavaliers d\'Herouville')
            ->view('emails.test.smtp');
    }
}
