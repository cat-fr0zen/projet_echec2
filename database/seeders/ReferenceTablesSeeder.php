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

        DB::table('ref_statut_newsletter_abonnement')->insertOrIgnore([
            ['code_statut' => 'actif', 'libelle_statut' => 'Actif'],
            ['code_statut' => 'desabonne', 'libelle_statut' => 'Desabonne'],
        ]);

        DB::table('ref_type_evenement_newsletter')->insertOrIgnore([
            ['code_type_evenement' => 'article', 'libelle_type_evenement' => 'Article'],
            ['code_type_evenement' => 'cours', 'libelle_type_evenement' => 'Cours'],
            ['code_type_evenement' => 'boutique', 'libelle_type_evenement' => 'Boutique'],
            ['code_type_evenement' => 'confirmation', 'libelle_type_evenement' => 'Confirmation'],
        ]);

        DB::table('ref_statut_envoi_newsletter')->insertOrIgnore([
            ['code_statut_envoi' => 'envoye', 'libelle_statut_envoi' => 'Envoye'],
            ['code_statut_envoi' => 'echec', 'libelle_statut_envoi' => 'Echec'],
            ['code_statut_envoi' => 'ignore', 'libelle_statut_envoi' => 'Ignore'],
        ]);

        DB::table('ref_difficulte_dammier')->insertOrIgnore([
            ['code_difficulte' => 'facile', 'libelle_difficulte' => 'Facile', 'ordre_affichage' => 1],
            ['code_difficulte' => 'medium', 'libelle_difficulte' => 'Medium', 'ordre_affichage' => 2],
            ['code_difficulte' => 'difficile', 'libelle_difficulte' => 'Difficile', 'ordre_affichage' => 3],
            ['code_difficulte' => 'extreme', 'libelle_difficulte' => 'Extreme', 'ordre_affichage' => 4],
        ]);
    }
}
