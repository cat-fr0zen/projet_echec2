<?php

declare(strict_types=1);

final class StockageJson
{
    public function __construct(private string $cheminFichier)
    {
        $dossier = dirname($this->cheminFichier);

        if (!is_dir($dossier)) {
            mkdir($dossier, 0777, true);
        }

        if (!file_exists($this->cheminFichier)) {
            file_put_contents($this->cheminFichier, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }

    public function lire(): array
    {
        $contenu = file_get_contents($this->cheminFichier);

        if ($contenu === false || trim($contenu) === '') {
            return [];
        }

        $donnees = json_decode($contenu, true);

        return is_array($donnees) ? $donnees : [];
    }

    public function ecrire(array $enregistrements): void
    {
        file_put_contents(
            $this->cheminFichier,
            json_encode(array_values($enregistrements), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            LOCK_EX
        );
    }
}
