<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : BureauMembreRepository.
 */

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class BureauMembreRepository
{
    /**
     * @param  array<int, array<string, mixed>>  $membresParDefaut
     * @return array<int, array<string, mixed>>
     */
    public function listerPourAccueil(array $membresParDefaut = []): array
    {
        if (! $this->tableDisponible()) {
            return $membresParDefaut;
        }

        try {
            return DB::table('bureau_membre')
                ->where('est_actif', true)
                ->orderBy('ordre_affichage')
                ->orderBy('cree_le')
                ->get()
                ->map(fn (object $ligne): array => $this->normaliser((array) $ligne))
                ->all();
        } catch (Throwable) {
            return $membresParDefaut;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listerTousPourAdmin(): array
    {
        if (! $this->tableDisponible()) {
            return [];
        }

        try {
            return DB::table('bureau_membre')
                ->orderBy('ordre_affichage')
                ->orderBy('cree_le')
                ->get()
                ->map(fn (object $ligne): array => $this->normaliser((array) $ligne))
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @param  array<string, mixed>  $donnees
     */
    public function creer(array $donnees): string
    {
        $identifiant = 'bureau_'.bin2hex(random_bytes(8));
        $instant = now()->format('Y-m-d H:i:s');

        DB::table('bureau_membre')->insert([
            'identifiant_membre_bureau' => $identifiant,
            'prenom' => trim((string) ($donnees['prenom'] ?? '')),
            'nom' => trim((string) ($donnees['nom'] ?? '')),
            'role_affiche' => trim((string) ($donnees['role_affiche'] ?? '')),
            'description' => trim((string) ($donnees['description'] ?? '')),
            'photo_url' => trim((string) ($donnees['photo_url'] ?? '')),
            'ordre_affichage' => max(1, (int) ($donnees['ordre_affichage'] ?? 1)),
            'est_actif' => (bool) ($donnees['est_actif'] ?? true),
            'cree_le' => $instant,
            'mis_a_jour_le' => $instant,
        ]);

        return $identifiant;
    }

    /**
     * @param  array<string, mixed>  $donnees
     */
    public function mettreAJour(string $identifiant, array $donnees): bool
    {
        if ($identifiant === '' || ! $this->tableDisponible()) {
            return false;
        }

        return DB::table('bureau_membre')
            ->where('identifiant_membre_bureau', $identifiant)
            ->update([
                'prenom' => trim((string) ($donnees['prenom'] ?? '')),
                'nom' => trim((string) ($donnees['nom'] ?? '')),
                'role_affiche' => trim((string) ($donnees['role_affiche'] ?? '')),
                'description' => trim((string) ($donnees['description'] ?? '')),
                'photo_url' => trim((string) ($donnees['photo_url'] ?? '')),
                'ordre_affichage' => max(1, (int) ($donnees['ordre_affichage'] ?? 1)),
                'est_actif' => (bool) ($donnees['est_actif'] ?? true),
                'mis_a_jour_le' => now()->format('Y-m-d H:i:s'),
            ]) > 0;
    }

    public function supprimer(string $identifiant): bool
    {
        if ($identifiant === '' || ! $this->tableDisponible()) {
            return false;
        }

        return DB::table('bureau_membre')
            ->where('identifiant_membre_bureau', $identifiant)
            ->delete() > 0;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function trouverParIdentifiant(string $identifiant): ?array
    {
        if ($identifiant === '' || ! $this->tableDisponible()) {
            return null;
        }

        $ligne = DB::table('bureau_membre')
            ->where('identifiant_membre_bureau', $identifiant)
            ->first();

        return $ligne === null ? null : $this->normaliser((array) $ligne);
    }

    /**
     * @param  array<string, mixed>  $ligne
     * @return array<string, mixed>
     */
    private function normaliser(array $ligne): array
    {
        $prenom = trim((string) ($ligne['prenom'] ?? ''));
        $nom = trim((string) ($ligne['nom'] ?? ''));
        $nomComplet = trim($prenom.' '.$nom);

        return [
            'identifiant_membre_bureau' => (string) ($ligne['identifiant_membre_bureau'] ?? ''),
            'prenom' => $prenom,
            'nom' => $nom,
            'nom_complet' => $nomComplet,
            'full_name' => $nomComplet,
            'role' => trim((string) ($ligne['role_affiche'] ?? '')),
            'description' => trim((string) ($ligne['description'] ?? '')),
            'photo' => trim((string) ($ligne['photo_url'] ?? '')),
            'ordre_affichage' => (int) ($ligne['ordre_affichage'] ?? 1),
            'est_actif' => (bool) ($ligne['est_actif'] ?? false),
        ];
    }

    private function tableDisponible(): bool
    {
        try {
            return Schema::hasTable('bureau_membre');
        } catch (Throwable) {
            return false;
        }
    }
}
