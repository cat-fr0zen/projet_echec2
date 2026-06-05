<?php

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

    public static function cheminMedia(string $nomFichier): string
    {
        return 'fichiers/medias/'.rawurlencode($nomFichier);
    }

    public static function cheminArticle(string $nomFichier): string
    {
        return 'fichiers/articles/'.rawurlencode($nomFichier);
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

    public static function securiserNomFichier(string $nomFichier): ?string
    {
        $nomNormalise = basename(trim($nomFichier));

        if ($nomNormalise === '' || ! preg_match('/^[a-zA-Z0-9._-]+$/', $nomNormalise)) {
            return null;
        }

        return $nomNormalise;
    }
}
