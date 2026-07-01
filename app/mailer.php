<?php
/**
 * Aide SMTP / Laravel pour les scripts cron o2switch.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

if (! function_exists('o2switch_boot_laravel')) {
    function o2switch_boot_laravel(): \Illuminate\Foundation\Application
    {
        static $application = null;

        if ($application instanceof \Illuminate\Foundation\Application) {
            return $application;
        }

        $base = o2switch_require_laravel_base_path();

        require_once $base . '/vendor/autoload.php';

        /** @var \Illuminate\Foundation\Application $application */
        $application = require $base . '/bootstrap/app.php';
        $kernel = $application->make(\Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();

        return $application;
    }

    /**
     * @return array<string, mixed>
     */
    function o2switch_mailer_config(): array
    {
        return [
            'host' => o2switch_app_config()['mail_host'] ?? '',
            'port' => o2switch_app_config()['mail_port'] ?? 465,
            'encryption' => o2switch_app_config()['mail_encryption'] ?? 'ssl',
            'username' => o2switch_app_config()['mail_username'] ?? '',
            'from' => o2switch_app_config()['mail_from'] ?? '',
            'from_name' => o2switch_app_config()['mail_from_name'] ?? '',
        ];
    }

    function o2switch_send_laravel_mailable(string $destinataire, \Illuminate\Mail\Mailable $mailable): void
    {
        o2switch_boot_laravel();
        \Illuminate\Support\Facades\Mail::to($destinataire)->send($mailable);
    }
}
