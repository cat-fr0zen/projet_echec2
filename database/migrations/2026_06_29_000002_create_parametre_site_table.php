<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : 2026 06 29 000002 create parametre site table.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parametre_site', function (Blueprint $table): void {
            $table->string('cle_parametre', 120)->primary();
            $table->text('valeur_texte')->nullable();
            $table->timestamp('cree_le')->useCurrent();
            $table->timestamp('mis_a_jour_le')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parametre_site');
    }
};
