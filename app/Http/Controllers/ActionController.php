<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : ActionController.
 */

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Repositories\ArticleRepository;
use App\Repositories\BoutiqueProduitRepository;
use App\Repositories\BureauMembreRepository;
use App\Repositories\CoursDocumentRepository;
use App\Repositories\ConstructeurPagesRepository;
use App\Repositories\DammierRepository;
use App\Repositories\MediaRepository;
use App\Repositories\NewsletterRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ParametreSiteRepository;
use App\Repositories\ScheduleRepository;
use App\Repositories\UserRepository;
use App\Services\AdhesionRenewalService;
use App\Services\NewsletterMailerService;
use App\Support\BoutiqueCartService;
use App\Support\SensitiveActionRateLimiter;
use App\Support\SiteActionHandler;
use App\Support\UploadStorage;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

final class ActionController extends Controller
{
    /**
     * Point d'entree unique des formulaires du site.
     */
    public function handle(Request $request, ?string $page = null): Response
    {
        $this->hydraterSuperglobalesLegacy($request);

        $newsletterRepository = new NewsletterRepository;
        $handler = new SiteActionHandler(
            new UserRepository,
            new ArticleRepository,
            new CoursDocumentRepository,
            new MediaRepository,
            new OrderRepository,
            new DammierRepository,
            new ScheduleRepository,
            new ConstructeurPagesRepository,
            UploadStorage::dossierMedias(),
            $newsletterRepository,
            NewsletterMailerService::depuisEnvironnement($newsletterRepository),
            new AdhesionRenewalService(new UserRepository),
            new SensitiveActionRateLimiter,
            new BoutiqueCartService,
            new BoutiqueProduitRepository,
            new ParametreSiteRepository,
            new BureauMembreRepository
        );

        $handler->traiter();

        return redirect(url_route($page ?? 'accueil'));
    }

    /**
     * Recompose le format attendu par la logique historique du projet.
     */
    private function hydraterSuperglobalesLegacy(Request $request): void
    {
        $_SERVER['REQUEST_METHOD'] = $request->getMethod();
        $_POST = $request->request->all();
        $_FILES = $this->convertirFichiersPourLegacy($request->allFiles());
    }

    /**
     * @param  array<string, mixed>  $fichiers
     * @return array<string, array{name: string, type: string, tmp_name: string, error: int, size: int}>
     */
    private function convertirFichiersPourLegacy(array $fichiers): array
    {
        $resultat = [];

        foreach ($fichiers as $cle => $fichier) {
            if (! $fichier instanceof UploadedFile) {
                continue;
            }

            $cheminTemporaire = $fichier->getRealPath();

            if (! is_string($cheminTemporaire) || $cheminTemporaire === '') {
                $cheminTemporaire = $fichier->getPathname();
            }

            $resultat[$cle] = [
                'name' => (string) $fichier->getClientOriginalName(),
                'type' => (string) ($fichier->getClientMimeType() ?? ''),
                'tmp_name' => $cheminTemporaire,
                'error' => (int) $fichier->getError(),
                'size' => (int) $fichier->getSize(),
            ];
        }

        return $resultat;
    }
}
