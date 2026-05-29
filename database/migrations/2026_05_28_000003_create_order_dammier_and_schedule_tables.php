<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commande_locale', function (Blueprint $table): void {
            $table->string('identifiant', 40)->primary();
            $table->string('identifiant_utilisateur', 40);
            $table->string('nom_utilisateur', 220);
            $table->string('produit', 160);
            $table->string('categorie', 80);
            $table->string('code_statut', 30)->default('en_attente');
            $table->timestamp('cree_le')->useCurrent();
            $table->timestamp('mis_a_jour_le')->nullable();

            $table->foreign('identifiant_utilisateur')->references('identifiant')->on('compte_membre');
            $table->foreign('code_statut')->references('code_statut')->on('ref_statut_commande');
            $table->index(['identifiant_utilisateur', 'cree_le'], 'ix_commande_utilisateur_date');
        });

        Schema::create('dammier_puzzle', function (Blueprint $table): void {
            $table->string('dammier_id', 60)->primary();
            $table->string('titre', 160);
            $table->string('description', 500)->nullable();
            $table->string('instruction', 500)->nullable();
            $table->string('fen', 120);
            $table->string('trait', 1)->default('w');
            $table->string('solution', 1000)->nullable();
            $table->string('reponses', 1000)->nullable();
            $table->string('indices', 1000)->nullable();
            $table->string('source_puzzle', 80)->default('pool_local');
            $table->boolean('actif')->default(true);
            $table->timestamp('cree_le')->useCurrent();
        });

        Schema::create('dammier_score', function (Blueprint $table): void {
            $table->string('dammier_score_id', 60)->primary();
            $table->string('dammier_week_key', 12);
            $table->string('dammier_puzzle_id', 60);
            $table->string('dammier_user_id', 40);
            $table->string('dammier_display_name', 220);
            $table->unsignedTinyInteger('dammier_moves_count');
            $table->unsignedInteger('dammier_elapsed_seconds');
            $table->timestamp('dammier_solved_at')->useCurrent();

            $table->unique(['dammier_week_key', 'dammier_puzzle_id', 'dammier_user_id'], 'uq_dammier_score_user');
            $table->foreign('dammier_puzzle_id')->references('dammier_id')->on('dammier_puzzle');
            $table->foreign('dammier_user_id')->references('identifiant')->on('compte_membre');
            $table->index(
                ['dammier_week_key', 'dammier_puzzle_id', 'dammier_moves_count', 'dammier_elapsed_seconds'],
                'ix_dammier_score_rank'
            );
        });

        Schema::create('horaire_club', function (Blueprint $table): void {
            $table->string('schedule_id', 40)->primary();
            $table->string('season_label', 120);
            $table->string('holiday_notice', 320)->nullable();
            $table->timestamp('updated_at')->useCurrent();
        });

        Schema::create('horaire_creneau', function (Blueprint $table): void {
            $table->string('identifiant_creneau', 60)->primary();
            $table->string('schedule_id', 40);
            $table->unsignedTinyInteger('ordre_affichage');
            $table->string('jour', 60);
            $table->string('horaire', 80);
            $table->string('titre', 180);
            $table->string('details', 1400)->nullable();
            $table->boolean('jour_ferie')->default(false);

            $table->unique(['schedule_id', 'ordre_affichage'], 'uq_horaire_creneau_ordre');
            $table->foreign('schedule_id')->references('schedule_id')->on('horaire_club')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horaire_creneau');
        Schema::dropIfExists('horaire_club');
        Schema::dropIfExists('dammier_score');
        Schema::dropIfExists('dammier_puzzle');
        Schema::dropIfExists('commande_locale');
    }
};
