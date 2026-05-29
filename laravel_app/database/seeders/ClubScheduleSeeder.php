<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class ClubScheduleSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('horaire_club')->updateOrInsert(
            ['schedule_id' => 'club_schedule'],
            [
                'season_label' => "Horaires 2025/2026 - Club d'Echecs",
                'holiday_notice' => "Les horaires peuvent etre adaptes les jours feries. Consultez l'emploi du temps complet avant de vous deplacer.",
                'updated_at' => now(),
            ]
        );

        DB::table('horaire_creneau')->where('schedule_id', 'club_schedule')->delete();

        $items = [
            ['Mardi', '18h00 a 19h30', 'Entrainement ados et adultes debutants', 'Avec Patrick.'],
            ['Mercredi', '17h30 a 18h30', 'Initiation et perfectionnement enfants debutants', 'Avec Ashot et Francois.'],
            ['Jeudi', '14h00 a 16h30', 'Club Senior +', 'Avec Francois.'],
            ['Vendredi', '17h00 a 18h00', 'Cours preparation aux championnats scolaires', "Ecole Sainte-Marie et College Saint-Pierre.\nAvec Jean-Patrick a l'ecole et Ryan au college."],
            ['Vendredi', '18h00 a 19h30', 'Parties libres tous publics', "Materiel a disposition.\nSalle du restaurant du Cafe des images."],
            ['Samedi', '10h30 a 12h00', "Groupes selon l'age et le niveau", 'Centre Socioculturel CAF, 202 Boulevard des Belles Portes, 14200 Herouville Saint-Clair.'],
            ['Samedi', '14h30 a 16h00', 'Tous publics', 'Parties libres et tournois mensuels au club.'],
        ];

        foreach ($items as $index => [$jour, $horaire, $titre, $details]) {
            DB::table('horaire_creneau')->insert([
                'identifiant_creneau' => 'horaire_seed_' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                'schedule_id' => 'club_schedule',
                'ordre_affichage' => $index + 1,
                'jour' => $jour,
                'horaire' => $horaire,
                'titre' => $titre,
                'details' => $details,
                'jour_ferie' => false,
            ]);
        }
    }
}
