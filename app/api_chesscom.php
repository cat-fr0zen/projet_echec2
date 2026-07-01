<?php
/**
 * Helper Chess.com avec cache SQL o2switch.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api_lichess.php';

if (! function_exists('o2switch_chesscom_fetch_player')) {
    /**
     * @return array<string, mixed>
     */
    function o2switch_chesscom_fetch_player(PDO $pdo, string $pseudo): array
    {
        $config = o2switch_app_config();
        $pseudo = mb_strtolower(trim($pseudo));

        if ($pseudo === '') {
            return [
                'status' => 'missing',
                'pseudo' => '',
                'message' => 'Pseudo Chess.com vide.',
            ];
        }

        $cacheKey = 'chesscom:user:' . $pseudo;
        $cache = o2switch_api_cache_get($pdo, 'chesscom', $cacheKey);

        if ($cache !== null) {
            $cache['source_cache'] = 'db';

            return $cache;
        }

        $baseUrl = rtrim((string) $config['chesscom_base_url'], '/');
        $userAgent = (string) $config['api_user_agent'];
        $profil = o2switch_http_json_request($baseUrl . '/player/' . rawurlencode($pseudo), $userAgent);
        $stats = o2switch_http_json_request($baseUrl . '/player/' . rawurlencode($pseudo) . '/stats', $userAgent);
        $status = $profil['status'] !== 200 ? $profil['status'] : $stats['status'];

        $payload = [
            'status' => $profil['status'] === 200 && $stats['status'] === 200 ? 'ok' : 'error',
            'pseudo' => $pseudo,
            'http_status' => $status,
            'message' => $status === 429
                ? 'Chess.com a temporairement limite les requetes.'
                : (($profil['error'] !== '' ? $profil['error'] : $stats['error']) ?: ''),
            'profile' => $profil['data'] ?? [],
            'stats' => $stats['data'] ?? [],
            'fetched_at' => gmdate('c'),
            'source_cache' => 'direct',
        ];

        $ttl = $status === 200 ? (int) $config['api_cache_ttl'] : 900;
        o2switch_api_cache_put($pdo, 'chesscom', $cacheKey, $payload, $ttl, $status);

        return $payload;
    }
}
