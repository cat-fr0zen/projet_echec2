<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : DammierHebdomadaireTest.
 */

declare(strict_types=1);

namespace Tests\Feature;

use App\Repositories\DammierRepository;
use App\Repositories\UserRepository;
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

    public function test_un_membre_ne_peut_pas_enregistrer_deux_fois_le_meme_casse_tete_hebdomadaire(): void
    {
        $this->seed(DatabaseSeeder::class);

        $depot = new DammierRepository();
        $puzzle = $depot->obtenirPuzzleHebdomadaire();
        $membre = (new UserRepository())->creer([
            'nom' => 'Unique',
            'prenom' => 'Joueur',
            'date_naissance' => '2000-01-02',
            'courriel' => 'dammier-unique@example.test',
            'numero_licence' => 'FFE-UNIQUE-001',
            'mot_de_passe' => 'motdepasse-solide',
            'description_profil' => 'Compte de test',
            'pseudo_chess' => '',
            'pseudo_lichess' => '',
        ]);

        $premierScore = $depot->enregistrerScoreHebdomadaire($membre, $puzzle, 4, 30);
        $secondScore = $depot->enregistrerScoreHebdomadaire($membre, $puzzle, 2, 10);

        self::assertSame('created', $premierScore['dammier_record_status']);
        self::assertSame('already_played', $secondScore['dammier_record_status']);
        self::assertSame(4, $secondScore['dammier_moves_count']);
        self::assertSame(30, $secondScore['dammier_elapsed_seconds']);
        self::assertSame(
            1,
            (int) DB::table('dammier_score')
                ->where('dammier_week_key', (string) ($puzzle['dammier_week_key'] ?? ''))
                ->where('dammier_puzzle_id', (string) ($puzzle['dammier_id'] ?? ''))
                ->where('dammier_user_id', (string) ($membre['identifiant'] ?? ''))
                ->count()
        );
    }

    public function test_l_accueil_signale_qu_un_casse_tete_a_deja_ete_joue_par_le_membre_connecte(): void
    {
        $this->seed(DatabaseSeeder::class);

        $depot = new DammierRepository();
        $puzzle = $depot->obtenirPuzzleHebdomadaire();
        $membre = (new UserRepository())->creer([
            'nom' => 'Signal',
            'prenom' => 'Joueur',
            'date_naissance' => '2000-01-02',
            'courriel' => 'dammier-signal@example.test',
            'numero_licence' => 'FFE-SIGNAL-001',
            'mot_de_passe' => 'motdepasse-solide',
            'description_profil' => 'Compte de test',
            'pseudo_chess' => '',
            'pseudo_lichess' => '',
        ]);

        $depot->enregistrerScoreHebdomadaire($membre, $puzzle, 5, 42);

        $reponse = $this->withSession([
            'identifiant_utilisateur' => (string) $membre['identifiant'],
        ])->get('/');

        $reponse->assertOk();
        $reponse->assertSee('"dammier_already_played":true', false);
    }
}
