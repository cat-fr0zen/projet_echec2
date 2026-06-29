<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : 2026 06 29 000003 create bureau membre table.
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
        Schema::create('bureau_membre', function (Blueprint $table): void {
            $table->string('identifiant_membre_bureau', 40)->primary();
            $table->string('prenom', 100);
            $table->string('nom', 100)->default('');
            $table->string('role_affiche', 160)->default('');
            $table->string('description', 1200)->default('');
            $table->string('photo_url', 2048)->default('');
            $table->unsignedSmallInteger('ordre_affichage')->default(1);
            $table->boolean('est_actif')->default(true);
            $table->timestamp('cree_le')->useCurrent();
            $table->timestamp('mis_a_jour_le')->nullable();
        });

        $instant = now()->format('Y-m-d H:i:s');

        DB::table('bureau_membre')->insert([
            [
                'identifiant_membre_bureau' => 'bureau_jeanpatrick',
                'prenom' => 'Jean-Patrick',
                'nom' => 'JORON',
                'role_affiche' => 'Président',
                'description' => "Pilote la vie du club, coordonne les décisions associatives et représente officiellement les Cavaliers d'Hérouville.",
                'photo_url' => '',
                'ordre_affichage' => 1,
                'est_actif' => true,
                'cree_le' => $instant,
                'mis_a_jour_le' => $instant,
            ],
            [
                'identifiant_membre_bureau' => 'bureau_francois',
                'prenom' => 'Francois',
                'nom' => '',
                'role_affiche' => 'Vice-président',
                'description' => "Accompagne l'organisation des activités, le suivi des groupes et la continuité des actions du club.",
                'photo_url' => '',
                'ordre_affichage' => 2,
                'est_actif' => true,
                'cree_le' => $instant,
                'mis_a_jour_le' => $instant,
            ],
            [
                'identifiant_membre_bureau' => 'bureau_ashot',
                'prenom' => 'Ashot',
                'nom' => '',
                'role_affiche' => 'Professeur / encadrant',
                'description' => "Intervient sur l'accompagnement pédagogique, l'initiation et la progression des jeunes joueurs du club.",
                'photo_url' => '',
                'ordre_affichage' => 3,
                'est_actif' => true,
                'cree_le' => $instant,
                'mis_a_jour_le' => $instant,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('bureau_membre');
    }
};
