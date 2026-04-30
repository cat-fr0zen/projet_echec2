<?php

declare(strict_types=1);

final class DepotCommandes
{
    public const STATUT_EN_ATTENTE = 'en_attente';
    public const STATUT_VALIDEE = 'validee';
    public const STATUT_ANNULEE = 'annulee';

    public function __construct(private StockageJson $stockage)
    {
    }

    public function listerToutes(): array
    {
        $commandes = array_map(
            fn (array $enregistrement): array => $this->normaliserCommande($enregistrement),
            $this->stockage->lire()
        );

        usort(
            $commandes,
            fn (array $gauche, array $droite): int => strcmp(
                (string) ($droite['cree_le'] ?? ''),
                (string) ($gauche['cree_le'] ?? '')
            )
        );

        return $commandes;
    }

    public function listerParIdentifiantUtilisateur(string $identifiantUtilisateur): array
    {
        return array_values(
            array_filter(
                $this->listerToutes(),
                fn (array $commande): bool => $commande['identifiant_utilisateur'] === $identifiantUtilisateur
            )
        );
    }

    public function creer(array $donnees): array
    {
        $commandes = $this->stockage->lire();

        $commande = [
            'identifiant' => 'commande_' . bin2hex(random_bytes(8)),
            'identifiant_utilisateur' => $donnees['identifiant_utilisateur'],
            'nom_utilisateur' => $donnees['nom_utilisateur'],
            'produit' => $donnees['produit'],
            'categorie' => $donnees['categorie'],
            'statut' => self::STATUT_EN_ATTENTE,
            'cree_le' => gmdate('c'),
        ];

        $commandes[] = $commande;
        $this->stockage->ecrire($commandes);

        return $this->normaliserCommande($commande);
    }

    public function changerStatut(string $identifiant, string $statut): ?array
    {
        if (!in_array($statut, [self::STATUT_EN_ATTENTE, self::STATUT_VALIDEE, self::STATUT_ANNULEE], true)) {
            return null;
        }

        $commandes = $this->stockage->lire();

        foreach ($commandes as $index => $enregistrement) {
            $commande = $this->normaliserCommande($enregistrement);

            if (($commande['identifiant'] ?? '') !== $identifiant) {
                continue;
            }

            $commandes[$index] = [
                'identifiant' => $commande['identifiant'],
                'identifiant_utilisateur' => $commande['identifiant_utilisateur'],
                'nom_utilisateur' => $commande['nom_utilisateur'],
                'produit' => $commande['produit'],
                'categorie' => $commande['categorie'],
                'statut' => $statut,
                'cree_le' => $commande['cree_le'],
                'mis_a_jour_le' => gmdate('c'),
            ];

            $this->stockage->ecrire($commandes);

            return $this->normaliserCommande($commandes[$index]);
        }

        return null;
    }

    private function normaliserCommande(array $enregistrement): array
    {
        $statut = (string) ($enregistrement['statut'] ?? $enregistrement['status'] ?? self::STATUT_EN_ATTENTE);
        if (!in_array($statut, [self::STATUT_EN_ATTENTE, self::STATUT_VALIDEE, self::STATUT_ANNULEE], true)) {
            $statut = self::STATUT_EN_ATTENTE;
        }

        return [
            'identifiant' => (string) ($enregistrement['identifiant'] ?? $enregistrement['id'] ?? ''),
            'identifiant_utilisateur' => (string) ($enregistrement['identifiant_utilisateur'] ?? $enregistrement['user_id'] ?? ''),
            'nom_utilisateur' => (string) ($enregistrement['nom_utilisateur'] ?? $enregistrement['user_name'] ?? ''),
            'produit' => (string) ($enregistrement['produit'] ?? $enregistrement['product'] ?? ''),
            'categorie' => (string) ($enregistrement['categorie'] ?? $enregistrement['category'] ?? ''),
            'statut' => $statut,
            'libelle_statut' => match ($statut) {
                self::STATUT_VALIDEE => 'Validee',
                self::STATUT_ANNULEE => 'Annulee',
                default => 'En attente',
            },
            'cree_le' => (string) ($enregistrement['cree_le'] ?? $enregistrement['created_at'] ?? ''),
            'mis_a_jour_le' => (string) ($enregistrement['mis_a_jour_le'] ?? $enregistrement['updated_at'] ?? ''),
        ];
    }
}
