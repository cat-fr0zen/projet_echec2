<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\NomAffichageUtilisateur;
use DateTimeImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class DammierRepository
{
    public function obtenirPuzzleHebdomadaire(): array
    {
        return $this->obtenirPuzzlePourDate(new DateTimeImmutable('now'));
    }

    public function obtenirPuzzlePourDate(DateTimeImmutable $dateReference): array
    {
        $puzzles = $this->listerPuzzlesActifs();
        $semaineIso = $dateReference->format('o-\WW');

        if ($puzzles === []) {
            $puzzle = $this->puzzleDeSecours();
            $puzzle['dammier_week_key'] = $semaineIso;
            $puzzle['dammier_generated_at'] = gmdate('c');

            return $puzzle;
        }

        $puzzles = $this->ordonnerPuzzlesPourRotation($puzzles);
        $indexHebdomadaire = (((int) $dateReference->format('o')) * 53 + (int) $dateReference->format('W')) % count($puzzles);
        $puzzle = $puzzles[$indexHebdomadaire];
        $puzzle['dammier_week_key'] = $semaineIso;
        $puzzle['dammier_generated_at'] = gmdate('c');

        return $puzzle;
    }

    public function listerClassementHebdomadaire(string $weekKey, string $puzzleId): array
    {
        if (! $this->tablesScoresDisponibles()) {
            return [];
        }

        $rows = $this->requeteScores()
            ->where('dammier_week_key', $weekKey)
            ->where('dammier_puzzle_id', $puzzleId)
            ->orderBy('dammier_moves_count')
            ->orderBy('dammier_elapsed_seconds')
            ->orderBy('dammier_solved_at')
            ->get()
            ->all();

        return array_map(fn (object $row): array => $this->normaliserScore((array) $row), $rows);
    }

    public function enregistrerScoreHebdomadaire(array $utilisateur, array $puzzle, int $movesCount, int $elapsedSeconds): array
    {
        $identifiantUtilisateur = (string) ($utilisateur['identifiant'] ?? '');
        $nomAffichage = trim((string) ($utilisateur['prenom'] ?? '') . ' ' . (string) ($utilisateur['nom'] ?? ''));
        $weekKey = (string) ($puzzle['dammier_week_key'] ?? '');
        $puzzleId = (string) ($puzzle['dammier_id'] ?? '');

        if (! Schema::hasTable('dammier_score')) {
            $displayName = $nomAffichage !== '' ? $nomAffichage : (string) ($utilisateur['courriel'] ?? 'Membre');

            return [
                'dammier_score_id' => '',
                'dammier_week_key' => $weekKey,
                'dammier_puzzle_id' => $puzzleId,
                'dammier_user_id' => $identifiantUtilisateur,
                'dammier_display_name' => $displayName,
                'dammier_moves_count' => max(1, $movesCount),
                'dammier_elapsed_seconds' => max(1, $elapsedSeconds),
                'dammier_solved_at' => gmdate('c'),
                'dammier_record_status' => 'storage_unavailable',
            ];
        }

        $existing = DB::table('dammier_score')
            ->where('dammier_week_key', $weekKey)
            ->where('dammier_puzzle_id', $puzzleId)
            ->where('dammier_user_id', $identifiantUtilisateur)
            ->first();

        if ($existing !== null) {
            $score = $this->normaliserScore((array) $existing);
            $estMeilleur = $movesCount < $score['dammier_moves_count']
                || ($movesCount === $score['dammier_moves_count'] && $elapsedSeconds < $score['dammier_elapsed_seconds']);

            if (!$estMeilleur) {
                $score['dammier_record_status'] = 'unchanged';

                return $score;
            }

            DB::table('dammier_score')
                ->where('dammier_score_id', $score['dammier_score_id'])
                ->update([
                    'dammier_moves_count' => $movesCount,
                    'dammier_elapsed_seconds' => $elapsedSeconds,
                    'dammier_solved_at' => date('Y-m-d H:i:s'),
                ]);

            foreach ($this->listerClassementHebdomadaire($weekKey, $puzzleId) as $row) {
                if ($row['dammier_user_id'] === $identifiantUtilisateur) {
                    $row['dammier_record_status'] = 'improved';

                    return $row;
                }
            }
        }

        $scoreId = 'dammier_score_' . bin2hex(random_bytes(8));
        $displayName = $nomAffichage !== '' ? $nomAffichage : (string) ($utilisateur['courriel'] ?? 'Membre');

        DB::table('dammier_score')->insert([
            'dammier_score_id' => $scoreId,
            'dammier_week_key' => $weekKey,
            'dammier_puzzle_id' => $puzzleId,
            'dammier_user_id' => $identifiantUtilisateur,
            'dammier_moves_count' => $movesCount,
            'dammier_elapsed_seconds' => $elapsedSeconds,
            'dammier_solved_at' => date('Y-m-d H:i:s'),
        ]);

        return [
            'dammier_score_id' => $scoreId,
            'dammier_week_key' => $weekKey,
            'dammier_puzzle_id' => $puzzleId,
            'dammier_user_id' => $identifiantUtilisateur,
            'dammier_display_name' => $displayName,
            'dammier_moves_count' => $movesCount,
            'dammier_elapsed_seconds' => $elapsedSeconds,
            'dammier_solved_at' => gmdate('c'),
            'dammier_record_status' => 'created',
        ];
    }

    public function verifierPuzzleHebdomadaire(string $weekKey, string $puzzleId): bool
    {
        $puzzle = $this->obtenirPuzzlePourDate(new DateTimeImmutable('now'));

        return $weekKey === (string) ($puzzle['dammier_week_key'] ?? '')
            && $puzzleId === (string) ($puzzle['dammier_id'] ?? '');
    }

    private function listerPuzzlesActifs(): array
    {
        if (! Schema::hasTable('dammier_puzzle')) {
            return [];
        }

        $requete = DB::table('dammier_puzzle')
            ->where('actif', 1)
            ->orderBy('dammier_puzzle.dammier_id');

        if (Schema::hasTable('ref_difficulte_dammier')) {
            $requete->leftJoin('ref_difficulte_dammier as difficulte', 'difficulte.code_difficulte', '=', 'dammier_puzzle.code_difficulte')
                ->select(
                    'dammier_puzzle.*',
                    'difficulte.libelle_difficulte as libelle_difficulte_dammier'
                );
        } else {
            $requete->select('dammier_puzzle.*');
        }

        $rows = $requete->get()->all();

        return array_map(fn (object $row): array => $this->normaliserPuzzle((array) $row), $rows);
    }

    private function normaliserPuzzle(array $row): array
    {
        $dammierId = (string) ($row['dammier_id'] ?? '');

        return [
            'dammier_id' => $dammierId,
            'dammier_title' => (string) ($row['titre'] ?? 'Puzzle hebdomadaire'),
            'dammier_description' => (string) ($row['description'] ?? ''),
            'dammier_instruction' => (string) ($row['instruction'] ?? ''),
            'dammier_fen' => (string) ($row['fen'] ?? '8/8/8/8/8/8/8/8 w - - 0 1'),
            'dammier_side_to_move' => (string) ($row['trait'] ?? 'w'),
            'dammier_difficulty_code' => (string) ($row['code_difficulte'] ?? 'medium'),
            'dammier_difficulty_label' => (string) ($row['libelle_difficulte_dammier'] ?? 'Medium'),
            'dammier_solution' => $this->chargerListePuzzle(
                'dammier_solution_etape',
                'ordre_etape',
                'coup',
                $dammierId,
                (string) ($row['solution'] ?? '')
            ),
            'dammier_replies' => $this->chargerListePuzzle(
                'dammier_reponse_attendue',
                'ordre_reponse',
                'coup',
                $dammierId,
                (string) ($row['reponses'] ?? '')
            ),
            'dammier_hints' => $this->chargerListePuzzle(
                'dammier_indice',
                'ordre_indice',
                'texte_indice',
                $dammierId,
                (string) ($row['indices'] ?? '')
            ),
            'dammier_source' => (string) ($row['source_puzzle'] ?? 'pool_local'),
            'dammier_white_moves_count' => count($this->chargerListePuzzle(
                'dammier_solution_etape',
                'ordre_etape',
                'coup',
                $dammierId,
                (string) ($row['solution'] ?? '')
            )),
        ];
    }

    private function normaliserScore(array $row): array
    {
        $displayName = $row['dammier_display_name'] ?? null;

        if ($displayName === null || trim((string) $displayName) === '') {
            $displayName = NomAffichageUtilisateur::depuisValeurs(
                $row['utilisateur_prenom_compte'] ?? '',
                $row['utilisateur_nom_compte'] ?? '',
                $row['utilisateur_courriel_compte'] ?? '',
                'Membre'
            );
        }

        return [
            'dammier_score_id' => (string) ($row['dammier_score_id'] ?? ''),
            'dammier_week_key' => (string) ($row['dammier_week_key'] ?? ''),
            'dammier_puzzle_id' => (string) ($row['dammier_puzzle_id'] ?? ''),
            'dammier_user_id' => (string) ($row['dammier_user_id'] ?? ''),
            'dammier_display_name' => (string) $displayName,
            'dammier_moves_count' => max(1, (int) ($row['dammier_moves_count'] ?? 0)),
            'dammier_elapsed_seconds' => max(1, (int) ($row['dammier_elapsed_seconds'] ?? 0)),
            'dammier_solved_at' => $this->formaterDateIso($row['dammier_solved_at'] ?? null),
            'dammier_record_status' => (string) ($row['dammier_record_status'] ?? ''),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function chargerListePuzzle(
        string $table,
        string $orderColumn,
        string $valueColumn,
        string $puzzleId,
        string $fallbackLegacy = ''
    ): array {
        if ($puzzleId === '' || ! Schema::hasTable($table)) {
            return $this->texteVersListe($fallbackLegacy);
        }

        $lignes = DB::table($table)
            ->where('dammier_puzzle_id', $puzzleId)
            ->orderBy($orderColumn)
            ->pluck($valueColumn)
            ->all();

        if ($lignes === []) {
            return $this->texteVersListe($fallbackLegacy);
        }

        return array_values(array_filter(
            array_map(static fn (mixed $item): string => trim((string) $item), $lignes),
            static fn (string $item): bool => $item !== ''
        ));
    }

    private function requeteScores(): Builder
    {
        return DB::table('dammier_score')
            ->leftJoin('compte_membre as utilisateur', 'utilisateur.identifiant', '=', 'dammier_score.dammier_user_id')
            ->select(
                'dammier_score.*',
                'utilisateur.nom as utilisateur_nom_compte',
                'utilisateur.prenom as utilisateur_prenom_compte',
                'utilisateur.courriel as utilisateur_courriel_compte'
            );
    }

    private function puzzleDeSecours(): array
    {
        return [
            'dammier_id' => 'dammier_secours',
            'dammier_title' => 'Puzzle de secours',
            'dammier_description' => "Un puzzle local reste disponible meme si la base n'a pas encore ete peuplee.",
            'dammier_instruction' => 'Trouve la suite theorique en 2 coups blancs.',
            'dammier_fen' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w - - 0 1',
            'dammier_side_to_move' => 'w',
            'dammier_difficulty_code' => 'facile',
            'dammier_difficulty_label' => 'Facile',
            'dammier_solution' => ['e2e4', 'g1f3'],
            'dammier_replies' => ['e7e5', 'b8c6'],
            'dammier_hints' => [
                'Commence par prendre le centre.',
                'Ensuite, developpe un cavalier vers une case active.',
            ],
            'dammier_source' => 'fallback_local',
            'dammier_white_moves_count' => 2,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $puzzles
     * @return array<int, array<string, mixed>>
     */
    private function ordonnerPuzzlesPourRotation(array $puzzles): array
    {
        usort($puzzles, static function (array $gauche, array $droite): int {
            $poidsGauche = sprintf('%u', crc32((string) ($gauche['dammier_id'] ?? '')));
            $poidsDroite = sprintf('%u', crc32((string) ($droite['dammier_id'] ?? '')));

            if ($poidsGauche === $poidsDroite) {
                return strcmp((string) ($gauche['dammier_id'] ?? ''), (string) ($droite['dammier_id'] ?? ''));
            }

            return $poidsGauche <=> $poidsDroite;
        });

        return $puzzles;
    }

    private function texteVersListe(string $valeur): array
    {
        if (trim($valeur) === '') {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', preg_split('/[\r\n,]+/', $valeur) ?: []),
            static fn (string $item): bool => $item !== ''
        ));
    }

    private function formaterDateIso(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return (new DateTimeImmutable((string) $value))->format('c');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    private function tablesScoresDisponibles(): bool
    {
        return Schema::hasTable('dammier_score') && Schema::hasTable('compte_membre');
    }
}
