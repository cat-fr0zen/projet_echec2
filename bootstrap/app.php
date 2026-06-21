<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : app.
 */

use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SynchronizeLegacyAuthentication;
use App\Http\Middleware\TrustProxies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\TrustProxies as LaravelTrustProxies;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up'
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->replace(LaravelTrustProxies::class, TrustProxies::class);
        $middleware->web(append: [
            SynchronizeLegacyAuthentication::class,
        ]);
        $middleware->append(SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Le port local garde la gestion d'exceptions Laravel par defaut.
    })
    ->create();
