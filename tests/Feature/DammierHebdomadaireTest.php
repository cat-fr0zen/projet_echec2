<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : DammierHebdomadaireTest.
 */

declare(strict_types=1);

namespace Tests\Feature;

use App\Repositories\DammierRepository;
use Database\Seeders\DatabaseSeeder;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class DammierHebdomadaireTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_seeding_cree_un_vrai_pool_de_plus_de_cent_puzzles_classes(): void
    {
        $this->seed(DatabaseSeeder::class);

        $totalActifs = (int) DB::table('dammier_puzzle')->where('actif', 1)->count();
        self::assertGreaterThanOrEqual(100, $totalActifs);

        $idsActifs = DB::table('dammier_puzzle')
            ->where('actif', 1)
            ->pluck('dammier_id')
            ->all();

        $solutionCounts = DB::table('dammier_solution_etape')
            ->selectRaw('dammier_puzzle_id, COUNT(*) as total')
            ->whereIn('dammier_puzzle_id', $idsActifs)
            ->groupBy('dammier_puzzle_id')
            ->pluck('total', 'dammier_puzzle_id')
            ->all();

        self::assertCount($totalActifs, $solutionCounts);

        foreach ($solutionCounts as $nombreCoups) {
            self::assertGreaterThanOrEqual(2, (int) $nombreCoups);
        }

        foreach (['facile', 'medium', 'difficile', 'extreme'] as $difficulte) {
            self::assertGreaterThan(
                0,
                (int) DB::table('dammier_puzzle')->where('actif', 1)->where('code_difficulte', $difficulte)->count()
            );
        }
    }

    public function test_le_puzzle_hebdomadaire_retourne_une_difficulte_et_plusieurs_coups(): void
    {
        $this->seed(DatabaseSeeder::class);

        $puzzle = (new DammierRepository())->obtenirPuzzlePourDate(new DateTimeImmutable('2026-06-03 10:00:00'));

        self::assertContains(
            $puzzle['dammier_difficulty_code'] ?? '',
            ['facile', 'medium', 'difficile', 'extreme']
        );
        self::assertNotSame('', (string) ($puzzle['dammier_difficulty_label'] ?? ''));
        self::assertGreaterThanOrEqual(2, count((array) ($puzzle['dammier_solution'] ?? [])));
    }

    public function test_la_rotation_est_stable_dans_la_semaine_et_change_la_semaine_suivante(): void
    {
        $this->seed(DatabaseSeeder::class);

        $depot = new DammierRepository();

        $puzzleSemaine = $depot->obtenirPuzzlePourDate(new DateTimeImmutable('2026-06-03 10:00:00'));
        $puzzleMemeSemaine = $depot->obtenirPuzzlePourDate(new DateTimeImmutable('2026-06-05 20:00:00'));
        $puzzleSemaineSuivante = $depot->obtenirPuzzlePourDate(new DateTimeImmutable('2026-06-10 10:00:00'));

        self::assertSame($puzzleSemaine['dammier_week_key'], $puzzleMemeSemaine['dammier_week_key']);
        self::assertSame($puzzleSemaine['dammier_id'], $puzzleMemeSemaine['dammier_id']);
        self::assertNotSame($puzzleSemaine['dammier_week_key'], $puzzleSemaineSuivante['dammier_week_key']);
        self::assertNotSame($puzzleSemaine['dammier_id'], $puzzleSemaineSuivante['dammier_id']);
    }
}
