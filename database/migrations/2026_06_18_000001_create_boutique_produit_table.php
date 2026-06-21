<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : 2026 06 18 000001 create boutique produit table.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boutique_produit', function (Blueprint $table): void {
            $table->string('identifiant_produit', 40)->primary();
            $table->string('reference_produit', 80)->unique();
            $table->string('titre_produit', 160);
            $table->string('categorie_produit', 40);
            $table->string('public_cible', 40)->default('tous');
            $table->unsignedInteger('prix_euros')->default(0);
            $table->string('badge', 80)->nullable();
            $table->string('mode_vente', 30)->default('reservation');
            $table->text('texte_produit')->nullable();
            $table->text('resume_produit')->nullable();
            $table->text('avantages_json')->nullable();
            $table->unsignedSmallInteger('ordre_affichage')->default(1);
            $table->boolean('est_en_stock')->default(true);
            $table->boolean('est_actif')->default(true);
            $table->string('identifiant_auteur', 40);
            $table->timestamp('cree_le')->useCurrent();
            $table->timestamp('mis_a_jour_le')->nullable();

            $table->index(['est_actif', 'ordre_affichage'], 'ix_boutique_produit_actif_ordre');
            $table->index(['categorie_produit', 'public_cible'], 'ix_boutique_produit_categorie_public');
            $table->foreign('identifiant_auteur', 'fk_boutique_produit_auteur')
                ->references('identifiant')
                ->on('compte_membre')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boutique_produit');
    }
};
