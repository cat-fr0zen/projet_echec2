<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : BoutiqueCartService.
 */

declare(strict_types=1);

namespace App\Support;

final class BoutiqueCartService
{
    private const SESSION_KEY = 'panier_boutique';

    /**
     * @param  array<int, array<string, mixed>>  $catalogue
     * @return array<string, mixed>
     */
    public function obtenirPanier(array $catalogue): array
    {
        $catalogueIndexe = $this->indexerCatalogue($catalogue);
        $panierBrut = $this->chargerPanierBrut();
        $lignes = [];
        $nombreLignes = 0;
        $quantiteTotale = 0;
        $sousTotalEuros = 0;
        $contientAdhesion = false;
        $panierNettoye = [];

        foreach ($panierBrut as $identifiantProduit => $ligneBrute) {
            if (! is_string($identifiantProduit) || ! isset($catalogueIndexe[$identifiantProduit])) {
                continue;
            }

            $produit = $catalogueIndexe[$identifiantProduit];
            $quantite = $this->normaliserQuantite(
                (int) ($ligneBrute['quantite'] ?? 1),
                $this->estProduitAdhesion($produit)
            );

            if ($quantite <= 0) {
                continue;
            }

            $prixUnitaire = max(0, (int) ($produit['prix_euros'] ?? 0));
            $totalLigne = $prixUnitaire * $quantite;
            $estAdhesion = $this->estProduitAdhesion($produit);

            $lignes[] = [
                'identifiant' => $identifiantProduit,
                'reference' => (string) ($produit['reference'] ?? ''),
                'titre' => (string) ($produit['titre'] ?? 'Produit'),
                'categorie' => (string) ($produit['categorie'] ?? ''),
                'categorie_label' => (string) ($produit['categorie_label'] ?? $produit['type'] ?? 'Produit'),
                'badge' => (string) ($produit['badge'] ?? 'Club'),
                'prix_unitaire_euros' => $prixUnitaire,
                'quantite' => $quantite,
                'prix_total_euros' => $totalLigne,
                'stock_label' => (string) ($produit['stock_label'] ?? 'Disponible'),
                'public_label' => (string) ($produit['public_label'] ?? 'Tous'),
                'resume' => (string) ($produit['resume'] ?? ''),
                'est_adhesion' => $estAdhesion,
            ];

            $panierNettoye[$identifiantProduit] = ['quantite' => $quantite];
            $nombreLignes++;
            $quantiteTotale += $quantite;
            $sousTotalEuros += $totalLigne;
            $contientAdhesion = $contientAdhesion || $estAdhesion;
        }

        if ($panierNettoye !== $panierBrut) {
            $this->remplacerPanierBrut($panierNettoye);
        }

        return [
            'lignes' => $lignes,
            'nombre_lignes' => $nombreLignes,
            'quantite_totale' => $quantiteTotale,
            'sous_total_euros' => $sousTotalEuros,
            'contient_adhesion' => $contientAdhesion,
            'est_vide' => $nombreLignes === 0,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $catalogue
     * @return array<string, mixed>
     */
    public function ajouterProduit(array $catalogue, string $identifiantProduit, int $quantite = 1): array
    {
        $catalogueIndexe = $this->indexerCatalogue($catalogue);

        if (! isset($catalogueIndexe[$identifiantProduit])) {
            return $this->obtenirPanier($catalogue);
        }

        $produit = $catalogueIndexe[$identifiantProduit];
        $panier = $this->chargerPanierBrut();
        $estAdhesion = $this->estProduitAdhesion($produit);

        if ($estAdhesion) {
            foreach (array_keys($panier) as $identifiantPresent) {
                $produitPresent = $catalogueIndexe[$identifiantPresent] ?? null;

                if (is_array($produitPresent) && $this->estProduitAdhesion($produitPresent)) {
                    unset($panier[$identifiantPresent]);
                }
            }
        }

        $quantiteActuelle = (int) ($panier[$identifiantProduit]['quantite'] ?? 0);
        $panier[$identifiantProduit] = [
            'quantite' => $this->normaliserQuantite($quantiteActuelle + $quantite, $estAdhesion),
        ];

        $this->remplacerPanierBrut($panier);

        return $this->obtenirPanier($catalogue);
    }

    /**
     * @param  array<int, array<string, mixed>>  $catalogue
     * @return array<string, mixed>
     */
    public function mettreAJourQuantite(array $catalogue, string $identifiantProduit, int $quantite): array
    {
        $catalogueIndexe = $this->indexerCatalogue($catalogue);
        $panier = $this->chargerPanierBrut();

        if (! isset($catalogueIndexe[$identifiantProduit])) {
            unset($panier[$identifiantProduit]);
            $this->remplacerPanierBrut($panier);

            return $this->obtenirPanier($catalogue);
        }

        $produit = $catalogueIndexe[$identifiantProduit];
        $quantiteNormalisee = $this->normaliserQuantite($quantite, $this->estProduitAdhesion($produit));

        if ($quantiteNormalisee <= 0) {
            unset($panier[$identifiantProduit]);
        } else {
            $panier[$identifiantProduit] = ['quantite' => $quantiteNormalisee];
        }

        $this->remplacerPanierBrut($panier);

        return $this->obtenirPanier($catalogue);
    }

    public function retirerProduit(string $identifiantProduit): void
    {
        $panier = $this->chargerPanierBrut();
        unset($panier[$identifiantProduit]);
        $this->remplacerPanierBrut($panier);
    }

    public function vider(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /**
     * @return array<string, mixed>
     */
    public function configurationPaiementCarte(): array
    {
        $active = (bool) config('services.shop_card.enabled', false);
        $prestataire = trim((string) config('services.shop_card.provider_label', 'Prestataire externe'));
        $urlPaiement = trim((string) config('services.shop_card.checkout_url', ''));

        return [
            'active' => $active && $urlPaiement !== '',
            'prestataire' => $prestataire !== '' ? $prestataire : 'Prestataire externe',
            'checkout_url' => $urlPaiement,
            'resume' => $active && $urlPaiement !== ''
                ? "Le paiement CB pourra etre redirige vers {$prestataire}."
                : "Le panier est pret, mais le reglement CB doit encore etre branche sur un prestataire securise.",
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $catalogue
     * @return array<string, array<string, mixed>>
     */
    private function indexerCatalogue(array $catalogue): array
    {
        $index = [];

        foreach ($catalogue as $produit) {
            $identifiant = trim((string) ($produit['identifiant'] ?? ''));

            if ($identifiant === '') {
                continue;
            }

            $index[$identifiant] = $produit;
        }

        return $index;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function chargerPanierBrut(): array
    {
        $panier = session(self::SESSION_KEY, []);

        return is_array($panier) ? $panier : [];
    }

    /**
     * @param  array<string, array<string, mixed>>  $panier
     */
    private function remplacerPanierBrut(array $panier): void
    {
        if ($panier === []) {
            $this->vider();

            return;
        }

        session([self::SESSION_KEY => $panier]);
    }

    private function estProduitAdhesion(array $produit): bool
    {
        return (string) ($produit['categorie'] ?? '') === 'adhesion';
    }

    private function normaliserQuantite(int $quantite, bool $estAdhesion): int
    {
        if ($estAdhesion) {
            return $quantite > 0 ? 1 : 0;
        }

        return max(0, min($quantite, 99));
    }
}
