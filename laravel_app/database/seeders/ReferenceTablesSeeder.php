<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class ReferenceTablesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('ref_role_compte')->insertOrIgnore([
            ['code_role' => 'connecte', 'libelle_role' => 'Compte connecte', 'niveau_acces' => 10],
            ['code_role' => 'adherent', 'libelle_role' => 'Adherent', 'niveau_acces' => 50],
            ['code_role' => 'admin', 'libelle_role' => 'Administrateur', 'niveau_acces' => 100],
        ]);

        DB::table('ref_statut_compte')->insertOrIgnore([
            ['code_statut' => 'actif', 'libelle_statut' => 'Actif'],
            ['code_statut' => 'suspendu', 'libelle_statut' => 'Suspendu'],
        ]);

        DB::table('ref_statut_adhesion')->insertOrIgnore([
            ['code_statut' => 'aucune', 'libelle_statut' => 'Non adherent'],
            ['code_statut' => 'active', 'libelle_statut' => 'Adhesion active'],
        ]);

        DB::table('ref_statut_publication')->insertOrIgnore([
            ['code_statut' => 'en_attente_validation', 'libelle_statut' => 'En attente'],
            ['code_statut' => 'publie', 'libelle_statut' => 'Publie'],
            ['code_statut' => 'refuse', 'libelle_statut' => 'Refuse'],
        ]);

        DB::table('ref_type_media')->insertOrIgnore([
            ['code_type' => 'photo', 'libelle_type' => 'Photo'],
            ['code_type' => 'video', 'libelle_type' => 'Video'],
        ]);

        DB::table('ref_statut_commande')->insertOrIgnore([
            ['code_statut' => 'en_attente', 'libelle_statut' => 'En attente'],
            ['code_statut' => 'validee', 'libelle_statut' => 'Validee'],
            ['code_statut' => 'annulee', 'libelle_statut' => 'Annulee'],
        ]);

        DB::table('ref_type_bloc_article')->insertOrIgnore([
            ['code_type' => 'paragraphe', 'libelle_type' => 'Paragraphe'],
            ['code_type' => 'sous_titre', 'libelle_type' => 'Sous-titre'],
            ['code_type' => 'image', 'libelle_type' => 'Image'],
            ['code_type' => 'video', 'libelle_type' => 'Video'],
        ]);
    }
}
