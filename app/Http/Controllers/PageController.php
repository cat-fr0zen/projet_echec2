<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : PageController.
 */

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Repositories\BoutiqueProduitRepository;
use App\Repositories\BureauMembreRepository;
use App\Repositories\NewsletterRepository;
use App\Repositories\ParametreSiteRepository;
use App\Services\ChessComService;
use App\Services\LichessService;
use App\Support\BoutiqueCartService;
use App\Support\SitePageRenderer;
use App\Support\SiteContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class PageController extends Controller
{
    /**
     * Ouvre une page du site puis delegue la preparation des donnees.
     */
    public function show(Request $request, ?string $page = 'accueil'): Response|RedirectResponse
    {
        $jetonLegacyDesabonnement = trim((string) $request->query('newsletter_unsubscribe', ''));

        if ($jetonLegacyDesabonnement !== '') {
            return redirect()->route('newsletter.unsubscribe', ['jeton' => $jetonLegacyDesabonnement]);
        }

        $renderer = new SitePageRenderer(
            new SiteContent(),
            new \App\Repositories\UserRepository(),
            new \App\Repositories\ArticleRepository(),
            new \App\Repositories\CoursDocumentRepository(),
            new \App\Repositories\MediaRepository(),
            new \App\Repositories\OrderRepository(),
            new \App\Repositories\DammierRepository(),
            new \App\Repositories\ScheduleRepository(),
            new \App\Repositories\ConstructeurPagesRepository(),
            new NewsletterRepository(),
            new \App\Repositories\TraficVisiteursRepository(),
            new ChessComService(storage_path('app/cache/chesscom')),
            new LichessService(storage_path('app/cache/lichess')),
            new \App\Services\GoogleReviewsService(
                storage_path('app/cache/google-avis'),
                (string) env('GOOGLE_PLACES_API_KEY', '')
            ),
            recuperer_messages_flash(),
            recuperer_etat_formulaire(),
            new BoutiqueCartService,
            new BoutiqueProduitRepository,
            new ParametreSiteRepository,
            new BureauMembreRepository
        );

        return response($renderer->afficher($page ?? 'accueil'));
    }
}
