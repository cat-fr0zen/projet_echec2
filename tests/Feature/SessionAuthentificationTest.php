<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Repositories\ArticleRepository;
use App\Repositories\CoursDocumentRepository;
use App\Repositories\ConstructeurPagesRepository;
use App\Repositories\DammierRepository;
use App\Repositories\MediaRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ScheduleRepository;
use App\Repositories\UserRepository;
use App\Support\LegacyActionHandler;
use ReflectionMethod;
use Tests\TestCase;

final class SessionAuthentificationTest extends TestCase
{
    public function test_la_connexion_peut_regenerer_la_session_meme_si_aucune_session_native_n_est_active(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $handler = $this->creerHandler();
        $methode = new ReflectionMethod($handler, 'ouvrirSessionAuthentification');
        $methode->setAccessible(true);

        $methode->invoke($handler, 'utilisateur_test');

        self::assertSame('utilisateur_test', session('identifiant_utilisateur'));
    }

    public function test_la_deconnexion_peut_nettoyer_la_session_sans_session_native_active(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        session(['identifiant_utilisateur' => 'utilisateur_test']);

        $handler = $this->creerHandler();
        $methode = new ReflectionMethod($handler, 'fermerSessionAuthentification');
        $methode->setAccessible(true);

        $methode->invoke($handler);

        self::assertNull(session('identifiant_utilisateur'));
    }

    private function creerHandler(): LegacyActionHandler
    {
        return new LegacyActionHandler(
            new UserRepository,
            new ArticleRepository,
            new CoursDocumentRepository,
            new MediaRepository,
            new OrderRepository,
            new DammierRepository,
            new ScheduleRepository,
            new ConstructeurPagesRepository,
            public_path('assets/media/uploads')
        );
    }
}
