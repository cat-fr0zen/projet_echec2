<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\MediaPublication;
use App\Models\User;
use App\Repositories\ArticleRepository;
use App\Repositories\MediaRepository;
use App\Repositories\UserRepository;
use App\Support\UploadStorage;
use Illuminate\Contracts\Routing\ResponseFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class MediaAssetController extends Controller
{
    public function showPublication(
        string $nomFichier,
        MediaRepository $mediaRepository,
        UserRepository $userRepository,
        ResponseFactory $responseFactory
    ): BinaryFileResponse {
        $nomSecurise = UploadStorage::securiserNomFichier($nomFichier);

        abort_if($nomSecurise === null, 404);

        $media = $mediaRepository->trouverParNomFichierStocke($nomSecurise);
        abort_if($media === null, 404);

        $utilisateur = $this->recupererUtilisateurCourant($userRepository);
        $estPublie = ($media['statut'] ?? '') === MediaPublication::STATUT_PUBLIE;
        $estAuteur = $utilisateur !== null && (string) ($media['identifiant_auteur'] ?? '') === (string) ($utilisateur['identifiant'] ?? '');
        $estAdmin = $utilisateur !== null && (string) ($utilisateur['role'] ?? '') === User::ROLE_ADMIN;

        abort_unless($estPublie || $estAuteur || $estAdmin, 404);

        $cheminFichier = UploadStorage::resoudreCheminMedia($nomSecurise);
        abort_if($cheminFichier === null, 404);

        return $this->repondreFichier($responseFactory, $cheminFichier, (string) ($media['type_mime'] ?? 'application/octet-stream'));
    }

    public function showArticle(
        string $nomFichier,
        ArticleRepository $articleRepository,
        UserRepository $userRepository,
        ResponseFactory $responseFactory
    ): BinaryFileResponse {
        $nomSecurise = UploadStorage::securiserNomFichier($nomFichier);

        abort_if($nomSecurise === null, 404);

        $bloc = $articleRepository->trouverBlocMediaParNomFichierStocke($nomSecurise);
        abort_if($bloc === null, 404);

        $utilisateur = $this->recupererUtilisateurCourant($userRepository);
        $estPublie = ($bloc['statut_article'] ?? '') === Article::STATUT_PUBLIE;
        $estAuteur = $utilisateur !== null && (string) ($bloc['identifiant_auteur'] ?? '') === (string) ($utilisateur['identifiant'] ?? '');
        $estAdmin = $utilisateur !== null && (string) ($utilisateur['role'] ?? '') === User::ROLE_ADMIN;

        abort_unless($estPublie || $estAuteur || $estAdmin, 404);

        $cheminFichier = UploadStorage::resoudreCheminArticle($nomSecurise);
        abort_if($cheminFichier === null, 404);

        return $this->repondreFichier($responseFactory, $cheminFichier, (string) ($bloc['type_mime'] ?? 'application/octet-stream'));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function recupererUtilisateurCourant(UserRepository $userRepository): ?array
    {
        return $userRepository->trouverParIdentifiant(identifiant_utilisateur_courant());
    }

    private function repondreFichier(ResponseFactory $responseFactory, string $cheminFichier, string $typeMime): BinaryFileResponse
    {
        $reponse = $responseFactory->file($cheminFichier, [
            'Content-Type' => $typeMime,
            'Content-Disposition' => 'inline; filename="'.basename($cheminFichier).'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'public, max-age=3600',
        ]);

        return $reponse;
    }
}
