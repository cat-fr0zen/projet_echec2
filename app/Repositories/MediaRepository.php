<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\MediaPublication;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Throwable;

final class MediaRepository
{
    public function listerTous(): array
    {
        return $this->chargerMedias(DB::table('media_publication')->orderByDesc('cree_le')->get()->all());
    }

    public function trouverPublies(): array
    {
        return $this->chargerMedias(
            DB::table('media_publication')
                ->where('code_statut', MediaPublication::STATUT_PUBLIE)
                ->orderByDesc('cree_le')
                ->get()
                ->all()
        );
    }

    public function trouverParIdentifiantAuteur(string $identifiantAuteur): array
    {
        return $this->chargerMedias(
            DB::table('media_publication')
                ->where('identifiant_auteur', $identifiantAuteur)
                ->orderByDesc('cree_le')
                ->get()
                ->all()
        );
    }

    public function creer(array $donnees): array
    {
        $identifiant = 'media_' . bin2hex(random_bytes(8));

        DB::table('media_publication')->insert([
            'identifiant' => $identifiant,
            'identifiant_auteur' => (string) ($donnees['identifiant_auteur'] ?? ''),
            'nom_auteur' => (string) ($donnees['nom_auteur'] ?? ''),
            'code_type_media' => (string) ($donnees['type_media'] ?? MediaPublication::TYPE_PHOTO),
            'titre' => (string) ($donnees['titre'] ?? ''),
            'description' => (string) ($donnees['description'] ?? ''),
            'nom_fichier_original' => (string) ($donnees['nom_fichier_original'] ?? ''),
            'nom_fichier_stocke' => (string) ($donnees['nom_fichier_stocke'] ?? ''),
            'chemin_public' => (string) ($donnees['chemin_public'] ?? ''),
            'type_mime' => (string) ($donnees['type_mime'] ?? ''),
            'taille_octets' => (int) ($donnees['taille_octets'] ?? 0),
            'code_statut' => MediaPublication::STATUT_EN_ATTENTE,
            'cree_le' => date('Y-m-d H:i:s'),
        ]);

        return $this->trouverParIdentifiant($identifiant) ?? [];
    }

    public function changerStatut(string $identifiant, string $statut): ?array
    {
        if (!in_array($statut, [
            MediaPublication::STATUT_EN_ATTENTE,
            MediaPublication::STATUT_PUBLIE,
            MediaPublication::STATUT_REFUSE,
        ], true)) {
            return null;
        }

        $updated = DB::table('media_publication')
            ->where('identifiant', $identifiant)
            ->update([
                'code_statut' => $statut,
                'mis_a_jour_le' => date('Y-m-d H:i:s'),
            ]);

        return $updated > 0 ? $this->trouverParIdentifiant($identifiant) : null;
    }

    public function trouverParIdentifiant(string $identifiant): ?array
    {
        $row = DB::table('media_publication')->where('identifiant', $identifiant)->first();

        return $row !== null ? $this->normaliserMedia((array) $row) : null;
    }

    /**
     * @param array<int, object> $rows
     * @return array<int, array<string, mixed>>
     */
    private function chargerMedias(array $rows): array
    {
        return array_map(fn (object $row): array => $this->normaliserMedia((array) $row), $rows);
    }

    private function normaliserMedia(array $row): array
    {
        $statut = (string) ($row['code_statut'] ?? MediaPublication::STATUT_EN_ATTENTE);

        return [
            'identifiant' => (string) ($row['identifiant'] ?? ''),
            'identifiant_auteur' => (string) ($row['identifiant_auteur'] ?? ''),
            'nom_auteur' => (string) ($row['nom_auteur'] ?? ''),
            'type_media' => (string) ($row['code_type_media'] ?? MediaPublication::TYPE_PHOTO),
            'titre' => (string) ($row['titre'] ?? ''),
            'description' => (string) ($row['description'] ?? ''),
            'nom_fichier_original' => (string) ($row['nom_fichier_original'] ?? ''),
            'nom_fichier_stocke' => (string) ($row['nom_fichier_stocke'] ?? ''),
            'chemin_public' => (string) ($row['chemin_public'] ?? ''),
            'type_mime' => (string) ($row['type_mime'] ?? ''),
            'taille_octets' => (int) ($row['taille_octets'] ?? 0),
            'statut' => $statut,
            'libelle_statut' => match ($statut) {
                MediaPublication::STATUT_PUBLIE => 'Publie',
                MediaPublication::STATUT_REFUSE => 'Refuse',
                default => 'En attente',
            },
            'cree_le' => $this->formaterDateIso($row['cree_le'] ?? null),
            'mis_a_jour_le' => $this->formaterDateIso($row['mis_a_jour_le'] ?? null),
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
