<?php

declare(strict_types=1);

namespace App\Support;

final class NomAffichageUtilisateur
{
    public static function depuisValeurs(
        mixed $prenom,
        mixed $nom,
        mixed $courriel,
        string $fallback = 'Membre'
    ): string {
        $nomComplet = trim((string) $prenom . ' ' . (string) $nom);

        if ($nomComplet !== '') {
            return $nomComplet;
        }

        $courriel = trim((string) $courriel);

        return $courriel !== '' ? $courriel : $fallback;
    }
}
