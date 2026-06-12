<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CoursDocumentRepository;
use App\Repositories\UserRepository;
use App\Support\UploadStorage;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class CoursPdfImportService
{
    public function __construct(
        private readonly CoursDocumentRepository $coursDocumentRepository,
        private readonly UserRepository $userRepository
    ) {
    }

    /**
     * @return array{
     *     ajoutes:int,
     *     mis_a_jour:int,
     *     ignores:int,
     *     erreurs:array<int, string>
     * }
     */
    public function importer(?string $dossierSource = null, ?string $identifiantAuteur = null): array
    {
        $dossierSource = $this->normaliserCheminDossier($dossierSource ?: UploadStorage::dossierCours());
        $dossierDestination = $this->normaliserCheminDossier(UploadStorage::dossierCours());
        $sourceEtDestinationIdentiques = $this->cheminsSontIdentiques($dossierSource, $dossierDestination);

        $resultat = [
            'ajoutes' => 0,
            'mis_a_jour' => 0,
            'ignores' => 0,
            'erreurs' => [],
        ];

        if (! is_dir($dossierSource)) {
            $resultat['erreurs'][] = 'Le dossier source des PDF de cours est introuvable.';

            return $resultat;
        }

        if (! is_dir($dossierDestination) && ! mkdir($dossierDestination, 0775, true) && ! is_dir($dossierDestination)) {
            $resultat['erreurs'][] = 'Le dossier destination des PDF de cours est indisponible.';

            return $resultat;
        }

        $auteur = $this->resoudreAuteurImport($identifiantAuteur);

        if ($auteur === null) {
            $resultat['erreurs'][] = "Aucun auteur valide n'a ete trouve pour l'import.";

            return $resultat;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dossierSource, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $fichier */
        foreach ($iterator as $fichier) {
            if (! $fichier->isFile() || mb_strtolower($fichier->getExtension()) !== 'pdf') {
                continue;
            }

            $document = $this->preparerDocumentImporte($fichier, $dossierSource);

            if ($document === null) {
                $resultat['ignores']++;

                continue;
            }

            $documentExistant = $this->coursDocumentRepository->trouverParCheminSourceInterne(
                (string) $document['chemin_source_interne']
            );

            $nomFichierStocke = $this->resoudreNomFichierStocke($documentExistant);

            if (! $sourceEtDestinationIdentiques) {
                $cheminDestination = $dossierDestination.DIRECTORY_SEPARATOR.$nomFichierStocke;

                if (! @copy($fichier->getPathname(), $cheminDestination)) {
                    $resultat['erreurs'][] = 'Copie impossible pour '.$document['nom_fichier_original'].'.';

                    continue;
                }
            }

            $donnees = array_merge($document, [
                'nom_fichier_stocke' => $nomFichierStocke,
                'chemin_fichier' => UploadStorage::cheminCours($nomFichierStocke),
                'identifiant_auteur' => (string) $auteur['identifiant'],
            ]);

            if ($documentExistant === null) {
                $this->coursDocumentRepository->creer($donnees);
                $resultat['ajoutes']++;

                continue;
            }

            $this->coursDocumentRepository->mettreAJour(
                (string) $documentExistant['identifiant_document'],
                $donnees
            );
            $resultat['mis_a_jour']++;
        }

        return $resultat;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resoudreAuteurImport(?string $identifiantAuteur): ?array
    {
        $identifiantAuteur = trim((string) $identifiantAuteur);

        if ($identifiantAuteur !== '') {
            return $this->userRepository->trouverParIdentifiant($identifiantAuteur);
        }

        foreach ($this->userRepository->listerTous() as $utilisateur) {
            if ((string) ($utilisateur['role'] ?? '') === 'admin' && (string) ($utilisateur['statut_compte'] ?? '') === 'actif') {
                return $utilisateur;
            }
        }

        foreach ($this->userRepository->listerTous() as $utilisateur) {
            if ((string) ($utilisateur['statut_compte'] ?? '') === 'actif') {
                return $utilisateur;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function preparerDocumentImporte(SplFileInfo $fichier, string $dossierSource): ?array
    {
        $cheminSource = $this->normaliserSeparateurs(
            substr($fichier->getPathname(), strlen($dossierSource) + 1) ?: ''
        );

        if ($cheminSource === '') {
            return null;
        }

        $segments = array_values(array_filter(explode('/', $cheminSource), static fn (string $segment): bool => $segment !== ''));

        if (count($segments) < 2) {
            return null;
        }

        $nomFichier = (string) array_pop($segments);
        $dossierRacine = $this->normaliserCleDossier($segments[0] ?? '');
        $codeRubrique = match ($dossierRacine) {
            'cours' => 'cours',
            'livret' => 'livrets',
            'strategie' => 'strategie',
            default => null,
        };

        if ($codeRubrique === null) {
            return null;
        }

        $dossiersIntermediaires = array_slice($segments, 1);
        $tailleOctets = $fichier->getSize();
        $typeMime = @mime_content_type($fichier->getPathname()) ?: 'application/pdf';

        return [
            'code_rubrique' => $codeRubrique,
            'titre_document' => $this->extraireTitreDocument($nomFichier),
            'description_document' => null,
            'nom_fichier_original' => $nomFichier,
            'type_mime' => is_string($typeMime) && $typeMime !== '' ? $typeMime : 'application/pdf',
            'taille_octets' => is_int($tailleOctets) ? $tailleOctets : 0,
            'groupe_document' => $dossiersIntermediaires[0] ?? null,
            'sous_groupe_document' => count($dossiersIntermediaires) > 1
                ? implode(' / ', array_slice($dossiersIntermediaires, 1))
                : null,
            'chemin_source_interne' => $cheminSource,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $documentExistant
     */
    private function resoudreNomFichierStocke(?array $documentExistant): string
    {
        $nomExistant = UploadStorage::securiserNomFichier((string) ($documentExistant['nom_fichier_stocke'] ?? ''));

        if ($nomExistant !== null) {
            return $nomExistant;
        }

        do {
            $nomGenere = 'cours_import_'.bin2hex(random_bytes(12)).'.pdf';
            $cheminGenere = UploadStorage::dossierCours().DIRECTORY_SEPARATOR.$nomGenere;
        } while (is_file($cheminGenere));

        return $nomGenere;
    }

    private function extraireTitreDocument(string $nomFichier): string
    {
        $titre = trim(pathinfo($nomFichier, PATHINFO_FILENAME));

        return $titre !== '' ? $titre : 'Document PDF';
    }

    private function normaliserCleDossier(string $nomDossier): string
    {
        $cle = Str::ascii(mb_strtolower(trim($nomDossier)));
        $cle = preg_replace('/[^a-z0-9]+/', '', $cle) ?? '';

        return $cle;
    }

    private function normaliserCheminDossier(string $chemin): string
    {
        return rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $chemin), DIRECTORY_SEPARATOR);
    }

    private function normaliserSeparateurs(string $chemin): string
    {
        return trim(str_replace('\\', '/', $chemin), '/');
    }

    private function cheminsSontIdentiques(string $cheminA, string $cheminB): bool
    {
        $cheminAReel = realpath($cheminA);
        $cheminBReel = realpath($cheminB);

        if ($cheminAReel === false || $cheminBReel === false) {
            return false;
        }

        return strtolower(str_replace('\\', '/', $cheminAReel))
            === strtolower(str_replace('\\', '/', $cheminBReel));
    }
}
