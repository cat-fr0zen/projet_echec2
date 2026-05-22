<?php

declare(strict_types=1);

final class DepotMediasOracle
{
    public function __construct(private BaseDeDonneesOracle $base)
    {
    }

    public function listerTous(): array
    {
        return $this->chargerMedias('ORDER BY m.cree_le DESC');
    }

    public function trouverPublies(): array
    {
        return $this->chargerMedias('WHERE m.code_statut = :statut ORDER BY m.cree_le DESC', [
            'statut' => DepotMedias::STATUT_PUBLIE,
        ]);
    }

    public function trouverParIdentifiantAuteur(string $identifiantAuteur): array
    {
        return $this->chargerMedias('WHERE m.identifiant_auteur = :identifiant_auteur ORDER BY m.cree_le DESC', [
            'identifiant_auteur' => $identifiantAuteur,
        ]);
    }

    public function creer(array $donnees): array
    {
        $identifiant = 'media_' . bin2hex(random_bytes(8));

        $this->base->executer(
            'INSERT INTO media_publication (
                identifiant, identifiant_auteur, nom_auteur, code_type_media,
                titre, description, nom_fichier_original, nom_fichier_stocke,
                chemin_public, type_mime, taille_octets, code_statut, cree_le
            ) VALUES (
                :identifiant, :identifiant_auteur, :nom_auteur, :code_type_media,
                :titre, :description, :nom_fichier_original, :nom_fichier_stocke,
                :chemin_public, :type_mime, :taille_octets, :code_statut, SYSDATE
            )',
            [
                'identifiant' => $identifiant,
                'identifiant_auteur' => (string) $donnees['identifiant_auteur'],
                'nom_auteur' => (string) $donnees['nom_auteur'],
                'code_type_media' => (string) $donnees['type_media'],
                'titre' => (string) $donnees['titre'],
                'description' => (string) $donnees['description'],
                'nom_fichier_original' => (string) $donnees['nom_fichier_original'],
                'nom_fichier_stocke' => (string) $donnees['nom_fichier_stocke'],
                'chemin_public' => (string) $donnees['chemin_public'],
                'type_mime' => (string) $donnees['type_mime'],
                'taille_octets' => (int) $donnees['taille_octets'],
                'code_statut' => DepotMedias::STATUT_EN_ATTENTE,
            ]
        );

        return $this->chargerMedias('WHERE m.identifiant = :identifiant', ['identifiant' => $identifiant])[0] ?? [];
    }

    public function changerStatut(string $identifiant, string $statut): ?array
    {
        if (!in_array($statut, [DepotMedias::STATUT_EN_ATTENTE, DepotMedias::STATUT_PUBLIE, DepotMedias::STATUT_REFUSE], true)) {
            return null;
        }

        $lignes = $this->base->executer(
            'UPDATE media_publication
                SET code_statut = :code_statut,
                    mis_a_jour_le = SYSDATE
              WHERE identifiant = :identifiant',
            [
                'code_statut' => $statut,
                'identifiant' => $identifiant,
            ]
        );

        return $lignes > 0
            ? ($this->chargerMedias('WHERE m.identifiant = :identifiant', ['identifiant' => $identifiant])[0] ?? null)
            : null;
    }

    private function chargerMedias(string $suffixe = '', array $parametres = []): array
    {
        $lignes = $this->base->lireTout(
            'SELECT
                m.identifiant,
                m.identifiant_auteur,
                m.nom_auteur,
                m.code_type_media,
                m.titre,
                m.description,
                m.nom_fichier_original,
                m.nom_fichier_stocke,
                m.chemin_public,
                m.type_mime,
                m.taille_octets,
                m.code_statut,
                TO_CHAR(m.cree_le, \'YYYY-MM-DD"T"HH24:MI:SS"Z"\') cree_le,
                TO_CHAR(m.mis_a_jour_le, \'YYYY-MM-DD"T"HH24:MI:SS"Z"\') mis_a_jour_le
            FROM media_publication m ' . $suffixe,
            $parametres
        );

        return array_map(static function (array $ligne): array {
            $statut = (string) ($ligne['code_statut'] ?? DepotMedias::STATUT_EN_ATTENTE);

            return [
                'identifiant' => (string) ($ligne['identifiant'] ?? ''),
                'identifiant_auteur' => (string) ($ligne['identifiant_auteur'] ?? ''),
                'nom_auteur' => (string) ($ligne['nom_auteur'] ?? ''),
                'type_media' => (string) ($ligne['code_type_media'] ?? DepotMedias::TYPE_PHOTO),
                'titre' => (string) ($ligne['titre'] ?? ''),
                'description' => (string) ($ligne['description'] ?? ''),
                'nom_fichier_original' => (string) ($ligne['nom_fichier_original'] ?? ''),
                'nom_fichier_stocke' => (string) ($ligne['nom_fichier_stocke'] ?? ''),
                'chemin_public' => (string) ($ligne['chemin_public'] ?? ''),
                'type_mime' => (string) ($ligne['type_mime'] ?? ''),
                'taille_octets' => (int) ($ligne['taille_octets'] ?? 0),
                'statut' => $statut,
                'libelle_statut' => match ($statut) {
                    DepotMedias::STATUT_PUBLIE => 'Publie',
                    DepotMedias::STATUT_REFUSE => 'Refuse',
                    default => 'En attente',
                },
                'cree_le' => (string) ($ligne['cree_le'] ?? ''),
                'mis_a_jour_le' => (string) ($ligne['mis_a_jour_le'] ?? ''),
            ];
        }, $lignes);
    }
}
