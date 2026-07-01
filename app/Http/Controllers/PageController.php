<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : PageController.
 */

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Repositories\BoutiqueProduitRepository;
use App\Repositories\BureauMembreRepository;
use App\Repositories\EvenementRepository;
use App\Repositories\ApiCacheRepository;
use App\Repositories\MediaAlbumRepository;
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

        $apiCache = new ApiCacheRepository;
        $apiUserAgent = (string) config('services.external_api.user_agent', 'cavaliersherouville.fr contact@cavaliersherouville.fr');
        $apiCacheTtl = (int) config('services.external_api.cache_ttl', 3600);

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
            new ChessComService(
                storage_path('app/cache/chesscom'),
                $apiUserAgent,
                $apiCache,
                (string) config('services.external_api.chesscom_base_url', 'https://api.chess.com/pub'),
                $apiCacheTtl
            ),
            new LichessService(
                storage_path('app/cache/lichess'),
                $apiUserAgent,
                $apiCache,
                (string) config('services.external_api.lichess_base_url', 'https://lichess.org/api'),
                $apiCacheTtl
            ),
            new \App\Services\GoogleReviewsService(
                storage_path('app/cache/google-avis'),
                (string) env('GOOGLE_PLACES_API_KEY', '')
            ),
            recuperer_messages_flash(),
            recuperer_etat_formulaire(),
            new BoutiqueCartService,
            new BoutiqueProduitRepository,
            new ParametreSiteRepository,
            new BureauMembreRepository,
            new EvenementRepository(new ParametreSiteRepository),
            new MediaAlbumRepository(new ParametreSiteRepository)
        );

        http_response_code(200);
        $contenu = $renderer->afficher($page ?? 'accueil');
        $statut = http_response_code();

        return response($contenu, is_int($statut) ? $statut : 200);
    }

    public function showArticle(Request $request, string $identifiant): Response|RedirectResponse
    {
        $jetonLegacyDesabonnement = trim((string) $request->query('newsletter_unsubscribe', ''));

        if ($jetonLegacyDesabonnement !== '') {
            return redirect()->route('newsletter.unsubscribe', ['jeton' => $jetonLegacyDesabonnement]);
        }

        $apiCache = new ApiCacheRepository;
        $apiUserAgent = (string) config('services.external_api.user_agent', 'cavaliersherouville.fr contact@cavaliersherouville.fr');
        $apiCacheTtl = (int) config('services.external_api.cache_ttl', 3600);

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
            new ChessComService(
                storage_path('app/cache/chesscom'),
                $apiUserAgent,
                $apiCache,
                (string) config('services.external_api.chesscom_base_url', 'https://api.chess.com/pub'),
                $apiCacheTtl
            ),
            new LichessService(
                storage_path('app/cache/lichess'),
                $apiUserAgent,
                $apiCache,
                (string) config('services.external_api.lichess_base_url', 'https://lichess.org/api'),
                $apiCacheTtl
            ),
            new \App\Services\GoogleReviewsService(
                storage_path('app/cache/google-avis'),
                (string) env('GOOGLE_PLACES_API_KEY', '')
            ),
            recuperer_messages_flash(),
            recuperer_etat_formulaire(),
            new BoutiqueCartService,
            new BoutiqueProduitRepository,
            new ParametreSiteRepository,
            new BureauMembreRepository,
            new EvenementRepository(new ParametreSiteRepository),
            new MediaAlbumRepository(new ParametreSiteRepository)
        );

        http_response_code(200);
        $contenu = $renderer->afficherArticle($identifiant);
        $statut = http_response_code();

        return response($contenu, is_int($statut) ? $statut : 200);
    }
}
