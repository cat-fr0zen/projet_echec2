<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : console.
 */

use App\Mail\SmtpTestMail;
use App\Services\CoursPdfImportService;
use App\Support\MailProviderConfig;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

Artisan::command('app:about-local', function (): void {
    $this->info('Port Laravel local du site Cavaliers d\'Herouville.');
})->purpose('Affiche un resume rapide du port Laravel local');

Artisan::command('mail:config-check', function (): void {
    $provider = MailProviderConfig::resolve(
        env('MAIL_PROVIDER', 'custom'),
        env('MAIL_HOST', ''),
        env('MAIL_PORT', 1025),
        env('MAIL_ENCRYPTION')
    );

    $this->info('Configuration mail resolue :');
    $this->line('MAIL_MAILER: '.config('mail.default'));
    $this->line('MAIL_PROVIDER: '.$provider['provider']);
    $this->line('MAIL_HOST: '.$provider['host']);
    $this->line('MAIL_PORT: '.$provider['port']);
    $this->line('MAIL_ENCRYPTION: '.($provider['encryption'] ?? 'aucune'));
    $this->line('MAIL_FROM_ADDRESS: '.(string) config('mail.from.address'));
    $this->line('MAIL_FROM_NAME: '.(string) config('mail.from.name'));

    if (config('mail.default') !== 'smtp') {
        $this->warn('Le mailer actif n est pas SMTP. Les emails reels ne partiront pas avec cette configuration.');
    }

    if ((string) env('MAIL_USERNAME', '') === '' || in_array((string) env('MAIL_PASSWORD', ''), ['', 'null'], true)) {
        $this->warn('Identifiants SMTP manquants ou incomplets.');
    }

    if ((string) env('MAIL_PROVIDER', 'custom') === 'ovh') {
        $this->warn('OVH peut varier selon le produit (MX Plan, Email Pro, etc.). Surcharge MAIL_HOST si besoin.');
    }
})->purpose('Affiche la configuration SMTP resolue du projet');

Artisan::command('mail:test-envoi {destinataire}', function (string $destinataire): void {
    if (! filter_var($destinataire, FILTER_VALIDATE_EMAIL)) {
        $this->error('Adresse email destinataire invalide.');

        return;
    }

    $provider = MailProviderConfig::resolve(
        env('MAIL_PROVIDER', 'custom'),
        env('MAIL_HOST', ''),
        env('MAIL_PORT', 1025),
        env('MAIL_ENCRYPTION')
    );

    Mail::to($destinataire)->send(new SmtpTestMail(
        $provider['provider'],
        $provider['host'],
        $provider['port']
    ));

    $this->info('Email de test envoye vers '.$destinataire.'.');
})->purpose('Envoie un email de test avec la configuration SMTP courante');

Artisan::command('cours:importer-pdf {--source=} {--auteur=}', function (): int {
    $resultat = app(CoursPdfImportService::class)->importer(
        $this->option('source'),
        $this->option('auteur')
    );

    foreach ($resultat['erreurs'] as $erreur) {
        $this->error($erreur);
    }

    $this->info(sprintf(
        'Import termine : %d ajoutes, %d mis a jour, %d ignores.',
        (int) $resultat['ajoutes'],
        (int) $resultat['mis_a_jour'],
        (int) $resultat['ignores']
    ));

    return $resultat['erreurs'] === [] ? 0 : 1;
})->purpose('Importe les PDF de cours ranges dans des dossiers vers la base locale');
