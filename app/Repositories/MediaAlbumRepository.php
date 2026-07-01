<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : MediaAlbumRepository.
 */

declare(strict_types=1);

namespace App\Repositories;

use DateTimeImmutable;
use Throwable;

final class MediaAlbumRepository
{
    private const CLE_PARAMETRE = 'site_media_albums';
    public const STATUT_EN_ATTENTE = 'en_attente_validation';
    public const STATUT_PUBLIE = 'publie';
    public const STATUT_REFUSE = 'refuse';

    public function __construct(
        private ?ParametreSiteRepository $parametreSiteRepository = null
    ) {
        $this->parametreSiteRepository ??= new ParametreSiteRepository;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function lister(): array
    {
        $json = $this->parametreSiteRepository->obtenirTexte(self::CLE_PARAMETRE, '[]');
        $donnees = json_decode($json, true);

        if (! is_array($donnees)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn (mixed $album): ?array => is_array($album) ? $this->normaliserAlbum($album) : null,
            $donnees
        )));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listerPublies(): array
    {
        return array_values(array_filter(
            $this->lister(),
            static fn (array $album): bool => (string) ($album['statut'] ?? '') === self::STATUT_PUBLIE
        ));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listerParIdentifiantAuteur(string $identifiantAuteur): array
    {
        return array_values(array_filter(
            $this->lister(),
            static fn (array $album): bool => (string) ($album['identifiant_auteur'] ?? '') === trim($identifiantAuteur)
        ));
    }

    /**
     * @param array<string, mixed> $donnees
     * @return array<string, mixed>
     */
    public function ajouter(array $donnees): array
    {
        $albums = $this->lister();
        $album = $this->normaliserAlbum([
            'identifiant' => 'album_' . bin2hex(random_bytes(8)),
            'cree_le' => date('Y-m-d H:i:s'),
            ...$donnees,
        ]);

        $albums[] = $album;
        $this->sauvegarder($albums);

        return $album;
    }

    public function changerStatut(string $identifiant, string $statut): ?array
    {
        $identifiant = trim($identifiant);

        if (
            $identifiant === ''
            || ! in_array($statut, [self::STATUT_EN_ATTENTE, self::STATUT_PUBLIE, self::STATUT_REFUSE], true)
        ) {
            return null;
        }

        $albums = $this->lister();
        $misAJour = null;

        foreach ($albums as $index => $album) {
            if ((string) ($album['identifiant'] ?? '') !== $identifiant) {
                continue;
            }

            $albums[$index]['statut'] = $statut;
            $albums[$index]['mis_a_jour_le'] = date('Y-m-d H:i:s');
            $misAJour = $this->normaliserAlbum($albums[$index]);
            $albums[$index] = $misAJour;
            break;
        }

        if ($misAJour === null) {
            return null;
        }

        $this->sauvegarder($albums);

        return $misAJour;
    }

    public function supprimer(string $identifiant): bool
    {
        $identifiant = trim($identifiant);

        if ($identifiant === '') {
            return false;
        }

        $albums = $this->lister();
        $avant = count($albums);
        $albums = array_values(array_filter(
            $albums,
            static fn (array $album): bool => (string) ($album['identifiant'] ?? '') !== $identifiant
        ));

        if ($avant === count($albums)) {
            return false;
        }

        $this->sauvegarder($albums);

        return true;
    }

    /**
     * @param array<int, array<string, mixed>> $albums
     */
    private function sauvegarder(array $albums): void
    {
        $this->parametreSiteRepository->mettreAJourTexte(
            self::CLE_PARAMETRE,
            json_encode(array_values($albums), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]'
        );
    }

    /**
     * @param array<string, mixed> $album
     * @return array<string, mixed>
     */
    private function normaliserAlbum(array $album): array
    {
        $mediaIds = is_array($album['media_ids'] ?? null) ? $album['media_ids'] : [];
        $mediaIds = array_values(array_filter(array_map(
            static fn (mixed $mediaId): string => trim((string) $mediaId),
            $mediaIds
        )));
        $statut = (string) ($album['statut'] ?? self::STATUT_PUBLIE);
        if (! in_array($statut, [self::STATUT_EN_ATTENTE, self::STATUT_PUBLIE, self::STATUT_REFUSE], true)) {
            $statut = self::STATUT_PUBLIE;
        }

        return [
            'identifiant' => trim((string) ($album['identifiant'] ?? '')),
            'titre' => trim((string) ($album['titre'] ?? '')),
            'description' => trim((string) ($album['description'] ?? '')),
            'identifiant_auteur' => trim((string) ($album['identifiant_auteur'] ?? '')),
            'nom_auteur' => trim((string) ($album['nom_auteur'] ?? '')),
            'statut' => $statut,
            'libelle_statut' => match ($statut) {
                self::STATUT_PUBLIE => 'Publié',
                self::STATUT_REFUSE => 'Refusé',
                default => 'En attente',
            },
            'cree_le' => $this->formaterDateIso($album['cree_le'] ?? null),
            'mis_a_jour_le' => $this->formaterDateIso($album['mis_a_jour_le'] ?? null),
            'media_ids' => $mediaIds,
        ];
    }

    private function formaterDateIso(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return (new DateTimeImmutable((string) $value))->format('c');
        } catch (Throwable) {
            return (string) $value;
        }
    }
}
