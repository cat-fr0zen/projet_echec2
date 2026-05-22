<?php

declare(strict_types=1);

final class DepotDammierOracle
{
    public function __construct(private BaseDeDonneesOracle $base)
    {
    }

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
        $lignes = $this->base->lireTout(
            'SELECT
                dammier_score_id,
                dammier_week_key,
                dammier_puzzle_id,
                dammier_user_id,
                dammier_display_name,
                dammier_moves_count,
                dammier_elapsed_seconds,
                TO_CHAR(dammier_solved_at, \'YYYY-MM-DD"T"HH24:MI:SS"Z"\') dammier_solved_at
            FROM dammier_score
            WHERE dammier_week_key = :week_key
              AND dammier_puzzle_id = :puzzle_id
            ORDER BY dammier_moves_count, dammier_elapsed_seconds, dammier_solved_at',
            [
                'week_key' => $weekKey,
                'puzzle_id' => $puzzleId,
            ]
        );

        return array_map([$this, 'normaliserScore'], $lignes);
    }

    public function enregistrerScoreHebdomadaire(array $utilisateur, array $puzzle, int $movesCount, int $elapsedSeconds): array
    {
        $identifiantUtilisateur = (string) ($utilisateur['identifiant'] ?? '');
        $nomAffichage = trim((string) ($utilisateur['prenom'] ?? '') . ' ' . (string) ($utilisateur['nom'] ?? ''));
        $weekKey = (string) ($puzzle['dammier_week_key'] ?? '');
        $puzzleId = (string) ($puzzle['dammier_id'] ?? '');

        $scoreExistant = $this->base->lireUne(
            'SELECT
                dammier_score_id,
                dammier_week_key,
                dammier_puzzle_id,
                dammier_user_id,
                dammier_display_name,
                dammier_moves_count,
                dammier_elapsed_seconds,
                TO_CHAR(dammier_solved_at, \'YYYY-MM-DD"T"HH24:MI:SS"Z"\') dammier_solved_at
            FROM dammier_score
            WHERE dammier_week_key = :week_key
              AND dammier_puzzle_id = :puzzle_id
              AND dammier_user_id = :user_id',
            [
                'week_key' => $weekKey,
                'puzzle_id' => $puzzleId,
                'user_id' => $identifiantUtilisateur,
            ]
        );

        if ($scoreExistant !== null) {
            $score = $this->normaliserScore($scoreExistant);
            $estMeilleur = $movesCount < $score['dammier_moves_count']
                || ($movesCount === $score['dammier_moves_count'] && $elapsedSeconds < $score['dammier_elapsed_seconds']);

            if (!$estMeilleur) {
                $score['dammier_record_status'] = 'unchanged';

                return $score;
            }

            $this->base->executer(
                'UPDATE dammier_score
                    SET dammier_display_name = :display_name,
                        dammier_moves_count = :moves_count,
                        dammier_elapsed_seconds = :elapsed_seconds,
                        dammier_solved_at = SYSDATE
                  WHERE dammier_score_id = :score_id',
                [
                    'display_name' => $nomAffichage !== '' ? $nomAffichage : (string) ($utilisateur['courriel'] ?? 'Membre'),
                    'moves_count' => $movesCount,
                    'elapsed_seconds' => $elapsedSeconds,
                    'score_id' => $score['dammier_score_id'],
                ]
            );

            $scoreMisAJour = $this->listerClassementHebdomadaire($weekKey, $puzzleId);
            foreach ($scoreMisAJour as $ligne) {
                if ($ligne['dammier_user_id'] === $identifiantUtilisateur) {
                    $ligne['dammier_record_status'] = 'improved';

                    return $ligne;
                }
            }
        }

        $scoreId = 'dammier_score_' . bin2hex(random_bytes(8));
        $displayName = $nomAffichage !== '' ? $nomAffichage : (string) ($utilisateur['courriel'] ?? 'Membre');

        $this->base->executer(
            'INSERT INTO dammier_score (
                dammier_score_id, dammier_week_key, dammier_puzzle_id,
                dammier_user_id, dammier_display_name, dammier_moves_count,
                dammier_elapsed_seconds, dammier_solved_at
            ) VALUES (
                :score_id, :week_key, :puzzle_id,
                :user_id, :display_name, :moves_count,
                :elapsed_seconds, SYSDATE
            )',
            [
                'score_id' => $scoreId,
                'week_key' => $weekKey,
                'puzzle_id' => $puzzleId,
                'user_id' => $identifiantUtilisateur,
                'display_name' => $displayName,
                'moves_count' => $movesCount,
                'elapsed_seconds' => $elapsedSeconds,
            ]
        );

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
        $lignes = $this->base->lireTout(
            'SELECT
                dammier_id,
                titre,
                description,
                instruction,
                fen,
                trait,
                solution,
                reponses,
                indices,
                source_puzzle
            FROM dammier_puzzle
            WHERE actif = 1
            ORDER BY dammier_id'
        );

        return array_map(fn (array $ligne): array => $this->normaliserPuzzle($ligne), $lignes);
    }

    private function normaliserPuzzle(array $ligne): array
    {
        return [
            'dammier_id' => (string) ($ligne['dammier_id'] ?? ''),
            'dammier_title' => (string) ($ligne['titre'] ?? 'Puzzle hebdomadaire'),
            'dammier_description' => (string) ($ligne['description'] ?? ''),
            'dammier_instruction' => (string) ($ligne['instruction'] ?? ''),
            'dammier_fen' => (string) ($ligne['fen'] ?? '8/8/8/8/8/8/8/8 w - - 0 1'),
            'dammier_side_to_move' => (string) ($ligne['trait'] ?? 'w'),
            'dammier_solution' => $this->texteVersListe((string) ($ligne['solution'] ?? '')),
            'dammier_replies' => $this->texteVersListe((string) ($ligne['reponses'] ?? '')),
            'dammier_hints' => $this->texteVersListe((string) ($ligne['indices'] ?? '')),
            'dammier_source' => (string) ($ligne['source_puzzle'] ?? 'pool_local'),
        ];
    }

    private function normaliserScore(array $ligne): array
    {
        return [
            'dammier_score_id' => (string) ($ligne['dammier_score_id'] ?? ''),
            'dammier_week_key' => (string) ($ligne['dammier_week_key'] ?? ''),
            'dammier_puzzle_id' => (string) ($ligne['dammier_puzzle_id'] ?? ''),
            'dammier_user_id' => (string) ($ligne['dammier_user_id'] ?? ''),
            'dammier_display_name' => (string) ($ligne['dammier_display_name'] ?? 'Membre'),
            'dammier_moves_count' => max(1, (int) ($ligne['dammier_moves_count'] ?? 0)),
            'dammier_elapsed_seconds' => max(1, (int) ($ligne['dammier_elapsed_seconds'] ?? 0)),
            'dammier_solved_at' => (string) ($ligne['dammier_solved_at'] ?? ''),
            'dammier_record_status' => (string) ($ligne['dammier_record_status'] ?? ''),
        ];
    }

    private function texteVersListe(string $valeur): array
    {
        if (trim($valeur) === '') {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', preg_split('/[\r\n,]+/', $valeur) ?: []),
            static fn (string $element): bool => $element !== ''
        ));
    }
}
