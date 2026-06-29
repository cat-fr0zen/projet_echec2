<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : ParametreSiteRepository.
 */

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class ParametreSiteRepository
{
    public const CLE_LIEN_BOUTIQUE_HELLOASSO = 'lien_boutique_helloasso';

    public const CLE_BUREAU_SURTITRE = 'bureau_section_surtitre';

    public const CLE_BUREAU_TITRE = 'bureau_section_titre';

    public const CLE_BUREAU_DESCRIPTION = 'bureau_section_description';

    public const LIEN_HELLOASSO_PAR_DEFAUT = 'https://www.helloasso.com/associations/les-cavaliers-d-herouville';

    public function obtenirLienBoutiqueHelloAsso(): string
    {
        $lien = $this->obtenirTexte(self::CLE_LIEN_BOUTIQUE_HELLOASSO, self::LIEN_HELLOASSO_PAR_DEFAUT);

        return $lien !== '' ? $lien : self::LIEN_HELLOASSO_PAR_DEFAUT;
    }

    public function mettreAJourLienBoutiqueHelloAsso(string $lien): string
    {
        $lienNormalise = trim($lien);

        if ($lienNormalise === '' || ! $this->tableDisponible()) {
            return self::LIEN_HELLOASSO_PAR_DEFAUT;
        }

        $instant = now()->format('Y-m-d H:i:s');
        $existeDeja = DB::table('parametre_site')
            ->where('cle_parametre', self::CLE_LIEN_BOUTIQUE_HELLOASSO)
            ->exists();

        if ($existeDeja) {
            DB::table('parametre_site')
                ->where('cle_parametre', self::CLE_LIEN_BOUTIQUE_HELLOASSO)
                ->update([
                    'valeur_texte' => $lienNormalise,
                    'mis_a_jour_le' => $instant,
                ]);
        } else {
            DB::table('parametre_site')->insert([
                'cle_parametre' => self::CLE_LIEN_BOUTIQUE_HELLOASSO,
                'valeur_texte' => $lienNormalise,
                'cree_le' => $instant,
                'mis_a_jour_le' => $instant,
            ]);
        }

        return $lienNormalise;
    }

    public function obtenirTexte(string $cleParametre, string $valeurParDefaut = ''): string
    {
        if ($cleParametre === '' || ! $this->tableDisponible()) {
            return $valeurParDefaut;
        }

        try {
            $valeur = DB::table('parametre_site')
                ->where('cle_parametre', $cleParametre)
                ->value('valeur_texte');
        } catch (Throwable) {
            return $valeurParDefaut;
        }

        $texte = trim((string) $valeur);

        return $texte !== '' ? $texte : $valeurParDefaut;
    }

    public function mettreAJourTexte(string $cleParametre, string $valeur): string
    {
        $cleNormalisee = trim($cleParametre);

        if ($cleNormalisee === '' || ! $this->tableDisponible()) {
            return '';
        }

        $valeurNormalisee = trim($valeur);
        $instant = now()->format('Y-m-d H:i:s');
        $existeDeja = DB::table('parametre_site')
            ->where('cle_parametre', $cleNormalisee)
            ->exists();

        if ($existeDeja) {
            DB::table('parametre_site')
                ->where('cle_parametre', $cleNormalisee)
                ->update([
                    'valeur_texte' => $valeurNormalisee,
                    'mis_a_jour_le' => $instant,
                ]);
        } else {
            DB::table('parametre_site')->insert([
                'cle_parametre' => $cleNormalisee,
                'valeur_texte' => $valeurNormalisee,
                'cree_le' => $instant,
                'mis_a_jour_le' => $instant,
            ]);
        }

        return $valeurNormalisee;
    }

    private function tableDisponible(): bool
    {
        try {
            return Schema::hasTable('parametre_site');
        } catch (Throwable) {
            return false;
        }
    }
}
