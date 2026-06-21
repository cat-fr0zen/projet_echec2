<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : 2026 06 11 000001 etendre commande locale pour panier et paiement.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commande_locale', function (Blueprint $table): void {
            $table->string('lot_commande', 60)->nullable()->after('identifiant');
            $table->string('reference_produit', 80)->nullable()->after('identifiant_utilisateur');
            $table->unsignedSmallInteger('quantite')->default(1)->after('categorie');
            $table->unsignedInteger('prix_unitaire_euros')->nullable()->after('quantite');
            $table->unsignedInteger('prix_total_euros')->nullable()->after('prix_unitaire_euros');
            $table->string('code_mode_paiement', 30)->default('sur_place')->after('code_statut');
            $table->string('code_statut_paiement', 30)->default('a_finaliser')->after('code_mode_paiement');

            $table->index('lot_commande', 'ix_commande_lot');
        });
    }

    public function down(): void
    {
        Schema::table('commande_locale', function (Blueprint $table): void {
            $table->dropIndex('ix_commande_lot');
            $table->dropColumn([
                'lot_commande',
                'reference_produit',
                'quantite',
                'prix_unitaire_euros',
                'prix_total_euros',
                'code_mode_paiement',
                'code_statut_paiement',
            ]);
        });
    }
};
