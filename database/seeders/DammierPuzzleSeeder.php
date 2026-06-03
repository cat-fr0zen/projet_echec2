<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class DammierPuzzleSeeder extends Seeder
{
    public function run(): void
    {
        $puzzles = [
            [
                'id' => 'puzzle_mate_dame_g7',
                'titre' => 'Mat en 1 : dame vers g7',
                'description' => 'Les Blancs jouent et mattent en un coup.',
                'instruction' => 'Cherche un coup de dame protege par le roi blanc.',
                'fen' => '7k/8/5KQ1/8/8/8/8/8 w - - 0 1',
                'trait' => 'w',
                'source_puzzle' => 'seed_local',
                'solution' => ['g6g7'],
                'reponses' => [],
                'indices' => ['La case finale de la dame doit etre couverte par ton roi.'],
            ],
            [
                'id' => 'puzzle_mate_tour_h8',
                'titre' => 'Mat en 1 : tour vers h8',
                'description' => 'Les Blancs jouent et mattent en un coup.',
                'instruction' => 'La tour doit fermer toute la 8e rangee.',
                'fen' => 'k7/8/1K6/8/8/8/8/7R w - - 0 1',
                'trait' => 'w',
                'source_puzzle' => 'seed_local',
                'solution' => ['h1h8'],
                'reponses' => [],
                'indices' => ['La tour doit donner echec de loin sans laisser de case de fuite.'],
            ],
            [
                'id' => 'puzzle_mate_dame_b7',
                'titre' => 'Mat en 1 : dame vers b7',
                'description' => 'Les Blancs jouent et mattent en un coup.',
                'instruction' => 'Observe la diagonale du roi noir et les cases de fuite.',
                'fen' => 'k7/8/1QK5/8/8/8/8/8 w - - 0 1',
                'trait' => 'w',
                'source_puzzle' => 'seed_local',
                'solution' => ['b6b7'],
                'reponses' => [],
                'indices' => ['La dame peut mater si elle reste protegee par le roi blanc.'],
            ],
        ];

        foreach ($puzzles as $puzzle) {
            DB::table('dammier_puzzle')->updateOrInsert(
                ['dammier_id' => $puzzle['id']],
                [
                    'titre' => $puzzle['titre'],
                    'description' => $puzzle['description'],
                    'instruction' => $puzzle['instruction'],
                    'fen' => $puzzle['fen'],
                    'trait' => $puzzle['trait'],
                    'source_puzzle' => $puzzle['source_puzzle'],
                    'actif' => true,
                    'cree_le' => now(),
                ]
            );

            DB::table('dammier_solution_etape')->where('dammier_puzzle_id', $puzzle['id'])->delete();
            DB::table('dammier_reponse_attendue')->where('dammier_puzzle_id', $puzzle['id'])->delete();
            DB::table('dammier_indice')->where('dammier_puzzle_id', $puzzle['id'])->delete();

            foreach ($puzzle['solution'] as $index => $coup) {
                DB::table('dammier_solution_etape')->insert([
                    'identifiant_etape' => sprintf('%s_solution_%02d', $puzzle['id'], $index + 1),
                    'dammier_puzzle_id' => $puzzle['id'],
                    'ordre_etape' => $index + 1,
                    'coup' => $coup,
                ]);
            }

            foreach ($puzzle['reponses'] as $index => $coup) {
                DB::table('dammier_reponse_attendue')->insert([
                    'identifiant_reponse' => sprintf('%s_reponse_%02d', $puzzle['id'], $index + 1),
                    'dammier_puzzle_id' => $puzzle['id'],
                    'ordre_reponse' => $index + 1,
                    'coup' => $coup,
                ]);
            }

            foreach ($puzzle['indices'] as $index => $indice) {
                DB::table('dammier_indice')->insert([
                    'identifiant_indice' => sprintf('%s_indice_%02d', $puzzle['id'], $index + 1),
                    'dammier_puzzle_id' => $puzzle['id'],
                    'ordre_indice' => $index + 1,
                    'texte_indice' => $indice,
                ]);
            }
        }
    }
}
