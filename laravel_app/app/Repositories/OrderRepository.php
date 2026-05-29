<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\CommandeLocale;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Throwable;

final class OrderRepository
{
    public function listerToutes(): array
    {
        return $this->chargerCommandes(DB::table('commande_locale')->orderByDesc('cree_le')->get()->all());
    }

    public function listerParIdentifiantUtilisateur(string $identifiantUtilisateur): array
    {
        return $this->chargerCommandes(
            DB::table('commande_locale')
                ->where('identifiant_utilisateur', $identifiantUtilisateur)
                ->orderByDesc('cree_le')
                ->get()
                ->all()
        );
    }

    public function creer(array $donnees): array
    {
        $identifiant = 'commande_' . bin2hex(random_bytes(8));

        DB::table('commande_locale')->insert([
            'identifiant' => $identifiant,
            'identifiant_utilisateur' => (string) ($donnees['identifiant_utilisateur'] ?? ''),
            'nom_utilisateur' => (string) ($donnees['nom_utilisateur'] ?? ''),
            'produit' => (string) ($donnees['produit'] ?? ''),
            'categorie' => (string) ($donnees['categorie'] ?? ''),
            'code_statut' => CommandeLocale::STATUT_EN_ATTENTE,
            'cree_le' => date('Y-m-d H:i:s'),
        ]);

        return $this->trouverParIdentifiant($identifiant) ?? [];
    }

    public function changerStatut(string $identifiant, string $statut): ?array
    {
        if (!in_array($statut, [
            CommandeLocale::STATUT_EN_ATTENTE,
            CommandeLocale::STATUT_VALIDEE,
            CommandeLocale::STATUT_ANNULEE,
        ], true)) {
            return null;
        }

        $updated = DB::table('commande_locale')
            ->where('identifiant', $identifiant)
            ->update([
                'code_statut' => $statut,
                'mis_a_jour_le' => date('Y-m-d H:i:s'),
            ]);

        return $updated > 0 ? $this->trouverParIdentifiant($identifiant) : null;
    }

    public function trouverParIdentifiant(string $identifiant): ?array
    {
        $row = DB::table('commande_locale')->where('identifiant', $identifiant)->first();

        return $row !== null ? $this->normaliserCommande((array) $row) : null;
    }

    /**
     * @param array<int, object> $rows
     * @return array<int, array<string, mixed>>
     */
    private function chargerCommandes(array $rows): array
    {
        return array_map(fn (object $row): array => $this->normaliserCommande((array) $row), $rows);
    }

    private function normaliserCommande(array $row): array
    {
        $statut = (string) ($row['code_statut'] ?? CommandeLocale::STATUT_EN_ATTENTE);

        return [
            'identifiant' => (string) ($row['identifiant'] ?? ''),
            'identifiant_utilisateur' => (string) ($row['identifiant_utilisateur'] ?? ''),
            'nom_utilisateur' => (string) ($row['nom_utilisateur'] ?? ''),
            'produit' => (string) ($row['produit'] ?? ''),
            'categorie' => (string) ($row['categorie'] ?? ''),
            'statut' => $statut,
            'libelle_statut' => match ($statut) {
                CommandeLocale::STATUT_VALIDEE => 'Validee',
                CommandeLocale::STATUT_ANNULEE => 'Annulee',
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
