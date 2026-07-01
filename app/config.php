<?php
/**
 * Configuration centralisee o2switch / cPanel.
 */

declare(strict_types=1);

if (! function_exists('o2switch_parse_env_file')) {
    /**
     * @return array<string, string>
     */
    function o2switch_parse_env_file(string $chemin): array
    {
        if (! is_file($chemin) || ! is_readable($chemin)) {
            return [];
        }

        $lignes = file($chemin, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if (! is_array($lignes)) {
            return [];
        }

        $valeurs = [];

        foreach ($lignes as $ligne) {
            $ligne = trim($ligne);

            if ($ligne === '' || str_starts_with($ligne, '#') || ! str_contains($ligne, '=')) {
                continue;
            }

            [$cle, $valeur] = explode('=', $ligne, 2);
            $cle = trim($cle);
            $valeur = trim($valeur);

            if ($cle === '') {
                continue;
            }

            if (
                strlen($valeur) >= 2
                && (($valeur[0] === '"' && substr($valeur, -1) === '"') || ($valeur[0] === "'" && substr($valeur, -1) === "'"))
            ) {
                $valeur = substr($valeur, 1, -1);
            }

            $valeurs[$cle] = $valeur;
        }

        return $valeurs;
    }

    /**
     * @return array<string, string>
     */
    function o2switch_load_env(): array
    {
        static $cache = null;

        if (is_array($cache)) {
            return $cache;
        }

        $cache = [];
        $chemins = [
            __DIR__ . '/.env',
            dirname(__DIR__) . '/.env',
            __DIR__ . '/.env.o2switch',
            dirname(__DIR__) . '/.env.o2switch',
        ];

        foreach ($chemins as $chemin) {
            foreach (o2switch_parse_env_file($chemin) as $cle => $valeur) {
                if (! array_key_exists($cle, $cache)) {
                    $cache[$cle] = $valeur;
                }
            }
        }

        foreach ($_ENV as $cle => $valeur) {
            if (is_string($cle) && is_scalar($valeur) && ! array_key_exists($cle, $cache)) {
                $cache[$cle] = (string) $valeur;
            }
        }

        return $cache;
    }

    function o2switch_env(string $cle, ?string $defaut = null): ?string
    {
        $valeurServeur = getenv($cle);

        if ($valeurServeur !== false && is_string($valeurServeur) && $valeurServeur !== '') {
            return $valeurServeur;
        }

        $env = o2switch_load_env();

        return array_key_exists($cle, $env) ? $env[$cle] : $defaut;
    }

    /**
     * @return array<string, mixed>
     */
    function o2switch_app_config(): array
    {
        static $config = null;

        if (is_array($config)) {
            return $config;
        }

        $laravelBasePath = o2switch_env('LARAVEL_BASE_PATH');

        if ($laravelBasePath === null || $laravelBasePath === '') {
            $laravelBasePath = is_file(__DIR__ . '/laravel/vendor/autoload.php')
                ? __DIR__ . '/laravel'
                : dirname(__DIR__);
        }

        $storageRoot = dirname(__DIR__) . '/storage';
        $pdfStoragePath = o2switch_env('PDF_STORAGE_PATH', $storageRoot . '/pdfs');

        $config = [
            'app_env' => o2switch_env('APP_ENV', 'production'),
            'app_debug' => filter_var(o2switch_env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL),
            'app_url' => rtrim((string) o2switch_env('APP_URL', 'https://cavaliersherouville.fr'), '/'),
            'db_host' => (string) o2switch_env('DB_HOST', 'localhost'),
            'db_port' => (int) o2switch_env('DB_PORT', '3306'),
            'db_name' => (string) o2switch_env('DB_DATABASE', (string) o2switch_env('DB_NAME', '')),
            'db_user' => (string) o2switch_env('DB_USERNAME', (string) o2switch_env('DB_USER', '')),
            'db_pass' => (string) o2switch_env('DB_PASSWORD', (string) o2switch_env('DB_PASS', '')),
            'db_charset' => (string) o2switch_env('DB_CHARSET', 'utf8mb4'),
            'mail_host' => (string) o2switch_env('MAIL_HOST', 'cobalte.o2switch.net'),
            'mail_port' => (int) o2switch_env('MAIL_PORT', '465'),
            'mail_encryption' => (string) o2switch_env('MAIL_ENCRYPTION', 'ssl'),
            'mail_username' => (string) o2switch_env('MAIL_USERNAME', (string) o2switch_env('MAIL_FROM', '')),
            'mail_password' => (string) o2switch_env('MAIL_PASSWORD', ''),
            'mail_from' => (string) o2switch_env('MAIL_FROM', (string) o2switch_env('MAIL_FROM_ADDRESS', '')),
            'mail_from_name' => (string) o2switch_env('MAIL_FROM_NAME', "Cavaliers d'Herouville"),
            'pdf_storage_path' => (string) $pdfStoragePath,
            'lichess_base_url' => rtrim((string) o2switch_env('LICHESS_BASE_URL', 'https://lichess.org/api'), '/'),
            'chesscom_base_url' => rtrim((string) o2switch_env('CHESSCOM_BASE_URL', 'https://api.chess.com/pub'), '/'),
            'api_cache_ttl' => max(60, (int) o2switch_env('API_CACHE_TTL', '3600')),
            'api_user_agent' => (string) o2switch_env('API_USER_AGENT', 'cavaliersherouville.fr contact@cavaliersherouville.fr'),
            'newsletter_batch_size' => max(1, (int) o2switch_env('NEWSLETTER_BATCH_SIZE', '20')),
            'newsletter_delivery_mode' => (string) o2switch_env('NEWSLETTER_DELIVERY_MODE', 'queue'),
            'newsletter_public_base_url' => rtrim((string) o2switch_env('NEWSLETTER_PUBLIC_BASE_URL', (string) o2switch_env('APP_URL', '')), '/'),
            'laravel_base_path' => rtrim((string) $laravelBasePath, '/\\'),
        ];

        return $config;
    }

    function o2switch_pdo(): PDO
    {
        static $pdo = null;

        if ($pdo instanceof PDO) {
            return $pdo;
        }

        $config = o2switch_app_config();
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['db_host'],
            $config['db_port'],
            $config['db_name'],
            $config['db_charset']
        );

        try {
            $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (Throwable $exception) {
            throw new RuntimeException('Connexion a la base impossible. Verifie la configuration o2switch.', 0, $exception);
        }

        return $pdo;
    }

    function o2switch_ensure_directory(string $dossier): void
    {
        if ($dossier !== '' && ! is_dir($dossier)) {
            mkdir($dossier, 0755, true);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    function o2switch_api_cache_get(PDO $pdo, string $service, string $cacheKey): ?array
    {
        try {
            $requete = $pdo->prepare('SELECT payload_json, expires_at FROM api_cache WHERE cache_key = :cache_key AND service_name = :service_name LIMIT 1');
            $requete->execute([
                ':cache_key' => $cacheKey,
                ':service_name' => $service,
            ]);
            $ligne = $requete->fetch();

            if (! is_array($ligne) || strtotime((string) ($ligne['expires_at'] ?? '')) < time()) {
                return null;
            }

            $payload = json_decode((string) ($ligne['payload_json'] ?? ''), true);

            return is_array($payload) ? $payload : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed>|null $metadata
     */
    function o2switch_api_cache_put(
        PDO $pdo,
        string $service,
        string $cacheKey,
        array $payload,
        int $ttl,
        int $httpStatus = 200,
        ?array $metadata = null
    ): void {
        $expireLe = date('Y-m-d H:i:s', time() + max(60, $ttl));
        $updatedAt = date('Y-m-d H:i:s');

        $requete = $pdo->prepare(
            'INSERT INTO api_cache (cache_key, service_name, payload_json, http_status, metadata_json, expires_at, updated_at)
             VALUES (:cache_key, :service_name, :payload_json, :http_status, :metadata_json, :expires_at, :updated_at)
             ON DUPLICATE KEY UPDATE
                service_name = VALUES(service_name),
                payload_json = VALUES(payload_json),
                http_status = VALUES(http_status),
                metadata_json = VALUES(metadata_json),
                expires_at = VALUES(expires_at),
                updated_at = VALUES(updated_at)'
        );

        $requete->execute([
            ':cache_key' => $cacheKey,
            ':service_name' => $service,
            ':payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
            ':http_status' => $httpStatus,
            ':metadata_json' => $metadata !== null ? (json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}') : null,
            ':expires_at' => $expireLe,
            ':updated_at' => $updatedAt,
        ]);
    }

    function o2switch_require_laravel_base_path(): string
    {
        $base = (string) (o2switch_app_config()['laravel_base_path'] ?? '');

        if ($base === '' || ! is_file($base . '/vendor/autoload.php') || ! is_file($base . '/bootstrap/app.php')) {
            throw new RuntimeException('Base Laravel introuvable pour les scripts o2switch.');
        }

        return $base;
    }
}
