<?php

declare(strict_types=1);

/**
 * DepotDammier
 *
 * Gere le mini-jeu d'accueil:
 * - pool local de puzzles (source fiable, sans dependance API)
 * - selection automatique du puzzle de la semaine
 * - classement hebdomadaire des membres connectes
 *
 * Convention:
 * - toutes les cles exposees au front commencent par `dammier_` quand cela a du sens
 */
final class DepotDammier
{
    public function __construct(
        private StockageJson $stockagePuzzles,
        private StockageJson $stockageClassements
    ) {
    }

    /**
     * Retourne le puzzle courant pour la semaine ISO en cours.
     *
     * @return array Structure du puzzle hebdomadaire.
     */
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
                'dammier_hints' => [],
                'dammier_source' => 'pool_local',
                'dammier_generated_at' => gmdate('c'),
            ];
        }

        $seed = ((int) $dateReference->format('o')) * 53 + (int) $dateReference->format('W');
        $index = $seed % count($puzzles);
        $puzzle = $this->normaliserPuzzle($puzzles[$index]);
        $puzzle['dammier_week_key'] = $semaineIso;
        $puzzle['dammier_generated_at'] = gmdate('c');

        return $puzzle;
    }

    /**
     * Retourne le classement trie du puzzle de la semaine.
     *
     * @param string $weekKey Cle de semaine ISO.
     * @param string $puzzleId Identifiant du puzzle courant.
     * @return array Liste triee des meilleurs scores.
     */
    public function listerClassementHebdomadaire(string $weekKey, string $puzzleId): array
    {
        $scores = array_filter(
            $this->stockageClassements->lire(),
            static fn (array $entry): bool =>
                (string) ($entry['dammier_week_key'] ?? '') === $weekKey
                && (string) ($entry['dammier_puzzle_id'] ?? '') === $puzzleId
        );

        $scores = array_map([$this, 'normaliserScore'], $scores);

        usort(
            $scores,
            static fn (array $left, array $right): int =>
                [$left['dammier_moves_count'], $left['dammier_elapsed_seconds'], $left['dammier_solved_at']]
                <=>
                [$right['dammier_moves_count'], $right['dammier_elapsed_seconds'], $right['dammier_solved_at']]
        );

        return array_values($scores);
    }

    /**
     * Enregistre ou ameliore le meilleur score d'un membre sur la semaine courante.
     *
     * @param array $utilisateur Utilisateur connecte.
     * @param array $puzzle Puzzle hebdomadaire normalise.
     * @param int $movesCount Nombre de coups/joues necessaires.
     * @param int $elapsedSeconds Temps de resolution.
     * @return array Score final conserve.
     */
    public function enregistrerScoreHebdomadaire(array $utilisateur, array $puzzle, int $movesCount, int $elapsedSeconds): array
    {
        $scores = $this->stockageClassements->lire();
        $identifiantUtilisateur = (string) ($utilisateur['identifiant'] ?? '');
        $nomAffichage = trim((string) ($utilisateur['prenom'] ?? '') . ' ' . (string) ($utilisateur['nom'] ?? ''));

        $nouveauScore = [
            'dammier_score_id' => 'dammier_score_' . bin2hex(random_bytes(8)),
            'dammier_week_key' => (string) ($puzzle['dammier_week_key'] ?? ''),
            'dammier_puzzle_id' => (string) ($puzzle['dammier_id'] ?? ''),
            'dammier_user_id' => $identifiantUtilisateur,
            'dammier_display_name' => $nomAffichage !== '' ? $nomAffichage : (string) ($utilisateur['courriel'] ?? 'Membre'),
            'dammier_moves_count' => $movesCount,
            'dammier_elapsed_seconds' => $elapsedSeconds,
            'dammier_solved_at' => gmdate('c'),
        ];

        $indexExistant = null;
        $scoreExistant = null;

        foreach ($scores as $index => $entry) {
            if (
                (string) ($entry['dammier_week_key'] ?? '') === $nouveauScore['dammier_week_key']
                && (string) ($entry['dammier_puzzle_id'] ?? '') === $nouveauScore['dammier_puzzle_id']
                && (string) ($entry['dammier_user_id'] ?? '') === $identifiantUtilisateur
            ) {
                $indexExistant = $index;
                $scoreExistant = $this->normaliserScore($entry);
                break;
            }
        }

        if ($scoreExistant !== null) {
            $estMeilleur = $movesCount < $scoreExistant['dammier_moves_count']
                || (
                    $movesCount === $scoreExistant['dammier_moves_count']
                    && $elapsedSeconds < $scoreExistant['dammier_elapsed_seconds']
                );

            if (!$estMeilleur) {
                return $scoreExistant;
            }

            $nouveauScore['dammier_score_id'] = $scoreExistant['dammier_score_id'];
            $scores[$indexExistant] = $nouveauScore;
        } else {
            $scores[] = $nouveauScore;
        }

        $this->stockageClassements->ecrire($scores);

        return $nouveauScore;
    }

    /**
     * Retourne vrai si la proposition correspond bien au puzzle hebdomadaire actif.
     */
    public function verifierPuzzleHebdomadaire(string $weekKey, string $puzzleId): bool
    {
        $puzzle = $this->obtenirPuzzleHebdomadaire();

        return $weekKey === (string) ($puzzle['dammier_week_key'] ?? '')
            && $puzzleId === (string) ($puzzle['dammier_id'] ?? '');
    }

    /**
     * @return array Liste brute des puzzles marques actifs.
     */
    private function listerPuzzlesActifs(): array
    {
        return array_values(
            array_filter(
                $this->stockagePuzzles->lire(),
                static fn (array $entry): bool => (bool) ($entry['dammier_active'] ?? true)
            )
        );
    }

    private function normaliserPuzzle(array $entry): array
    {
        return [
            'dammier_id' => (string) ($entry['dammier_id'] ?? ''),
            'dammier_title' => (string) ($entry['dammier_title'] ?? 'Puzzle hebdomadaire'),
            'dammier_description' => (string) ($entry['dammier_description'] ?? ''),
            'dammier_instruction' => (string) ($entry['dammier_instruction'] ?? ''),
            'dammier_fen' => (string) ($entry['dammier_fen'] ?? '8/8/8/8/8/8/8/8 w - - 0 1'),
            'dammier_side_to_move' => (string) ($entry['dammier_side_to_move'] ?? 'w'),
            'dammier_solution' => array_values(array_map('strval', (array) ($entry['dammier_solution'] ?? []))),
            'dammier_hints' => array_values(array_map('strval', (array) ($entry['dammier_hints'] ?? []))),
            'dammier_source' => (string) ($entry['dammier_source'] ?? 'pool_local'),
        ];
    }

    private function normaliserScore(array $entry): array
    {
        return [
            'dammier_score_id' => (string) ($entry['dammier_score_id'] ?? ''),
            'dammier_week_key' => (string) ($entry['dammier_week_key'] ?? ''),
            'dammier_puzzle_id' => (string) ($entry['dammier_puzzle_id'] ?? ''),
            'dammier_user_id' => (string) ($entry['dammier_user_id'] ?? ''),
            'dammier_display_name' => (string) ($entry['dammier_display_name'] ?? 'Membre'),
            'dammier_moves_count' => max(1, (int) ($entry['dammier_moves_count'] ?? 0)),
            'dammier_elapsed_seconds' => max(1, (int) ($entry['dammier_elapsed_seconds'] ?? 0)),
            'dammier_solved_at' => (string) ($entry['dammier_solved_at'] ?? ''),
        ];
    }
}
