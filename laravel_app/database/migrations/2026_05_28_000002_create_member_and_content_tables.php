<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compte_membre', function (Blueprint $table): void {
            $table->string('identifiant', 40)->primary();
            $table->string('nom', 100);
            $table->string('prenom', 100);
            $table->string('date_naissance', 10)->nullable();
            $table->string('courriel', 254);
            $table->string('courriel_normalise', 254)->unique();
            $table->string('numero_licence_federale', 30)->nullable()->unique();
            $table->string('mot_de_passe_hache', 255);
            $table->string('description_profil', 1200)->nullable();
            $table->string('pseudo_chess', 50)->nullable();
            $table->string('code_role', 30)->default('connecte');
            $table->string('code_statut_compte', 30)->default('actif');
            $table->string('code_statut_adhesion', 30)->default('aucune');
            $table->timestamp('cree_le')->useCurrent();
            $table->timestamp('mis_a_jour_le')->nullable();

            $table->foreign('code_role')->references('code_role')->on('ref_role_compte');
            $table->foreign('code_statut_compte')->references('code_statut')->on('ref_statut_compte');
            $table->foreign('code_statut_adhesion')->references('code_statut')->on('ref_statut_adhesion');
        });

        Schema::create('article', function (Blueprint $table): void {
            $table->string('identifiant', 40)->primary();
            $table->string('identifiant_auteur', 40);
            $table->string('nom_auteur', 220);
            $table->string('auteur_affiche', 120);
            $table->string('titre', 150);
            $table->string('resume', 500)->nullable();
            $table->text('contenu')->nullable();
            $table->string('code_statut', 40)->default('en_attente_validation');
            $table->timestamp('cree_le')->useCurrent();
            $table->timestamp('mis_a_jour_le')->nullable();

            $table->foreign('identifiant_auteur')->references('identifiant')->on('compte_membre');
            $table->foreign('code_statut')->references('code_statut')->on('ref_statut_publication');
            $table->index(['code_statut', 'cree_le'], 'ix_article_statut_date');
            $table->index(['identifiant_auteur', 'cree_le'], 'ix_article_auteur_date');
        });

        Schema::create('article_bloc', function (Blueprint $table): void {
            $table->string('identifiant_bloc', 50)->primary();
            $table->string('identifiant_article', 40);
            $table->unsignedSmallInteger('ordre_affichage');
            $table->string('code_type', 30);
            $table->text('texte')->nullable();
            $table->string('chemin_public', 500)->nullable();
            $table->string('type_mime', 120)->nullable();
            $table->string('texte_alternatif', 180)->nullable();
            $table->string('legende', 220)->nullable();
            $table->string('nom_fichier_original', 255)->nullable();
            $table->unsignedBigInteger('taille_octets')->default(0);

            $table->unique(['identifiant_article', 'ordre_affichage'], 'uq_article_bloc_ordre');
            $table->foreign('identifiant_article')->references('identifiant')->on('article')->cascadeOnDelete();
            $table->foreign('code_type')->references('code_type')->on('ref_type_bloc_article');
        });

        Schema::create('media_publication', function (Blueprint $table): void {
            $table->string('identifiant', 40)->primary();
            $table->string('identifiant_auteur', 40);
            $table->string('nom_auteur', 220);
            $table->string('code_type_media', 20);
            $table->string('titre', 150);
            $table->string('description', 500)->nullable();
            $table->string('nom_fichier_original', 255);
            $table->string('nom_fichier_stocke', 255);
            $table->string('chemin_public', 500);
            $table->string('type_mime', 120);
            $table->unsignedBigInteger('taille_octets');
            $table->string('code_statut', 40)->default('en_attente_validation');
            $table->timestamp('cree_le')->useCurrent();
            $table->timestamp('mis_a_jour_le')->nullable();

            $table->foreign('identifiant_auteur')->references('identifiant')->on('compte_membre');
            $table->foreign('code_type_media')->references('code_type')->on('ref_type_media');
            $table->foreign('code_statut')->references('code_statut')->on('ref_statut_publication');
            $table->index(['code_statut', 'cree_le'], 'ix_media_statut_date');
            $table->index(['identifiant_auteur', 'cree_le'], 'ix_media_auteur_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_publication');
        Schema::dropIfExists('article_bloc');
        Schema::dropIfExists('article');
        Schema::dropIfExists('compte_membre');
    }
};
