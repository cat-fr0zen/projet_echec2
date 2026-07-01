<?php
/**
 * Cron o2switch : prechauffe le cache Lichess / Chess.com.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/api_lichess.php';
require_once dirname(__DIR__) . '/api_chesscom.php';

$pdo = o2switch_pdo();

$requete = $pdo->query(
    "SELECT identifiant, pseudo_chess, pseudo_lichess
     FROM compte_membre
     WHERE statut_compte = 'actif'
       AND (
            (pseudo_chess IS NOT NULL AND pseudo_chess <> '')
         OR (pseudo_lichess IS NOT NULL AND pseudo_lichess <> '')
       )
     ORDER BY identifiant ASC"
);

foreach ($requete->fetchAll() as $utilisateur) {
    $pseudoChess = trim((string) ($utilisateur['pseudo_chess'] ?? ''));
    $pseudoLichess = trim((string) ($utilisateur['pseudo_lichess'] ?? ''));

    if ($pseudoChess !== '') {
        o2switch_chesscom_fetch_player($pdo, $pseudoChess);
        usleep(250000);
    }

    if ($pseudoLichess !== '') {
        o2switch_lichess_fetch_player($pdo, $pseudoLichess);
        usleep(250000);
    }
}
