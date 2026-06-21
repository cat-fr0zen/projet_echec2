<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : 2026 06 03 000005 ajouter role prof et journal visiteur.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('ref_role_compte')->updateOrInsert(
            ['code_role' => 'prof'],
            ['libelle_role' => 'Professeur', 'niveau_acces' => 70]
        );

        Schema::create('journal_visite_visiteur', function (Blueprint $table): void {
            $table->string('identifiant_visite', 40)->primary();
            $table->string('page', 40);
            $table->string('hachage_session', 64)->nullable();
            $table->string('hachage_ip', 64)->nullable();
            $table->string('hote_referent', 120)->nullable();
            $table->string('agent_utilisateur', 255)->nullable();
            $table->timestamp('visite_le')->useCurrent();

            $table->index(['page', 'visite_le'], 'ix_visiteur_page_date');
            $table->index(['hachage_session', 'visite_le'], 'ix_visiteur_session_date');
            $table->index(['visite_le'], 'ix_visiteur_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_visite_visiteur');

        if (Schema::hasTable('compte_membre')) {
            DB::table('compte_membre')
                ->where('code_role', 'prof')
                ->update(['code_role' => 'adherent']);
        }

        DB::table('ref_role_compte')
            ->where('code_role', 'prof')
            ->delete();
    }
};
