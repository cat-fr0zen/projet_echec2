<?php

declare(strict_types=1);

final class ChessDotComService
{
    private const PROFILE_ENDPOINT = 'https://api.chess.com/pub/player/%s';
    private const STATS_ENDPOINT = 'https://api.chess.com/pub/player/%s/stats';
    private const SUCCESS_CACHE_TTL = 43200;
    private const ERROR_CACHE_TTL = 900;

    public function __construct(
        private string $cacheDirectory,
        private string $userAgent = 'association-echecs-site/1.0'
    ) {
        if (!is_dir($this->cacheDirectory)) {
            mkdir($this->cacheDirectory, 0777, true);
        }
    }

    public function fetchPlayerSnapshot(string $username): array
    {
        $normalizedUsername = $this->normalizeUsername($username);

        if ($normalizedUsername === '') {
            return [
                'status' => 'missing',
                'username' => '',
                'message' => "Aucun pseudo Chess.com n'est enregistré pour ce profil.",
            ];
        }

        $cachedSnapshot = $this->readCache($normalizedUsername);

        if ($cachedSnapshot !== null) {
            return $cachedSnapshot;
        }

        $encodedUsername = rawurlencode($normalizedUsername);
        $profileResponse = $this->requestJson(sprintf(self::PROFILE_ENDPOINT, $encodedUsername));

        if (($profileResponse['status_code'] ?? 0) !== 200 || !is_array($profileResponse['data'] ?? null)) {
            $snapshot = $this->buildErrorSnapshot(
                $normalizedUsername,
                (int) ($profileResponse['status_code'] ?? 0)
            );

            $this->writeCache($normalizedUsername, $snapshot, self::ERROR_CACHE_TTL);

            return $snapshot;
        }

        $statsResponse = $this->requestJson(sprintf(self::STATS_ENDPOINT, $encodedUsername));

        if (($statsResponse['status_code'] ?? 0) !== 200 || !is_array($statsResponse['data'] ?? null)) {
            $snapshot = $this->buildErrorSnapshot(
                $normalizedUsername,
                (int) ($statsResponse['status_code'] ?? 0)
            );

            $this->writeCache($normalizedUsername, $snapshot, self::ERROR_CACHE_TTL);

            return $snapshot;
        }

        $snapshot = $this->buildSuccessSnapshot(
            $normalizedUsername,
            $profileResponse['data'],
            $statsResponse['data']
        );

        $this->writeCache($normalizedUsername, $snapshot, self::SUCCESS_CACHE_TTL);

        return $snapshot;
    }

    public function normalizeUsername(?string $username): string
    {
        return mb_strtolower(trim((string) $username));
    }

    private function buildSuccessSnapshot(string $username, array $profileData, array $statsData): array
    {
        $fetchedAt = gmdate('c');

        return [
            'status' => 'linked',
            'username' => $username,
            'profile_url' => (string) ($profileData['url'] ?? ('https://www.chess.com/member/' . rawurlencode($username))),
            'player' => [
                'username' => (string) ($profileData['username'] ?? $username),
                'display_name' => (string) ($profileData['name'] ?? $profileData['username'] ?? $username),
                'title' => (string) ($profileData['title'] ?? ''),
                'avatar' => (string) ($profileData['avatar'] ?? ''),
                'country' => $this->extractCountryName((string) ($profileData['country'] ?? '')),
                'followers' => $this->toNullableInt($profileData['followers'] ?? null),
                'fide' => $this->toNullableInt($profileData['fide'] ?? null),
                'last_online_label' => $this->formatLastOnline($profileData['last_online'] ?? null),
            ],
            'ratings' => $this->extractRatings($statsData),
            'stats_note' => "Données publiques Chess.com affichées en lecture seule.",
            'fetched_at' => $fetchedAt,
            'fetched_at_label' => $this->formatFetchedAt($fetchedAt),
            'cache_source' => 'live',
            'message' => '',
        ];
    }

    private function buildErrorSnapshot(string $username, int $statusCode): array
    {
        $message = match ($statusCode) {
            404 => "Le pseudo Chess.com renseigné n'a pas été trouvé dans les données publiques.",
            410 => "Les données publiques Chess.com de ce profil ne sont plus disponibles.",
            429 => "Chess.com limite temporairement les requêtes. Réessaie un peu plus tard.",
            default => "Les statistiques publiques Chess.com sont temporairement indisponibles pour ce profil.",
        };

        return [
            'status' => 'error',
            'username' => $username,
            'profile_url' => 'https://www.chess.com/member/' . rawurlencode($username),
            'player' => [
                'username' => $username,
                'display_name' => $username,
                'title' => '',
                'avatar' => '',
                'country' => '',
                'followers' => null,
                'fide' => null,
                'last_online_label' => '',
            ],
            'ratings' => [],
            'stats_note' => "La liaison reste facultative et repose uniquement sur les données publiques de Chess.com.",
            'fetched_at' => gmdate('c'),
            'fetched_at_label' => "Dernière tentative : à l'instant",
            'cache_source' => 'live',
            'message' => $message,
        ];
    }

