<?php

declare(strict_types=1);

namespace App\Repositories;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final class DammierRepository
{
    public function obtenirPuzzleHebdomadaire(): array
    {
        $puzzles = $this->listerPuzzlesActifs();
        $dateReference = new DateTimeImmutable('now');
        $semaineIso = $dateReference->format('o-\WW');

        if ($puzzles === []) {
            return [
                'dammier_id' => 'dammier_placeholder',
                'dammier_week_key' => $semaineIso,
                'dammier_title' => 'Puzzle en preparation',
                'dammier_description' => 'Le prochain casse-tete sera bientot disponible.',
                'dammier_instruction' => 'Le puzzle hebdomadaire arrive bientot.',
                'dammier_fen' => '8/8/8/8/8/8/8/8 w - - 0 1',
                'dammier_side_to_move' => 'w',
                'dammier_solution' => [],
                'dammier_replies' => [],
                'dammier_hints' => [],
                'dammier_source' => 'pool_local',
                'dammier_generated_at' => gmdate('c'),
            ];
        }

        $seed = ((int) $dateReference->format('o')) * 53 + (int) $dateReference->format('W');
        $puzzle = $puzzles[$seed % count($puzzles)];
        $puzzle['dammier_week_key'] = $semaineIso;
        $puzzle['dammier_generated_at'] = gmdate('c');

        return $puzzle;
    }

    public function listerClassementHebdomadaire(string $weekKey, string $puzzleId): array
    {
        $rows = DB::table('dammier_score')
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
                    'dammier_display_name' => $nomAffichage !== '' ? $nomAffichage : (string) ($utilisateur['courriel'] ?? 'Membre'),
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
            'dammier_display_name' => $displayName,
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
        $puzzle = $this->obtenirPuzzleHebdomadaire();

        return $weekKey === (string) ($puzzle['dammier_week_key'] ?? '')
            && $puzzleId === (string) ($puzzle['dammier_id'] ?? '');
    }

    private function listerPuzzlesActifs(): array
    {
        $rows = DB::table('dammier_puzzle')
            ->where('actif', 1)
            ->orderBy('dammier_id')
            ->get()
            ->all();

        return array_map(fn (object $row): array => $this->normaliserPuzzle((array) $row), $rows);
    }

    private function normaliserPuzzle(array $row): array
    {
        return [
            'dammier_id' => (string) ($row['dammier_id'] ?? ''),
            'dammier_title' => (string) ($row['titre'] ?? 'Puzzle hebdomadaire'),
            'dammier_description' => (string) ($row['description'] ?? ''),
            'dammier_instruction' => (string) ($row['instruction'] ?? ''),
            'dammier_fen' => (string) ($row['fen'] ?? '8/8/8/8/8/8/8/8 w - - 0 1'),
            'dammier_side_to_move' => (string) ($row['trait'] ?? 'w'),
            'dammier_solution' => $this->texteVersListe((string) ($row['solution'] ?? '')),
            'dammier_replies' => $this->texteVersListe((string) ($row['reponses'] ?? '')),
            'dammier_hints' => $this->texteVersListe((string) ($row['indices'] ?? '')),
            'dammier_source' => (string) ($row['source_puzzle'] ?? 'pool_local'),
        ];
    }

    private function normaliserScore(array $row): array
    {
        return [
            'dammier_score_id' => (string) ($row['dammier_score_id'] ?? ''),
            'dammier_week_key' => (string) ($row['dammier_week_key'] ?? ''),
            'dammier_puzzle_id' => (string) ($row['dammier_puzzle_id'] ?? ''),
            'dammier_user_id' => (string) ($row['dammier_user_id'] ?? ''),
            'dammier_display_name' => (string) ($row['dammier_display_name'] ?? 'Membre'),
            'dammier_moves_count' => max(1, (int) ($row['dammier_moves_count'] ?? 0)),
            'dammier_elapsed_seconds' => max(1, (int) ($row['dammier_elapsed_seconds'] ?? 0)),
            'dammier_solved_at' => $this->formaterDateIso($row['dammier_solved_at'] ?? null),
            'dammier_record_status' => (string) ($row['dammier_record_status'] ?? ''),
        ];
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
}
