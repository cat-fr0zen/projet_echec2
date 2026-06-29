<?php
/**
 * Ajoute un titre et un texte personnalisables aux blocs du constructeur d'accueil.
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
        Schema::table('constructeur_page_bloc', function (Blueprint $table): void {
            $table->string('titre_personnalise', 160)->default('')->after('est_verrouille');
            $table->text('contenu_personnalise')->nullable()->after('titre_personnalise');
        });

        DB::table('constructeur_page_bloc')
            ->where('code_page', 'accueil')
            ->where('code_bloc', 'mot_du_club')
            ->update([
                'titre_personnalise' => 'Présentation',
                'contenu_personnalise' => "Bienvenue chez Les Cavaliers d'Hérouville, un club d'échecs pas comme les autres ! Notre mission ? Faire découvrir et partager la passion du jeu d'échecs à tous. Des 5 ans jusqu'à 105 ans. Débutants curieux ou pros de la stratégie. Convivialité, apprentissage, progression... le tout dans la bonne humeur ! Que vous vouliez apprendre, progresser ou simplement jouer pour le plaisir... Venez faire travailler vos neurones dans une ambiance chaleureuse et stimulante ! Rejoignez-nous et faites partie d'une communauté passionnée !",
            ]);
    }

    public function down(): void
    {
        Schema::table('constructeur_page_bloc', function (Blueprint $table): void {
            $table->dropColumn(['titre_personnalise', 'contenu_personnalise']);
        });
    }
};
