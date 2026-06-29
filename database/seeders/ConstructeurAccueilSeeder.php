<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : ConstructeurAccueilSeeder.
 */

declare(strict_types=1);

namespace Database\Seeders;

use App\Repositories\ConstructeurPagesRepository;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class ConstructeurAccueilSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('constructeur_page_bloc')->upsert(
            ConstructeurPagesRepository::definitionsParDefaut('accueil'),
            ['code_page', 'code_bloc'],
            ['libelle_bloc', 'description_bloc', 'ordre_affichage', 'est_actif', 'est_verrouille', 'titre_personnalise', 'contenu_personnalise', 'updated_at']
        );
    }
}
