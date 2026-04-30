<?php

declare(strict_types=1);

final class DepotMedias
{
    public const TYPE_PHOTO = 'photo';
    public const TYPE_VIDEO = 'video';

    public const STATUT_EN_ATTENTE = 'en_attente_validation';
    public const STATUT_PUBLIE = 'publie';
    public const STATUT_REFUSE = 'refuse';

    public function __construct(private StockageJson $stockage)
    {
    }

    public function listerTous(): array
    {
        $medias = array_map(
            fn (array $enregistrement): array => $this->normaliserMedia($enregistrement),
            $this->stockage->lire()
        );

        usort(
            $medias,
            fn (array $gauche, array $droite): int => strcmp(
                (string) ($droite['cree_le'] ?? ''),
                (string) ($gauche['cree_le'] ?? '')
            )
        );

        return $medias;
    }

    public function trouverPublies(): array
    {
        return array_values(
            array_filter(
                $this->listerTous(),
                fn (array $media): bool => $media['statut'] === self::STATUT_PUBLIE
            )
        );
    }

    public function trouverParIdentifiantAuteur(string $identifiantAuteur): array
    {
        return array_values(
            array_filter(
                $this->listerTous(),
                fn (array $media): bool => $media['identifiant_auteur'] === $identifiantAuteur
            )
        );
    }

    public function creer(array $donnees): array
    {
        $medias = $this->stockage->lire();

        $media = [
            'identifiant' => 'media_' . bin2hex(random_bytes(8)),
            'identifiant_auteur' => $donnees['identifiant_auteur'],
            'nom_auteur' => $donnees['nom_auteur'],
            'type_media' => $donnees['type_media'],
            'titre' => $donnees['titre'],
            'description' => $donnees['description'],
            'nom_fichier_original' => $donnees['nom_fichier_original'],
            'nom_fichier_stocke' => $donnees['nom_fichier_stocke'],
            'chemin_public' => $donnees['chemin_public'],
            'type_mime' => $donnees['type_mime'],
            'taille_octets' => $donnees['taille_octets'],
            'statut' => self::STATUT_EN_ATTENTE,
            'cree_le' => gmdate('c'),
        ];

        $medias[] = $media;
        $this->stockage->ecrire($medias);

        return $this->normaliserMedia($media);
    }

    public function changerStatut(string $identifiant, string $statut): ?array
    {
        if (!in_array($statut, [self::STATUT_EN_ATTENTE, self::STATUT_PUBLIE, self::STATUT_REFUSE], true)) {
            return null;
        }

        $medias = $this->stockage->lire();

        foreach ($medias as $index => $enregistrement) {
            $media = $this->normaliserMedia($enregistrement);

            if (($media['identifiant'] ?? '') !== $identifiant) {
                continue;
            }

            $medias[$index] = [
                'identifiant' => $media['identifiant'],
                'identifiant_auteur' => $media['identifiant_auteur'],
                'nom_auteur' => $media['nom_auteur'],
                'type_media' => $media['type_media'],
                'titre' => $media['titre'],
                'description' => $media['description'],
                'nom_fichier_original' => $media['nom_fichier_original'],
                'nom_fichier_stocke' => $media['nom_fichier_stocke'],
                'chemin_public' => $media['chemin_public'],
                'type_mime' => $media['type_mime'],
                'taille_octets' => $media['taille_octets'],
                'statut' => $statut,
                'cree_le' => $media['cree_le'],
                'mis_a_jour_le' => gmdate('c'),
            ];

            $this->stockage->ecrire($medias);

            return $this->normaliserMedia($medias[$index]);
        }

        return null;
    }

    private function normaliserMedia(array $enregistrement): array
    {
        $typeMedia = (string) ($enregistrement['type_media'] ?? $enregistrement['media_type'] ?? self::TYPE_PHOTO);
        if (!in_array($typeMedia, [self::TYPE_PHOTO, self::TYPE_VIDEO], true)) {
            $typeMedia = self::TYPE_PHOTO;
        }

        $statutBrut = (string) ($enregistrement['statut'] ?? $enregistrement['status'] ?? self::STATUT_EN_ATTENTE);
        $statut = match ($statutBrut) {
            self::STATUT_PUBLIE => self::STATUT_PUBLIE,
            self::STATUT_REFUSE => self::STATUT_REFUSE,
            default => self::STATUT_EN_ATTENTE,
        };

        return [
            'identifiant' => (string) ($enregistrement['identifiant'] ?? $enregistrement['id'] ?? ''),
            'identifiant_auteur' => (string) ($enregistrement['identifiant_auteur'] ?? $enregistrement['author_id'] ?? ''),
            'nom_auteur' => (string) ($enregistrement['nom_auteur'] ?? $enregistrement['author_name'] ?? ''),
            'type_media' => $typeMedia,
            'titre' => (string) ($enregistrement['titre'] ?? $enregistrement['title'] ?? ''),
            'description' => (string) ($enregistrement['description'] ?? ''),
            'nom_fichier_original' => (string) ($enregistrement['nom_fichier_original'] ?? ''),
            'nom_fichier_stocke' => (string) ($enregistrement['nom_fichier_stocke'] ?? ''),
            'chemin_public' => (string) ($enregistrement['chemin_public'] ?? ''),
            'type_mime' => (string) ($enregistrement['type_mime'] ?? ''),
            'taille_octets' => (int) ($enregistrement['taille_octets'] ?? 0),
            'statut' => $statut,
            'libelle_statut' => match ($statut) {
                self::STATUT_PUBLIE => 'Publie',
                self::STATUT_REFUSE => 'Refuse',
                default => 'En attente',
            },
            'cree_le' => (string) ($enregistrement['cree_le'] ?? $enregistrement['created_at'] ?? ''),
            'mis_a_jour_le' => (string) ($enregistrement['mis_a_jour_le'] ?? $enregistrement['updated_at'] ?? ''),
        ];
    }
}
