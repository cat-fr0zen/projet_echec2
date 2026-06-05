<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Repositories\ArticleRepository;
use App\Repositories\CoursDocumentRepository;
use App\Repositories\ConstructeurPagesRepository;
use App\Repositories\DammierRepository;
use App\Repositories\MediaRepository;
use App\Repositories\NewsletterRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ScheduleRepository;
use App\Repositories\UserRepository;
use App\Services\NewsletterMailerService;
use App\Support\LegacyActionHandler;
use App\Support\SensitiveActionRateLimiter;
use App\Support\UploadStorage;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

final class ActionController extends Controller
{
    public function handle(Request $request, ?string $page = null): Response
    {
        $this->hydraterSuperglobalesLegacy($request);

        $newsletterRepository = new NewsletterRepository;
        $handler = new LegacyActionHandler(
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
            new SensitiveActionRateLimiter
        );

        $handler->traiter();

        return redirect(url_route($page ?? 'accueil'));
    }

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
