<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : OrderRepository.
 */

declare(strict_types=1);

namespace App\Repositories;

use App\Models\CommandeLocale;
use App\Support\NomAffichageUtilisateur;
use DateTimeImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Throwable;

final class OrderRepository
{
    private const MODES_PAIEMENT = [
        CommandeLocale::MODE_PAIEMENT_SUR_PLACE,
        CommandeLocale::MODE_PAIEMENT_CARTE_BANCAIRE,
    ];

    private const STATUTS_PAIEMENT = [
        CommandeLocale::STATUT_PAIEMENT_A_FINALISER,
        CommandeLocale::STATUT_PAIEMENT_EN_ATTENTE_PRESTATAIRE,
        CommandeLocale::STATUT_PAIEMENT_REGLE,
        CommandeLocale::STATUT_PAIEMENT_ANNULE,
    ];

    public function listerToutes(): array
    {
        return $this->chargerCommandes($this->requeteCommandes()->orderByDesc('commande_locale.cree_le')->get()->all());
    }

    public function listerParIdentifiantUtilisateur(string $identifiantUtilisateur): array
    {
        return $this->chargerCommandes(
            $this->requeteCommandes()
                ->where('commande_locale.identifiant_utilisateur', $identifiantUtilisateur)
                ->orderByDesc('commande_locale.cree_le')
                ->get()
                ->all()
        );
    }

