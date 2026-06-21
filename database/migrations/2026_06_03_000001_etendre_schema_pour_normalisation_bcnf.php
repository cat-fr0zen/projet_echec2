<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : 2026 06 03 000001 etendre schema pour normalisation bcnf.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ref_statut_newsletter_abonnement', function (Blueprint $table): void {
            $table->string('code_statut', 30)->primary();
            $table->string('libelle_statut', 80);
        });

        Schema::create('ref_type_evenement_newsletter', function (Blueprint $table): void {
            $table->string('code_type_evenement', 30)->primary();
            $table->string('libelle_type_evenement', 80);
        });

        Schema::create('ref_statut_envoi_newsletter', function (Blueprint $table): void {
            $table->string('code_statut_envoi', 30)->primary();
            $table->string('libelle_statut_envoi', 80);
        });

        Schema::create('dammier_solution_etape', function (Blueprint $table): void {
            $table->string('identifiant_etape', 80)->primary();
            $table->string('dammier_puzzle_id', 60);
            $table->unsignedSmallInteger('ordre_etape');
            $table->string('coup', 40);

            $table->unique(['dammier_puzzle_id', 'ordre_etape'], 'uq_dammier_solution_ordre');
            $table->foreign('dammier_puzzle_id')->references('dammier_id')->on('dammier_puzzle')->cascadeOnDelete();
        });

        Schema::create('dammier_reponse_attendue', function (Blueprint $table): void {
            $table->string('identifiant_reponse', 80)->primary();
            $table->string('dammier_puzzle_id', 60);
            $table->unsignedSmallInteger('ordre_reponse');
            $table->string('coup', 40);

            $table->unique(['dammier_puzzle_id', 'ordre_reponse'], 'uq_dammier_reponse_ordre');
            $table->foreign('dammier_puzzle_id')->references('dammier_id')->on('dammier_puzzle')->cascadeOnDelete();
        });

        Schema::create('dammier_indice', function (Blueprint $table): void {
            $table->string('identifiant_indice', 80)->primary();
            $table->string('dammier_puzzle_id', 60);
            $table->unsignedSmallInteger('ordre_indice');
            $table->string('texte_indice', 500);

            $table->unique(['dammier_puzzle_id', 'ordre_indice'], 'uq_dammier_indice_ordre');
            $table->foreign('dammier_puzzle_id')->references('dammier_id')->on('dammier_puzzle')->cascadeOnDelete();
        });

        Schema::table('compte_membre', function (Blueprint $table): void {
            $table->date('date_naissance_normalisee')->nullable();
        });

        Schema::table('article', function (Blueprint $table): void {
            $table->text('contenu_plat_cache')->nullable();
        });

        Schema::table('newsletter_abonnement', function (Blueprint $table): void {
            $table->string('code_statut', 30)->nullable();
            $table->foreign('code_statut')->references('code_statut')->on('ref_statut_newsletter_abonnement');
            $table->index(['code_statut', 'cree_le'], 'ix_newsletter_code_statut');
        });

        Schema::table('newsletter_envoi', function (Blueprint $table): void {
            $table->string('code_type_evenement', 30)->nullable();
            $table->string('code_statut_envoi', 30)->nullable();
            $table->foreign('code_type_evenement')->references('code_type_evenement')->on('ref_type_evenement_newsletter');
            $table->foreign('code_statut_envoi')->references('code_statut_envoi')->on('ref_statut_envoi_newsletter');
            $table->index(['code_type_evenement', 'envoye_le'], 'ix_newsletter_envoi_code_type');
        });

        Schema::table('horaire_creneau', function (Blueprint $table): void {
            $table->time('heure_debut')->nullable();
            $table->time('heure_fin')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('horaire_creneau', function (Blueprint $table): void {
            $table->dropColumn(['heure_debut', 'heure_fin']);
        });

        Schema::table('newsletter_envoi', function (Blueprint $table): void {
            $table->dropIndex('ix_newsletter_envoi_code_type');
            $table->dropForeign(['code_type_evenement']);
            $table->dropForeign(['code_statut_envoi']);
            $table->dropColumn(['code_type_evenement', 'code_statut_envoi']);
        });

        Schema::table('newsletter_abonnement', function (Blueprint $table): void {
            $table->dropIndex('ix_newsletter_code_statut');
            $table->dropForeign(['code_statut']);
            $table->dropColumn('code_statut');
        });

        Schema::table('article', function (Blueprint $table): void {
            $table->dropColumn('contenu_plat_cache');
        });

        Schema::table('compte_membre', function (Blueprint $table): void {
            $table->dropColumn('date_naissance_normalisee');
        });

        Schema::dropIfExists('dammier_indice');
        Schema::dropIfExists('dammier_reponse_attendue');
        Schema::dropIfExists('dammier_solution_etape');
        Schema::dropIfExists('ref_statut_envoi_newsletter');
        Schema::dropIfExists('ref_type_evenement_newsletter');
        Schema::dropIfExists('ref_statut_newsletter_abonnement');
    }
};
