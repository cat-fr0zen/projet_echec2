<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\NewsletterMailerService;
use App\Support\LegacyActionHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ActionController extends Controller
{
    public function handle(Request $request, ?string $page = null): Response
    {
        $newsletterRepository = new \App\Repositories\NewsletterRepository();
        $handler = new LegacyActionHandler(
            new \App\Repositories\UserRepository(),
            new \App\Repositories\ArticleRepository(),
            new \App\Repositories\MediaRepository(),
            new \App\Repositories\OrderRepository(),
            new \App\Repositories\DammierRepository(),
            new \App\Repositories\ScheduleRepository(),
            public_path('assets/media/uploads'),
            $newsletterRepository,
            NewsletterMailerService::depuisEnvironnement($newsletterRepository)
        );

        $handler->traiter();

        return redirect(url_route($page ?? 'accueil'));
    }
}
