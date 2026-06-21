<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : 2026 05 28 000001 create reference tables.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schema_migration', function (Blueprint $table): void {
            $table->string('version_schema', 30)->primary();
            $table->string('nom_migration', 160);
            $table->string('categorie', 60);
            $table->timestamp('applique_le')->useCurrent();
            $table->string('checksum', 128)->nullable();
            $table->string('commentaire', 1000)->nullable();
        });

        Schema::create('audit_changement_base', function (Blueprint $table): void {
            $table->string('identifiant_changement', 80)->primary();
            $table->string('categorie', 60);
            $table->string('operation', 20);
            $table->string('objet_cible', 120);
            $table->string('description', 1000);
            $table->string('demandeur', 120)->nullable();
            $table->string('applique_par', 120)->nullable();
            $table->timestamp('applique_le')->useCurrent();
            $table->string('verification', 1000)->nullable();
            $table->string('rollback_prevu', 1000)->nullable();
            $table->index(['categorie', 'applique_le'], 'ix_audit_categorie_date');
        });

        Schema::create('ref_role_compte', function (Blueprint $table): void {
            $table->string('code_role', 30)->primary();
            $table->string('libelle_role', 80);
            $table->unsignedSmallInteger('niveau_acces')->default(0);
        });

        Schema::create('ref_statut_compte', function (Blueprint $table): void {
            $table->string('code_statut', 30)->primary();
            $table->string('libelle_statut', 80);
        });

        Schema::create('ref_statut_adhesion', function (Blueprint $table): void {
            $table->string('code_statut', 30)->primary();
            $table->string('libelle_statut', 80);
        });

        Schema::create('ref_statut_publication', function (Blueprint $table): void {
            $table->string('code_statut', 40)->primary();
            $table->string('libelle_statut', 80);
        });

        Schema::create('ref_type_media', function (Blueprint $table): void {
            $table->string('code_type', 20)->primary();
            $table->string('libelle_type', 80);
        });

        Schema::create('ref_statut_commande', function (Blueprint $table): void {
            $table->string('code_statut', 30)->primary();
            $table->string('libelle_statut', 80);
        });

        Schema::create('ref_type_bloc_article', function (Blueprint $table): void {
            $table->string('code_type', 30)->primary();
            $table->string('libelle_type', 80);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ref_type_bloc_article');
        Schema::dropIfExists('ref_statut_commande');
        Schema::dropIfExists('ref_type_media');
        Schema::dropIfExists('ref_statut_publication');
        Schema::dropIfExists('ref_statut_adhesion');
        Schema::dropIfExists('ref_statut_compte');
        Schema::dropIfExists('ref_role_compte');
        Schema::dropIfExists('audit_changement_base');
        Schema::dropIfExists('schema_migration');
    }
};
