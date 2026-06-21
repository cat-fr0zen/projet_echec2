<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : CoursDocumentController.
 */

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Repositories\CoursDocumentRepository;
use App\Repositories\UserRepository;
use App\Support\UploadStorage;
use Illuminate\Contracts\Routing\ResponseFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class CoursDocumentController extends Controller
{
    /**
     * Telecharge un PDF de cours seulement si la personne a le bon role.
     */
    public function show(
        string $nomFichier,
        CoursDocumentRepository $coursDocumentRepository,
        UserRepository $userRepository,
        ResponseFactory $responseFactory
    ): BinaryFileResponse {
        $nomSecurise = UploadStorage::securiserNomFichier($nomFichier);

        abort_if($nomSecurise === null, 404);

        $document = $coursDocumentRepository->trouverParNomFichierStocke($nomSecurise);
        abort_if($document === null, 404);

        $utilisateur = $userRepository->trouverParIdentifiant(identifiant_utilisateur_courant());
        $estAutorise = $utilisateur !== null
            && ($utilisateur['statut_compte'] ?? '') === User::STATUT_COMPTE_ACTIF
            && in_array((string) ($utilisateur['role'] ?? ''), [User::ROLE_ADMIN, User::ROLE_PROF], true);

        abort_unless($estAutorise, 404);

        $cheminFichier = UploadStorage::resoudreCheminCours($nomSecurise);

        if ($cheminFichier === null) {
            $cheminFichier = UploadStorage::resoudreCheminCoursInterne(
                (string) ($document['chemin_source_interne'] ?? '')
            );
        }

        abort_if($cheminFichier === null, 404);

        return $responseFactory->download(
            $cheminFichier,
            (string) ($document['nom_fichier_original'] ?? basename($cheminFichier)),
            [
                'Content-Type' => (string) ($document['type_mime'] ?? 'application/pdf'),
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, max-age=0, must-revalidate',
            ]
        );
    }
}
