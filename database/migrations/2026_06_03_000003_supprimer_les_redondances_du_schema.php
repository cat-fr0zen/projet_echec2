<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : 2026 06 03 000003 supprimer les redondances du schema.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('article', function (Blueprint $table): void {
            $table->dropColumn(['nom_auteur', 'auteur_affiche', 'contenu']);
        });

        Schema::table('media_publication', function (Blueprint $table): void {
            $table->dropColumn('nom_auteur');
        });

        Schema::table('commande_locale', function (Blueprint $table): void {
            $table->dropColumn('nom_utilisateur');
        });

        Schema::table('dammier_score', function (Blueprint $table): void {
            $table->dropColumn('dammier_display_name');
        });

        Schema::table('dammier_puzzle', function (Blueprint $table): void {
            $table->dropColumn(['solution', 'reponses', 'indices']);
        });

        Schema::table('newsletter_abonnement', function (Blueprint $table): void {
            $table->dropIndex('ix_newsletter_statut');
            $table->dropColumn('statut');
        });

        Schema::table('newsletter_envoi', function (Blueprint $table): void {
            $table->dropIndex('ix_newsletter_envoi_event');
            $table->dropColumn(['type_evenement', 'statut_envoi']);
        });

        Schema::table('horaire_creneau', function (Blueprint $table): void {
            $table->dropColumn('horaire');
        });

        Schema::table('compte_membre', function (Blueprint $table): void {
            $table->dropColumn('date_naissance');
        });

        Schema::table('compte_membre', function (Blueprint $table): void {
            $table->renameColumn('date_naissance_normalisee', 'date_naissance');
        });
    }

    public function down(): void
    {
        Schema::table('compte_membre', function (Blueprint $table): void {
            $table->renameColumn('date_naissance', 'date_naissance_normalisee');
            $table->string('date_naissance', 10)->nullable();
        });

        Schema::table('horaire_creneau', function (Blueprint $table): void {
            $table->string('horaire', 80)->nullable();
        });

        Schema::table('newsletter_envoi', function (Blueprint $table): void {
            $table->string('type_evenement', 30)->nullable();
            $table->string('statut_envoi', 20)->nullable();
        });

        Schema::table('newsletter_abonnement', function (Blueprint $table): void {
            $table->string('statut', 20)->nullable();
        });

        Schema::table('dammier_puzzle', function (Blueprint $table): void {
            $table->string('solution', 1000)->nullable();
            $table->string('reponses', 1000)->nullable();
            $table->string('indices', 1000)->nullable();
        });

        Schema::table('dammier_score', function (Blueprint $table): void {
            $table->string('dammier_display_name', 220)->nullable();
        });

        Schema::table('commande_locale', function (Blueprint $table): void {
            $table->string('nom_utilisateur', 220)->nullable();
        });

        Schema::table('media_publication', function (Blueprint $table): void {
            $table->string('nom_auteur', 220)->nullable();
        });

        Schema::table('article', function (Blueprint $table): void {
            $table->string('nom_auteur', 220)->nullable();
            $table->string('auteur_affiche', 120)->nullable();
            $table->text('contenu')->nullable();
        });

        $utilisateurs = DB::table('compte_membre')
            ->select(['identifiant', 'nom', 'prenom', 'courriel', 'date_naissance_normalisee'])
            ->get()
            ->keyBy('identifiant');

        foreach (DB::table('article')->select(['identifiant', 'identifiant_auteur', 'contenu_plat_cache'])->get() as $article) {
            $auteur = $utilisateurs->get($article->identifiant_auteur);
            $nomAuteur = $this->construireNomAffichage($auteur?->prenom ?? '', $auteur?->nom ?? '', $auteur?->courriel ?? '');

            DB::table('article')
                ->where('identifiant', $article->identifiant)
                ->update([
                    'nom_auteur' => $nomAuteur,
                    'auteur_affiche' => $nomAuteur,
                    'contenu' => (string) ($article->contenu_plat_cache ?? ''),
                ]);
        }

        foreach (DB::table('media_publication')->select(['identifiant', 'identifiant_auteur'])->get() as $media) {
            $auteur = $utilisateurs->get($media->identifiant_auteur);
            $nomAuteur = $this->construireNomAffichage($auteur?->prenom ?? '', $auteur?->nom ?? '', $auteur?->courriel ?? '');

            DB::table('media_publication')
                ->where('identifiant', $media->identifiant)
                ->update(['nom_auteur' => $nomAuteur]);
        }

        foreach (DB::table('commande_locale')->select(['identifiant', 'identifiant_utilisateur'])->get() as $commande) {
            $utilisateur = $utilisateurs->get($commande->identifiant_utilisateur);
            $nomUtilisateur = $this->construireNomAffichage($utilisateur?->prenom ?? '', $utilisateur?->nom ?? '', $utilisateur?->courriel ?? '');

            DB::table('commande_locale')
                ->where('identifiant', $commande->identifiant)
                ->update(['nom_utilisateur' => $nomUtilisateur]);
        }

        foreach (DB::table('dammier_score')->select(['dammier_score_id', 'dammier_user_id'])->get() as $score) {
            $utilisateur = $utilisateurs->get($score->dammier_user_id);
            $nomUtilisateur = $this->construireNomAffichage($utilisateur?->prenom ?? '', $utilisateur?->nom ?? '', $utilisateur?->courriel ?? '');

            DB::table('dammier_score')
                ->where('dammier_score_id', $score->dammier_score_id)
                ->update(['dammier_display_name' => $nomUtilisateur]);
        }

        foreach (DB::table('dammier_puzzle')->select(['dammier_id'])->get() as $puzzle) {
            $puzzleId = (string) $puzzle->dammier_id;

            DB::table('dammier_puzzle')
                ->where('dammier_id', $puzzleId)
                ->update([
                    'solution' => $this->joindreValeurs(
                        DB::table('dammier_solution_etape')
                            ->where('dammier_puzzle_id', $puzzleId)
                            ->orderBy('ordre_etape')
                            ->pluck('coup')
                            ->all()
                    ),
                    'reponses' => $this->joindreValeurs(
                        DB::table('dammier_reponse_attendue')
                            ->where('dammier_puzzle_id', $puzzleId)
                            ->orderBy('ordre_reponse')
                            ->pluck('coup')
                            ->all()
                    ),
                    'indices' => $this->joindreValeurs(
                        DB::table('dammier_indice')
                            ->where('dammier_puzzle_id', $puzzleId)
                            ->orderBy('ordre_indice')
                            ->pluck('texte_indice')
                            ->all()
                    ),
                ]);
        }

        foreach (DB::table('newsletter_abonnement')->select(['identifiant_abonnement', 'code_statut'])->get() as $abonnement) {
            DB::table('newsletter_abonnement')
                ->where('identifiant_abonnement', $abonnement->identifiant_abonnement)
                ->update([
                    'statut' => (string) ($abonnement->code_statut ?? 'actif'),
                ]);
        }

        foreach (DB::table('newsletter_envoi')->select(['identifiant_envoi', 'code_type_evenement', 'code_statut_envoi'])->get() as $envoi) {
            DB::table('newsletter_envoi')
                ->where('identifiant_envoi', $envoi->identifiant_envoi)
                ->update([
                    'type_evenement' => (string) ($envoi->code_type_evenement ?? 'confirmation'),
                    'statut_envoi' => (string) ($envoi->code_statut_envoi ?? 'ignore'),
                ]);
        }

        foreach (DB::table('horaire_creneau')->select(['identifiant_creneau', 'heure_debut', 'heure_fin'])->get() as $creneau) {
            DB::table('horaire_creneau')
                ->where('identifiant_creneau', $creneau->identifiant_creneau)
                ->update([
                    'horaire' => $this->formaterPlageHoraire(
                        $creneau->heure_debut !== null ? (string) $creneau->heure_debut : null,
                        $creneau->heure_fin !== null ? (string) $creneau->heure_fin : null
                    ),
                ]);
        }

        foreach ($utilisateurs as $identifiant => $utilisateur) {
            DB::table('compte_membre')
                ->where('identifiant', $identifiant)
                ->update([
                    'date_naissance' => $utilisateur->date_naissance_normalisee !== null
                        ? substr((string) $utilisateur->date_naissance_normalisee, 0, 10)
                        : null,
                ]);
        }

        Schema::table('newsletter_abonnement', function (Blueprint $table): void {
            $table->index(['statut', 'cree_le'], 'ix_newsletter_statut');
        });

        Schema::table('newsletter_envoi', function (Blueprint $table): void {
            $table->index(['type_evenement', 'envoye_le'], 'ix_newsletter_envoi_event');
        });
    }

    private function construireNomAffichage(string $prenom, string $nom, string $courriel): string
    {
        $nomComplet = trim($prenom . ' ' . $nom);

        return $nomComplet !== '' ? $nomComplet : ($courriel !== '' ? $courriel : 'Membre');
    }

    /**
     * @param array<int, mixed> $valeurs
     */
    private function joindreValeurs(array $valeurs): string
    {
        return implode(', ', array_values(array_filter(
            array_map(static fn (mixed $valeur): string => trim((string) $valeur), $valeurs),
            static fn (string $valeur): bool => $valeur !== ''
        )));
    }

    private function formaterPlageHoraire(?string $heureDebut, ?string $heureFin): ?string
    {
        if ($heureDebut === null || $heureFin === null) {
            return null;
        }

        return sprintf(
            '%sh%s a %sh%s',
            substr($heureDebut, 0, 2),
            substr($heureDebut, 3, 2),
            substr($heureFin, 0, 2),
            substr($heureFin, 3, 2)
        );
    }
};
