<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_cours', function (Blueprint $table): void {
            $table->string('identifiant_document', 40)->primary();
            $table->string('code_rubrique', 40);
            $table->string('titre_document', 160);
            $table->text('description_document')->nullable();
            $table->string('nom_fichier_original', 255);
            $table->string('nom_fichier_stocke', 255)->unique();
            $table->string('chemin_fichier', 255);
            $table->string('type_mime', 120);
            $table->unsignedBigInteger('taille_octets');
            $table->string('identifiant_auteur', 40);
            $table->timestamp('cree_le')->useCurrent();
            $table->timestamp('mis_a_jour_le')->nullable();

            $table->index(['code_rubrique', 'cree_le'], 'ix_document_cours_rubrique_date');
            $table->foreign('identifiant_auteur', 'fk_document_cours_auteur')
                ->references('identifiant')
                ->on('compte_membre')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_cours');
    }
};
