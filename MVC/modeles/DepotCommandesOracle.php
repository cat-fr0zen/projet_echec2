<?php

declare(strict_types=1);

final class DepotCommandesOracle
{
    public function __construct(private BaseDeDonneesOracle $base)
    {
    }

    public function listerToutes(): array
    {
        return $this->chargerCommandes('ORDER BY c.cree_le DESC');
    }

    public function listerParIdentifiantUtilisateur(string $identifiantUtilisateur): array
    {
        return $this->chargerCommandes('WHERE c.identifiant_utilisateur = :identifiant_utilisateur ORDER BY c.cree_le DESC', [
            'identifiant_utilisateur' => $identifiantUtilisateur,
        ]);
    }

    public function creer(array $donnees): array
    {
        $identifiant = 'commande_' . bin2hex(random_bytes(8));

        $this->base->executer(
            'INSERT INTO commande_locale (
                identifiant, identifiant_utilisateur, nom_utilisateur,
                produit, categorie, code_statut, cree_le
            ) VALUES (
                :identifiant, :identifiant_utilisateur, :nom_utilisateur,
                :produit, :categorie, :code_statut, SYSDATE
            )',
            [
                'identifiant' => $identifiant,
                'identifiant_utilisateur' => (string) $donnees['identifiant_utilisateur'],
                'nom_utilisateur' => (string) $donnees['nom_utilisateur'],
                'produit' => (string) $donnees['produit'],
                'categorie' => (string) $donnees['categorie'],
                'code_statut' => DepotCommandes::STATUT_EN_ATTENTE,
            ]
        );

        return $this->chargerCommandes('WHERE c.identifiant = :identifiant', ['identifiant' => $identifiant])[0] ?? [];
    }

    public function changerStatut(string $identifiant, string $statut): ?array
    {
        if (!in_array($statut, [DepotCommandes::STATUT_EN_ATTENTE, DepotCommandes::STATUT_VALIDEE, DepotCommandes::STATUT_ANNULEE], true)) {
            return null;
        }

        $lignes = $this->base->executer(
            'UPDATE commande_locale
                SET code_statut = :code_statut,
                    mis_a_jour_le = SYSDATE
              WHERE identifiant = :identifiant',
            [
                'code_statut' => $statut,
                'identifiant' => $identifiant,
            ]
        );

        return $lignes > 0
            ? ($this->chargerCommandes('WHERE c.identifiant = :identifiant', ['identifiant' => $identifiant])[0] ?? null)
            : null;
    }

    private function chargerCommandes(string $suffixe = '', array $parametres = []): array
    {
        $lignes = $this->base->lireTout(
            'SELECT
                c.identifiant,
                c.identifiant_utilisateur,
                c.nom_utilisateur,
                c.produit,
                c.categorie,
                c.code_statut,
                TO_CHAR(c.cree_le, \'YYYY-MM-DD"T"HH24:MI:SS"Z"\') cree_le,
                TO_CHAR(c.mis_a_jour_le, \'YYYY-MM-DD"T"HH24:MI:SS"Z"\') mis_a_jour_le
            FROM commande_locale c ' . $suffixe,
            $parametres
        );

        return array_map(static function (array $ligne): array {
            $statut = (string) ($ligne['code_statut'] ?? DepotCommandes::STATUT_EN_ATTENTE);

            return [
                'identifiant' => (string) ($ligne['identifiant'] ?? ''),
                'identifiant_utilisateur' => (string) ($ligne['identifiant_utilisateur'] ?? ''),
                'nom_utilisateur' => (string) ($ligne['nom_utilisateur'] ?? ''),
                'produit' => (string) ($ligne['produit'] ?? ''),
                'categorie' => (string) ($ligne['categorie'] ?? ''),
                'statut' => $statut,
                'libelle_statut' => match ($statut) {
                    DepotCommandes::STATUT_VALIDEE => 'Validee',
                    DepotCommandes::STATUT_ANNULEE => 'Annulee',
                    default => 'En attente',
                },
                'cree_le' => (string) ($ligne['cree_le'] ?? ''),
                'mis_a_jour_le' => (string) ($ligne['mis_a_jour_le'] ?? ''),
            ];
        }, $lignes);
    }
}
