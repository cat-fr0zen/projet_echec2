<?php
/**
 * Ajoute un pseudo Lichess optionnel au profil membre.
 *
 * Cette migration sert surtout aux bases deja existantes.
 * Sur une base neuve, elle complete simplement la structure creee
 * par les migrations initiales.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('compte_membre') || Schema::hasColumn('compte_membre', 'pseudo_lichess')) {
            return;
        }

        Schema::table('compte_membre', function (Blueprint $table): void {
            $table->string('pseudo_lichess', 50)->nullable()->after('pseudo_chess');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('compte_membre') || ! Schema::hasColumn('compte_membre', 'pseudo_lichess')) {
            return;
        }

        Schema::table('compte_membre', function (Blueprint $table): void {
            $table->dropColumn('pseudo_lichess');
        });
    }
};
