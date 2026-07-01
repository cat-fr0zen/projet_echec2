<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : UploadStorage.
 */

declare(strict_types=1);

namespace App\Support;

final class UploadStorage
{
    public static function dossierRacine(): string
    {
        return storage_path('app/private/uploads');
    }

    public static function dossierMedias(): string
    {
        return self::dossierRacine().DIRECTORY_SEPARATOR.'medias';
    }

    public static function dossierArticles(): string
    {
        return self::dossierRacine().DIRECTORY_SEPARATOR.'articles';
    }

    public static function dossierCours(): string
    {
        $dossierPersonnalise = trim((string) env('PDF_STORAGE_PATH', env('COURSE_UPLOADS_PATH', '')));

        if ($dossierPersonnalise !== '') {
            return $dossierPersonnalise;
        }

        return self::dossierRacine().DIRECTORY_SEPARATOR.'cours';
    }

    public static function cheminMedia(string $nomFichier): string
    {
        return 'fichiers/medias/'.rawurlencode($nomFichier);
    }

    public static function cheminArticle(string $nomFichier): string
    {
        return 'fichiers/articles/'.rawurlencode($nomFichier);
    }

    public static function cheminCours(string $nomFichier): string
    {
        return 'fichiers/cours/'.rawurlencode($nomFichier);
    }

    public static function resoudreCheminMedia(string $nomFichier): ?string
    {
        $nomSecurise = self::securiserNomFichier($nomFichier);

        if ($nomSecurise === null) {
            return null;
        }

        foreach ([
            self::dossierMedias().DIRECTORY_SEPARATOR.$nomSecurise,
            self::dossierRacine().DIRECTORY_SEPARATOR.$nomSecurise,
        ] as $cheminPrive) {
            if (is_file($cheminPrive)) {
                return $cheminPrive;
            }
        }

        $cheminLegacy = public_path('assets/media/uploads/'.$nomSecurise);

        return is_file($cheminLegacy) ? $cheminLegacy : null;
    }

    public static function resoudreCheminArticle(string $nomFichier): ?string
    {
        $nomSecurise = self::securiserNomFichier($nomFichier);

        if ($nomSecurise === null) {
            return null;
        }

        $cheminPrive = self::dossierArticles().DIRECTORY_SEPARATOR.$nomSecurise;

        if (is_file($cheminPrive)) {
            return $cheminPrive;
        }

        $cheminLegacy = public_path('assets/media/uploads/articles/'.$nomSecurise);

        return is_file($cheminLegacy) ? $cheminLegacy : null;
    }

    public static function resoudreCheminCours(string $nomFichier): ?string
    {
        $nomSecurise = self::securiserNomFichier($nomFichier);

        if ($nomSecurise === null) {
            return null;
        }

        $cheminPrive = self::dossierCours().DIRECTORY_SEPARATOR.$nomSecurise;

        return is_file($cheminPrive) ? $cheminPrive : null;
    }

    public static function resoudreCheminCoursInterne(string $cheminInterne): ?string
    {
        $cheminInterne = trim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $cheminInterne));

        if ($cheminInterne === '' || str_contains($cheminInterne, '..')) {
            return null;
        }

        $dossierCours = realpath(self::dossierCours());

        if ($dossierCours === false) {
            return null;
        }

        $cheminComplet = $dossierCours.DIRECTORY_SEPARATOR.$cheminInterne;

        if (! is_file($cheminComplet)) {
            return null;
        }

        $cheminReel = realpath($cheminComplet);

        if ($cheminReel === false) {
            return null;
        }

        $racineNormalisee = strtolower(str_replace('\\', '/', rtrim($dossierCours, DIRECTORY_SEPARATOR)));
        $cheminNormalise = strtolower(str_replace('\\', '/', $cheminReel));

        if (
            $cheminNormalise !== $racineNormalisee
            && ! str_starts_with($cheminNormalise, $racineNormalisee.'/')
        ) {
            return null;
        }

        return $cheminReel;
    }

    public static function supprimerCheminMedia(string $nomFichier): void
    {
        $nomSecurise = self::securiserNomFichier($nomFichier);

        if ($nomSecurise === null) {
            return;
        }

        foreach ([
            self::dossierMedias().DIRECTORY_SEPARATOR.$nomSecurise,
            self::dossierRacine().DIRECTORY_SEPARATOR.$nomSecurise,
            public_path('assets/media/uploads/'.$nomSecurise),
        ] as $chemin) {
            if (is_file($chemin)) {
                unlink($chemin);
            }
        }
    }

    public static function supprimerCheminArticle(string $nomFichier): void
    {
        $nomSecurise = self::securiserNomFichier($nomFichier);

        if ($nomSecurise === null) {
            return;
        }

        foreach ([
            self::dossierArticles().DIRECTORY_SEPARATOR.$nomSecurise,
            public_path('assets/media/uploads/articles/'.$nomSecurise),
        ] as $chemin) {
            if (is_file($chemin)) {
                unlink($chemin);
            }
        }
    }

    public static function supprimerCheminCours(string $nomFichier): void
    {
        $nomSecurise = self::securiserNomFichier($nomFichier);

        if ($nomSecurise === null) {
            return;
        }

        $chemin = self::dossierCours().DIRECTORY_SEPARATOR.$nomSecurise;

        if (is_file($chemin)) {
            unlink($chemin);
        }
    }

    public static function securiserNomFichier(string $nomFichier): ?string
    {
        $nomNormalise = basename(trim($nomFichier));

        if ($nomNormalise === '' || ! preg_match('/^[a-zA-Z0-9._-]+$/', $nomNormalise)) {
            return null;
        }

        return $nomNormalise;
    }
}
