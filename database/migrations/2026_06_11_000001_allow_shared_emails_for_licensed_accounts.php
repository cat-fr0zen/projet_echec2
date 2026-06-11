<?php

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