    private function extractRatings(array $statsData): array
    {
        $mapping = [
            'chess_rapid' => 'Rapid',
            'chess_blitz' => 'Blitz',
            'chess_bullet' => 'Bullet',
            'chess_daily' => 'Daily',
            'fide' => 'FIDE',
            'tactics' => 'Tactiques',
            'puzzle_rush' => 'Puzzle Rush',
        ];

        $ratings = [];

        foreach ($mapping as $key => $label) {
            if (!isset($statsData[$key]) || !is_array($statsData[$key])) {
                continue;
            }

            $entry = $statsData[$key];
            $lastRating = $this->toNullableInt($entry['last']['rating'] ?? $entry['rating'] ?? null);
            $bestRating = $this->toNullableInt($entry['best']['rating'] ?? $entry['highest']['rating'] ?? null);
            $bestDate = $this->formatUnixDate($entry['best']['date'] ?? $entry['highest']['date'] ?? null);
            $wins = $this->toNullableInt($entry['record']['win'] ?? null);
            $losses = $this->toNullableInt($entry['record']['loss'] ?? null);
            $draws = $this->toNullableInt($entry['record']['draw'] ?? null);
            $games = null;

            if ($wins !== null || $losses !== null || $draws !== null) {
                $games = (int) (($wins ?? 0) + ($losses ?? 0) + ($draws ?? 0));
            }

            $ratings[] = [
                'key' => $key,
                'label' => $label,
                'rating' => $lastRating,
                'best_rating' => $bestRating,
                'best_date_label' => $bestDate,
                'games' => $games,
                'wins' => $wins,
                'losses' => $losses,
                'draws' => $draws,
            ];
        }

        return $ratings;
    }

    private function requestJson(string $url): array
    {
        $headers = [
            'Accept: application/json',
            'User-Agent: ' . $this->userAgent,
        ];

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 6,
                'ignore_errors' => true,
                'header' => implode("\r\n", $headers),
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $body = @file_get_contents($url, false, $context);
        $responseHeaders = $http_response_header ?? [];
        $statusCode = $this->extractStatusCode($responseHeaders);
        $data = null;

        if (is_string($body) && trim($body) !== '') {
            $decoded = json_decode($body, true);
            $data = is_array($decoded) ? $decoded : null;
        }

        return [
            'status_code' => $statusCode,
            'data' => $data,
        ];
    }

    private function extractStatusCode(array $responseHeaders): int
    {
        if ($responseHeaders === []) {
            return 0;
        }

        $statusLine = (string) ($responseHeaders[0] ?? '');

        if (preg_match('/\s(\d{3})\s/', $statusLine, $matches) === 1) {
            return (int) $matches[1];
        }

        return 0;
    }

    private function extractCountryName(string $countryUrl): string
    {
        if ($countryUrl === '') {
            return '';
        }

        $trimmedUrl = rtrim($countryUrl, '/');
        $countryCode = strtoupper((string) basename($trimmedUrl));

        return $countryCode !== '' ? $countryCode : '';
    }

    private function formatLastOnline(mixed $value): string
    {
        return $this->formatUnixDateTime($value, "Dernière présence : %s");
    }

    private function formatFetchedAt(string $value): string
    {
        try {
            $date = new DateTimeImmutable($value);
        } catch (Exception) {
            return '';
        }

        return 'Données récupérées le ' . $date->setTimezone(new DateTimeZone('Europe/Paris'))->format('d/m/Y à H:i');
    }

    private function formatUnixDate(mixed $value): string
    {
        $timestamp = $this->toNullableInt($value);

        if ($timestamp === null || $timestamp <= 0) {
            return '';
        }

        try {
            return (new DateTimeImmutable('@' . $timestamp))
                ->setTimezone(new DateTimeZone('Europe/Paris'))
                ->format('d/m/Y');
        } catch (Exception) {
            return '';
        }
    }

    private function formatUnixDateTime(mixed $value, string $template): string
    {
        $timestamp = $this->toNullableInt($value);

        if ($timestamp === null || $timestamp <= 0) {
            return '';
        }

        try {
            $formatted = (new DateTimeImmutable('@' . $timestamp))
                ->setTimezone(new DateTimeZone('Europe/Paris'))
                ->format('d/m/Y à H:i');

            return sprintf($template, $formatted);
        } catch (Exception) {
            return '';
        }
    }

    private function toNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    private function getCachePath(string $username): string
    {
        $safeName = preg_replace('/[^a-z0-9_-]+/i', '-', $username) ?: 'player';

        return rtrim($this->cacheDirectory, '/\\') . DIRECTORY_SEPARATOR . $safeName . '.json';
    }

    private function readCache(string $username): ?array
    {
        $cachePath = $this->getCachePath($username);

        if (!is_file($cachePath)) {
            return null;
        }

        $content = @file_get_contents($cachePath);

        if (!is_string($content) || trim($content) === '') {
            return null;
        }

        $decoded = json_decode($content, true);

        if (!is_array($decoded)) {
            return null;
        }

        $expiresAt = (int) ($decoded['_expires_at'] ?? 0);
        $snapshot = $decoded['snapshot'] ?? null;

        if ($expiresAt < time() || !is_array($snapshot)) {
            return null;
        }

        $snapshot['cache_source'] = 'cache';

        return $snapshot;
    }

    private function writeCache(string $username, array $snapshot, int $ttlSeconds): void
    {
        $cachePath = $this->getCachePath($username);
        $payload = [
            '_expires_at' => time() + $ttlSeconds,
            'snapshot' => $snapshot,
        ];

        file_put_contents(
            $cachePath,
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            LOCK_EX
        );
    }
}
