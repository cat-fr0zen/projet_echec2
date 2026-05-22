<?php

declare(strict_types=1);

/**
 * Stockage JSON "bas niveau".
 *
 * But:
 * - fournir un mini mecanisme de persistance pour le prototype (sans BDD)
 * - garantir l'existence du dossier et du fichier JSON
 * - lire/ecrire des tableaux PHP de maniere atomique (LOCK_EX)
 *
 * Utilise par:
 * - DepotUtilisateurs / DepotArticles / DepotMedias / DepotCommandes
 */
final class StockageJson
{
    /**
     * @param string $cheminFichier Chemin complet vers le fichier JSON.
     */
    public function __construct(private string $cheminFichier)
    {
        $dossier = dirname($this->cheminFichier);

        if (!is_dir($dossier)) {
            mkdir($dossier, 0755, true);
        }

        if (!file_exists($this->cheminFichier)) {
            file_put_contents($this->cheminFichier, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * Lit le JSON et retourne un tableau.
     *
     * @return array Liste d'enregistrements (ou [] si vide/invalide).
     */
    public function lire(): array
    {
        $contenu = file_get_contents($this->cheminFichier);

        if ($contenu === false || trim($contenu) === '') {
            return [];
        }

        $donnees = json_decode($contenu, true);

        return is_array($donnees) ? $donnees : [];
    }

    /**
     * Ecrit un tableau d'enregistrements en JSON.
     *
     * @param array $enregistrements Donnees a persister.
     */
    public function ecrire(array $enregistrements): void
    {
        file_put_contents(
            $this->cheminFichier,
            json_encode(array_values($enregistrements), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            LOCK_EX
        );
    }
}
