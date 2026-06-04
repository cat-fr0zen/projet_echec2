<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('constructeur_page_bloc', function (Blueprint $table): void {
            $table->id();
            $table->string('code_page', 60);
            $table->string('code_bloc', 80);
            $table->string('libelle_bloc', 120);
            $table->string('description_bloc', 255)->default('');
            $table->unsignedInteger('ordre_affichage')->default(1);
            $table->boolean('est_actif')->default(true);
            $table->boolean('est_verrouille')->default(false);
            $table->timestamps();

            $table->unique(['code_page', 'code_bloc'], 'constructeur_page_bloc_unique');
            $table->index(['code_page', 'ordre_affichage'], 'constructeur_page_bloc_ordre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('constructeur_page_bloc');
    }
};
