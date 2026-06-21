<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : BoutiqueProduitRepository.
 */

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class BoutiqueProduitRepository
{
    /**
     * Centralise les produits affiches dans la boutique du club.
     */
    /**
     * @var array<string, string>
     */
    public const CATEGORIES = [
        'adhesion' => 'Adhesion',
        'textile' => 'Textile',
        'accessoire' => 'Accessoire',
        'materiel' => 'Materiel',
        'evenement' => 'Evenement',
    ];

    /**
     * @var array<string, string>
     */
    public const PUBLICS = [
        'tous' => 'Tous publics',
        'membre' => 'Adherents',
        'jeune' => 'Jeunes',
        'famille' => 'Familles',
        'competiteur' => 'Competiteurs',
    ];

    /**
     * @var array<string, string>
     */
    public const MODES_VENTE = [
        'reservation' => 'Reservation',
        'precommande' => 'Precommande',
        'adhesion' => 'Adhesion',
    ];

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listerCatalogue(): array
    {
        return $this->lister(true);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listerTousPourAdmin(): array
    {
        return $this->lister(false);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function trouverParIdentifiant(string $identifiantProduit): ?array
    {
        $identifiantProduit = trim($identifiantProduit);

        if ($identifiantProduit === '' || ! $this->tableDisponible()) {
            return null;
        }

        $ligne = DB::table('boutique_produit')
            ->where('identifiant_produit', $identifiantProduit)
            ->first();

        return $ligne !== null ? $this->normaliserProduit((array) $ligne) : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function trouverParReference(string $referenceProduit): ?array
    {
        $referenceProduit = trim($referenceProduit);

        if ($referenceProduit === '' || ! $this->tableDisponible()) {
            return null;
        }

        $ligne = DB::table('boutique_produit')
            ->where('reference_produit', $referenceProduit)
            ->first();

        return $ligne !== null ? $this->normaliserProduit((array) $ligne) : null;
    }

    /**
     * @param  array<string, mixed>  $donnees
     * @return array<string, mixed>
     */
    public function creer(array $donnees): array
    {
        if (! $this->tableDisponible()) {
            return [];
        }

        $identifiantProduit = 'produit_'.bin2hex(random_bytes(8));

        DB::table('boutique_produit')->insert([
            'identifiant_produit' => $identifiantProduit,
            'reference_produit' => (string) ($donnees['reference_produit'] ?? ''),
            'titre_produit' => (string) ($donnees['titre_produit'] ?? ''),
            'categorie_produit' => (string) ($donnees['categorie_produit'] ?? ''),
            'public_cible' => (string) ($donnees['public_cible'] ?? 'tous'),
            'prix_euros' => (int) ($donnees['prix_euros'] ?? 0),
            'badge' => $this->normaliserTexteOptionnel($donnees['badge'] ?? null),
            'mode_vente' => (string) ($donnees['mode_vente'] ?? 'reservation'),
            'texte_produit' => $this->normaliserTexteOptionnel($donnees['texte_produit'] ?? null),
            'resume_produit' => $this->normaliserTexteOptionnel($donnees['resume_produit'] ?? null),
            'avantages_json' => $this->encoderAvantages($donnees['avantages'] ?? []),
            'ordre_affichage' => (int) ($donnees['ordre_affichage'] ?? 1),
            'est_en_stock' => (bool) ($donnees['est_en_stock'] ?? false),
            'est_actif' => (bool) ($donnees['est_actif'] ?? true),
            'identifiant_auteur' => (string) ($donnees['identifiant_auteur'] ?? ''),
            'cree_le' => now()->format('Y-m-d H:i:s'),
            'mis_a_jour_le' => now()->format('Y-m-d H:i:s'),
        ]);

        return $this->trouverParIdentifiant($identifiantProduit) ?? [];
    }

    /**
     * @param  array<string, mixed>  $donnees
     * @return array<string, mixed>|null
     */
    public function mettreAJour(string $identifiantProduit, array $donnees): ?array
    {
        $identifiantProduit = trim($identifiantProduit);

        if ($identifiantProduit === '' || ! $this->tableDisponible()) {
            return null;
        }

        DB::table('boutique_produit')
            ->where('identifiant_produit', $identifiantProduit)
            ->update([
                'reference_produit' => (string) ($donnees['reference_produit'] ?? ''),
                'titre_produit' => (string) ($donnees['titre_produit'] ?? ''),
                'categorie_produit' => (string) ($donnees['categorie_produit'] ?? ''),
                'public_cible' => (string) ($donnees['public_cible'] ?? 'tous'),
                'prix_euros' => (int) ($donnees['prix_euros'] ?? 0),
                'badge' => $this->normaliserTexteOptionnel($donnees['badge'] ?? null),
                'mode_vente' => (string) ($donnees['mode_vente'] ?? 'reservation'),
                'texte_produit' => $this->normaliserTexteOptionnel($donnees['texte_produit'] ?? null),
                'resume_produit' => $this->normaliserTexteOptionnel($donnees['resume_produit'] ?? null),
                'avantages_json' => $this->encoderAvantages($donnees['avantages'] ?? []),
                'ordre_affichage' => (int) ($donnees['ordre_affichage'] ?? 1),
                'est_en_stock' => (bool) ($donnees['est_en_stock'] ?? false),
                'est_actif' => (bool) ($donnees['est_actif'] ?? true),
                'mis_a_jour_le' => now()->format('Y-m-d H:i:s'),
            ]);

        return $this->trouverParIdentifiant($identifiantProduit);
    }

    public function supprimer(string $identifiantProduit): bool
    {
        $identifiantProduit = trim($identifiantProduit);

        if ($identifiantProduit === '' || ! $this->tableDisponible()) {
            return false;
        }

        return DB::table('boutique_produit')
            ->where('identifiant_produit', $identifiantProduit)
            ->delete() > 0;
    }

    public function categorieEstValide(string $categorie): bool
    {
        return array_key_exists($categorie, self::CATEGORIES);
    }

    public function publicEstValide(string $publicCible): bool
    {
        return array_key_exists($publicCible, self::PUBLICS);
    }

    public function modeVenteEstValide(string $modeVente): bool
    {
        return array_key_exists($modeVente, self::MODES_VENTE);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function lister(bool $uniquementActifs): array
    {
        if (! $this->tableDisponible()) {
            return [];
        }

        try {
            $requete = DB::table('boutique_produit')
                ->orderBy('ordre_affichage')
                ->orderBy('titre_produit');

            if ($uniquementActifs) {
                $requete->where('est_actif', true);
            }

            return array_map(
                fn (object $ligne): array => $this->normaliserProduit((array) $ligne),
                $requete->get()->all()
            );
        } catch (Throwable $exception) {
            report($exception);

            return [];
        }
    }

    /**
     * @param  array<string, mixed>  $ligne
     * @return array<string, mixed>
     */
    private function normaliserProduit(array $ligne): array
    {
        $categorie = (string) ($ligne['categorie_produit'] ?? '');
        $publicCible = (string) ($ligne['public_cible'] ?? 'tous');
        $modeVente = (string) ($ligne['mode_vente'] ?? 'reservation');
        $estEnStock = (bool) ($ligne['est_en_stock'] ?? false);
        $badge = trim((string) ($ligne['badge'] ?? ''));

        return [
            'identifiant' => (string) ($ligne['identifiant_produit'] ?? ''),
            'identifiant_produit' => (string) ($ligne['identifiant_produit'] ?? ''),
            'reference' => (string) ($ligne['reference_produit'] ?? ''),
            'reference_produit' => (string) ($ligne['reference_produit'] ?? ''),
            'titre' => (string) ($ligne['titre_produit'] ?? ''),
            'titre_produit' => (string) ($ligne['titre_produit'] ?? ''),
            'categorie' => $categorie,
            'categorie_produit' => $categorie,
            'categorie_label' => self::CATEGORIES[$categorie] ?? 'Produit',
            'public_cible' => $publicCible,
            'public_label' => self::PUBLICS[$publicCible] ?? 'Tous publics',
            'prix_euros' => (int) ($ligne['prix_euros'] ?? 0),
            'badge' => $badge !== '' ? $badge : (self::CATEGORIES[$categorie] ?? 'Club'),
            'mode_vente' => $modeVente,
            'mode_vente_label' => self::MODES_VENTE[$modeVente] ?? 'Reservation',
            'texte' => (string) ($ligne['texte_produit'] ?? ''),
            'texte_produit' => (string) ($ligne['texte_produit'] ?? ''),
            'resume' => (string) ($ligne['resume_produit'] ?? ''),
            'resume_produit' => (string) ($ligne['resume_produit'] ?? ''),
            'avantages' => $this->decoderAvantages($ligne['avantages_json'] ?? '[]'),
            'en_stock' => $estEnStock,
            'est_en_stock' => $estEnStock,
            'stock_label' => $this->genererLibelleStock($estEnStock, $modeVente, $categorie),
            'est_actif' => (bool) ($ligne['est_actif'] ?? false),
            'ordre_affichage' => (int) ($ligne['ordre_affichage'] ?? 1),
            'identifiant_auteur' => (string) ($ligne['identifiant_auteur'] ?? ''),
            'cree_le' => (string) ($ligne['cree_le'] ?? ''),
            'mis_a_jour_le' => (string) ($ligne['mis_a_jour_le'] ?? ''),
        ];
    }

    /**
     * @param  mixed  $avantages
     * @return array<int, string>
     */
    private function decoderAvantages(mixed $avantages): array
    {
        if (! is_string($avantages) || trim($avantages) === '') {
            return [];
        }

        try {
            $valeurs = json_decode($avantages, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return [];
        }

        if (! is_array($valeurs)) {
            return [];
        }

        $normalises = array_values(array_filter(array_map(
            static fn (mixed $valeur): string => trim((string) $valeur),
            $valeurs
        )));

        return array_slice(array_values(array_unique($normalises)), 0, 12);
    }

    /**
     * @param  mixed  $avantages
     */
    private function encoderAvantages(mixed $avantages): string
    {
        $liste = is_array($avantages) ? $avantages : [];
        $normalises = array_values(array_filter(array_map(
            static fn (mixed $valeur): string => trim((string) $valeur),
            $liste
        )));

        return json_encode(array_slice(array_values(array_unique($normalises)), 0, 12), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    private function genererLibelleStock(bool $estEnStock, string $modeVente, string $categorie): string
    {
        if ($categorie === 'adhesion' || $modeVente === 'adhesion') {
            return 'Demande ouverte';
        }

        if ($estEnStock) {
            return 'Disponible';
        }

        if ($modeVente === 'precommande') {
            return 'Precommande';
        }

        return 'Rupture';
    }

    private function normaliserTexteOptionnel(mixed $valeur): ?string
    {
        $texte = trim((string) $valeur);

        return $texte !== '' ? $texte : null;
    }

    private function tableDisponible(): bool
    {
        try {
            return Schema::hasTable('boutique_produit');
        } catch (Throwable) {
            return false;
        }
    }
}
