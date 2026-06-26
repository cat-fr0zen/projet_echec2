<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : add membership renewal tracking.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compte_membre', function (Blueprint $table): void {
            $table->string('saison_adhesion_active', 9)->nullable()->after('code_statut_adhesion');
            $table->string('saison_relance_adhesion', 9)->nullable()->after('saison_adhesion_active');
            $table->timestamp('adhesion_renouvelee_le')->nullable()->after('saison_relance_adhesion');
        });
    }

    public function down(): void
    {
        Schema::table('compte_membre', function (Blueprint $table): void {
            $table->dropColumn([
                'saison_adhesion_active',
                'saison_relance_adhesion',
                'adhesion_renouvelee_le',
            ]);
        });
    }
};
