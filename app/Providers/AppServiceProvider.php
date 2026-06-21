<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : AppServiceProvider.
 */

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        if ((bool) config('trustedproxy.force_https', false)) {
            URL::forceScheme('https');
        }
    }
}
