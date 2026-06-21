<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : 2026 06 12 000001 add grouping columns to document cours table.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_cours', function (Blueprint $table): void {
            if (! Schema::hasColumn('document_cours', 'groupe_document')) {
                $table->string('groupe_document', 160)->nullable();
            }

            if (! Schema::hasColumn('document_cours', 'sous_groupe_document')) {
                $table->string('sous_groupe_document', 160)->nullable();
            }

            if (! Schema::hasColumn('document_cours', 'chemin_source_interne')) {
                $table->string('chemin_source_interne', 255)->nullable();
            }
        });

        Schema::table('document_cours', function (Blueprint $table): void {
            $table->unique('chemin_source_interne', 'ux_document_cours_source_interne');
            $table->index(
                ['code_rubrique', 'groupe_document', 'sous_groupe_document'],
                'ix_document_cours_rubrique_groupe'
            );
        });
    }

    public function down(): void
    {
        Schema::table('document_cours', function (Blueprint $table): void {
            $table->dropIndex('ix_document_cours_rubrique_groupe');
            $table->dropUnique('ux_document_cours_source_interne');
        });

        Schema::table('document_cours', function (Blueprint $table): void {
            if (Schema::hasColumn('document_cours', 'chemin_source_interne')) {
                $table->dropColumn('chemin_source_interne');
            }

            if (Schema::hasColumn('document_cours', 'sous_groupe_document')) {
                $table->dropColumn('sous_groupe_document');
            }

            if (Schema::hasColumn('document_cours', 'groupe_document')) {
                $table->dropColumn('groupe_document');
            }
        });
    }
};
