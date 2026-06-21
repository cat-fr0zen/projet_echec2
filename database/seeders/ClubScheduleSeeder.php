<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : ClubScheduleSeeder.
 */

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
                'season_label' => "Horaires 2025/2026 - Club d'Échecs",
                'holiday_notice' => "Les horaires peuvent être adaptés les jours fériés. Consultez l'emploi du temps complet avant de vous déplacer.",
                'updated_at' => now(),
            ]
        );

        DB::table('horaire_creneau')->where('schedule_id', 'club_schedule')->delete();

        $items = [
            ['Mardi', '18h00 à 19h30', 'Entraînement ados et adultes débutants', 'Avec Patrick.'],
            ['Mercredi', '17h30 à 18h30', 'Initiation et perfectionnement enfants débutants', 'Avec Ashot et François.'],
            ['Jeudi', '14h00 à 16h30', 'Club Senior +', 'Avec François.'],
            ['Vendredi', '17h00 à 18h00', 'Cours de préparation aux championnats scolaires', "École Sainte-Marie et Collège Saint-Pierre.\nAvec Jean-Patrick à l'école et Ryan au collège."],
            ['Vendredi', '18h00 à 19h30', 'Parties libres tous publics', "Matériel à disposition.\nSalle du restaurant du Café des images."],
            ['Samedi', '10h30 à 12h00', "Groupes selon l'âge et le niveau", 'Centre Socioculturel CAF, 202 Boulevard des Belles Portes, 14200 Hérouville Saint-Clair.'],
            ['Samedi', '14h30 à 16h00', 'Tous publics', 'Parties libres et tournois mensuels au club.'],
        ];

        foreach ($items as $index => [$jour, $horaire, $titre, $details]) {
            [$heureDebut, $heureFin] = $this->decomposerPlageHoraire($horaire);

            DB::table('horaire_creneau')->insert([
                'identifiant_creneau' => 'horaire_seed_' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                'schedule_id' => 'club_schedule',
                'ordre_affichage' => $index + 1,
                'jour' => $jour,
                'heure_debut' => $heureDebut,
                'heure_fin' => $heureFin,
                'titre' => $titre,
                'details' => $details,
                'jour_ferie' => false,
            ]);
        }
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function decomposerPlageHoraire(string $plage): array
    {
        if (preg_match('/(\d{1,2})[h:](\d{2})\s*(?:a|à)\s*(\d{1,2})[h:](\d{2})/iu', $plage, $captures) !== 1) {
            return [null, null];
        }

        return [
            sprintf('%02d:%02d:00', (int) $captures[1], (int) $captures[2]),
            sprintf('%02d:%02d:00', (int) $captures[3], (int) $captures[4]),
        ];
    }
}
