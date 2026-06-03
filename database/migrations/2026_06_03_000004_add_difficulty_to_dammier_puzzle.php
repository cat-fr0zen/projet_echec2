<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ref_difficulte_dammier', function (Blueprint $table): void {
            $table->string('code_difficulte', 20)->primary();
            $table->string('libelle_difficulte', 40);
            $table->unsignedTinyInteger('ordre_affichage')->default(0);
        });

        Schema::table('dammier_puzzle', function (Blueprint $table): void {
            $table->string('code_difficulte', 20)->nullable()->after('source_puzzle');
            $table->foreign('code_difficulte')->references('code_difficulte')->on('ref_difficulte_dammier');
            $table->index(['actif', 'code_difficulte'], 'ix_dammier_actif_difficulte');
        });
    }

    public function down(): void
    {
        Schema::table('dammier_puzzle', function (Blueprint $table): void {
            $table->dropIndex('ix_dammier_actif_difficulte');
            $table->dropForeign(['code_difficulte']);
            $table->dropColumn('code_difficulte');
        });

        Schema::dropIfExists('ref_difficulte_dammier');
    }
};
