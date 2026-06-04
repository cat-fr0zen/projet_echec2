<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ChessComService;
use App\Support\LegacyPageRenderer;
use App\Support\SiteContent;
use Illuminate\Http\Response;

final class PageController extends Controller
{
    public function show(?string $page = 'accueil'): Response
    {
        $renderer = new LegacyPageRenderer(
            new SiteContent(),
            new \App\Repositories\UserRepository(),
            new \App\Repositories\ArticleRepository(),
            new \App\Repositories\MediaRepository(),
            new \App\Repositories\OrderRepository(),
            new \App\Repositories\DammierRepository(),
            new \App\Repositories\ScheduleRepository(),
            new \App\Repositories\ConstructeurPagesRepository(),
            new \App\Repositories\TraficVisiteursRepository(),
            new ChessComService(storage_path('app/cache/chesscom')),
            new \App\Services\GoogleReviewsService(
                storage_path('app/cache/google-avis'),
                (string) env('GOOGLE_PLACES_API_KEY', '')
            ),
            recuperer_messages_flash(),
            recuperer_etat_formulaire()
        );

        return response($renderer->afficher($page ?? 'accueil'));
    }
}
