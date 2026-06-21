<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : 2026 06 11 000001 allow shared emails for licensed accounts.
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
            $table->dropUnique(['courriel_normalise']);
            $table->index('courriel_normalise', 'ix_compte_membre_courriel_normalise');
        });
    }

    public function down(): void
    {
        Schema::table('compte_membre', function (Blueprint $table): void {
            $table->dropIndex('ix_compte_membre_courriel_normalise');
            $table->unique('courriel_normalise');
        });
    }
};