    public function creer(array $donnees): array
    {
        $identifiant = 'commande_' . bin2hex(random_bytes(8));
        $quantite = max(1, (int) ($donnees['quantite'] ?? 1));
        $prixUnitaire = isset($donnees['prix_unitaire_euros'])
            ? max(0, (int) $donnees['prix_unitaire_euros'])
            : null;
        $prixTotal = isset($donnees['prix_total_euros'])
            ? max(0, (int) $donnees['prix_total_euros'])
            : ($prixUnitaire !== null ? $prixUnitaire * $quantite : null);
        $modePaiement = trim((string) ($donnees['code_mode_paiement'] ?? CommandeLocale::MODE_PAIEMENT_SUR_PLACE));
        $statutPaiement = trim((string) ($donnees['code_statut_paiement'] ?? CommandeLocale::STATUT_PAIEMENT_A_FINALISER));

        if (! in_array($modePaiement, self::MODES_PAIEMENT, true)) {
            $modePaiement = CommandeLocale::MODE_PAIEMENT_SUR_PLACE;
        }

        if (! in_array($statutPaiement, self::STATUTS_PAIEMENT, true)) {
            $statutPaiement = CommandeLocale::STATUT_PAIEMENT_A_FINALISER;
        }

        DB::table('commande_locale')->insert([
            'identifiant' => $identifiant,
            'lot_commande' => $this->normaliserChaineNullable($donnees['lot_commande'] ?? null, 60),
            'identifiant_utilisateur' => (string) ($donnees['identifiant_utilisateur'] ?? ''),
            'reference_produit' => $this->normaliserChaineNullable($donnees['reference_produit'] ?? null, 80),
            'produit' => (string) ($donnees['produit'] ?? ''),
            'categorie' => (string) ($donnees['categorie'] ?? ''),
            'quantite' => $quantite,
            'prix_unitaire_euros' => $prixUnitaire,
            'prix_total_euros' => $prixTotal,
            'code_statut' => CommandeLocale::STATUT_EN_ATTENTE,
            'code_mode_paiement' => $modePaiement,
            'code_statut_paiement' => $statutPaiement,
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
        $row = $this->requeteCommandes()->where('commande_locale.identifiant', $identifiant)->first();

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
        $nomUtilisateur = NomAffichageUtilisateur::depuisValeurs(
            $row['utilisateur_prenom_compte'] ?? '',
            $row['utilisateur_nom_compte'] ?? '',
            $row['utilisateur_courriel_compte'] ?? '',
            'Membre'
        );
        $statut = (string) ($row['code_statut'] ?? CommandeLocale::STATUT_EN_ATTENTE);

        return [
            'identifiant' => (string) ($row['identifiant'] ?? ''),
            'lot_commande' => (string) ($row['lot_commande'] ?? ''),
            'identifiant_utilisateur' => (string) ($row['identifiant_utilisateur'] ?? ''),
            'nom_utilisateur' => $nomUtilisateur,
            'reference_produit' => (string) ($row['reference_produit'] ?? ''),
            'produit' => (string) ($row['produit'] ?? ''),
            'categorie' => (string) ($row['categorie'] ?? ''),
            'quantite' => max(1, (int) ($row['quantite'] ?? 1)),
            'prix_unitaire_euros' => array_key_exists('prix_unitaire_euros', $row) && $row['prix_unitaire_euros'] !== null
                ? (int) $row['prix_unitaire_euros']
                : null,
            'prix_total_euros' => array_key_exists('prix_total_euros', $row) && $row['prix_total_euros'] !== null
                ? (int) $row['prix_total_euros']
                : null,
            'statut' => $statut,
            'libelle_statut' => match ($statut) {
                CommandeLocale::STATUT_VALIDEE => 'Validee',
                CommandeLocale::STATUT_ANNULEE => 'Annulee',
                default => 'En attente',
            },
            'mode_paiement' => $this->normaliserModePaiement($row['code_mode_paiement'] ?? null),
            'libelle_mode_paiement' => $this->libelleModePaiement($row['code_mode_paiement'] ?? null),
            'statut_paiement' => $this->normaliserStatutPaiement($row['code_statut_paiement'] ?? null),
            'libelle_statut_paiement' => $this->libelleStatutPaiement($row['code_statut_paiement'] ?? null),
            'cree_le' => $this->formaterDateIso($row['cree_le'] ?? null),
            'mis_a_jour_le' => $this->formaterDateIso($row['mis_a_jour_le'] ?? null),
        ];
    }

    private function requeteCommandes(): Builder
    {
        return DB::table('commande_locale')
            ->leftJoin('compte_membre as utilisateur', 'utilisateur.identifiant', '=', 'commande_locale.identifiant_utilisateur')
            ->select(
                'commande_locale.*',
                'utilisateur.nom as utilisateur_nom_compte',
                'utilisateur.prenom as utilisateur_prenom_compte',
                'utilisateur.courriel as utilisateur_courriel_compte'
            );
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

    private function normaliserModePaiement(mixed $mode): string
    {
        $modeNormalise = trim((string) $mode);

        return in_array($modeNormalise, self::MODES_PAIEMENT, true)
            ? $modeNormalise
            : CommandeLocale::MODE_PAIEMENT_SUR_PLACE;
    }

    private function libelleModePaiement(mixed $mode): string
    {
        return match ($this->normaliserModePaiement($mode)) {
            CommandeLocale::MODE_PAIEMENT_CARTE_BANCAIRE => 'Carte bancaire',
            default => 'Reglement au club',
        };
    }

    private function normaliserStatutPaiement(mixed $statut): string
    {
        $statutNormalise = trim((string) $statut);

        return in_array($statutNormalise, self::STATUTS_PAIEMENT, true)
            ? $statutNormalise
            : CommandeLocale::STATUT_PAIEMENT_A_FINALISER;
    }

    private function libelleStatutPaiement(mixed $statut): string
    {
        return match ($this->normaliserStatutPaiement($statut)) {
            CommandeLocale::STATUT_PAIEMENT_EN_ATTENTE_PRESTATAIRE => 'En attente CB',
            CommandeLocale::STATUT_PAIEMENT_REGLE => 'Regle',
            CommandeLocale::STATUT_PAIEMENT_ANNULE => 'Annule',
            default => 'A finaliser',
        };
    }

    private function normaliserChaineNullable(mixed $valeur, int $tailleMax): ?string
    {
        $chaine = trim((string) $valeur);

        if ($chaine === '') {
            return null;
        }

        return mb_substr($chaine, 0, $tailleMax);
    }
}
