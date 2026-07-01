<?php
/**
 * Helper Lichess avec cache SQL o2switch.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

if (! function_exists('o2switch_http_json_request')) {
    /**
     * @return array{status:int, data:array<string, mixed>|null, error:string}
     */
    function o2switch_http_json_request(string $url, string $userAgent): array
    {
        try {
            $contexte = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 8,
                    'header' => implode("\r\n", [
                        'Accept: application/json',
                        'User-Agent: ' . $userAgent,
                    ]),
                    'ignore_errors' => true,
                ],
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                ],
            ]);

            $contenu = @file_get_contents($url, false, $contexte);
            $status = 0;

            foreach (($http_response_header ?? []) as $ligneEntete) {
                if (preg_match('/\s(\d{3})\s/', $ligneEntete, $correspondances) === 1) {
                    $status = (int) $correspondances[1];
                    break;
                }
            }

            $donnees = is_string($contenu) ? json_decode($contenu, true) : null;

            return [
                'status' => $status,
                'data' => is_array($donnees) ? $donnees : null,
                'error' => '',
            ];
        } catch (Throwable $exception) {
            return [
                'status' => 0,
                'data' => null,
                'error' => $exception->getMessage(),
            ];
        }
    }
}

if (! function_exists('o2switch_lichess_fetch_player')) {
    /**
     * @return array<string, mixed>
     */
    function o2switch_lichess_fetch_player(PDO $pdo, string $pseudo): array
    {
        $config = o2switch_app_config();
        $pseudo = mb_strtolower(trim($pseudo));

        if ($pseudo === '') {
            return [
                'status' => 'missing',
                'pseudo' => '',
                'message' => 'Pseudo Lichess vide.',
            ];
        }

        $cacheKey = 'lichess:user:' . $pseudo;
        $cache = o2switch_api_cache_get($pdo, 'lichess', $cacheKey);

        if ($cache !== null) {
            $cache['source_cache'] = 'db';

            return $cache;
        }

        $reponse = o2switch_http_json_request(
            rtrim((string) $config['lichess_base_url'], '/') . '/user/' . rawurlencode($pseudo),
            (string) $config['api_user_agent']
        );

        $payload = [
            'status' => $reponse['status'] === 200 && is_array($reponse['data']) ? 'ok' : 'error',
            'pseudo' => $pseudo,
            'http_status' => $reponse['status'],
            'message' => $reponse['status'] === 429
                ? 'Lichess a temporairement limite les requetes.'
                : ($reponse['error'] !== '' ? $reponse['error'] : ''),
            'data' => $reponse['data'] ?? [],
            'fetched_at' => gmdate('c'),
            'source_cache' => 'direct',
        ];

        $ttl = $reponse['status'] === 200 ? (int) $config['api_cache_ttl'] : 900;
        o2switch_api_cache_put($pdo, 'lichess', $cacheKey, $payload, $ttl, $reponse['status']);

        return $payload;
    }
}
