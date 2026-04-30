<?php

declare(strict_types=1);

$sessionDirectory = __DIR__ . '/donnees/sessions';
$useSecureCookies = (
    (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443')
);

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');

    if (!is_dir($sessionDirectory)) {
        mkdir($sessionDirectory, 0777, true);
    }

    if (is_dir($sessionDirectory) && is_writable($sessionDirectory)) {
        session_save_path($sessionDirectory);
    }

    session_name('association_echecs_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => $useSecureCookies,
    ]);

    session_start();
}

require_once __DIR__ . '/modeles/SiteModel.php';
require_once __DIR__ . '/modeles/JsonStore.php';
require_once __DIR__ . '/modeles/UserRepository.php';
require_once __DIR__ . '/modeles/ArticleRepository.php';
require_once __DIR__ . '/modeles/ChessDotComService.php';
require_once __DIR__ . '/controleurs/ActionController.php';
require_once __DIR__ . '/controleurs/PageController.php';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function route_url(string $slug, array $query = []): string
{
    $normalizedSlug = trim($slug, '/');
    $path = $normalizedSlug === '' || $normalizedSlug === 'accueil'
        ? '/'
        : '/' . rawurlencode($normalizedSlug);

    if ($query === []) {
        return $path;
    }

    return $path . '?' . http_build_query($query);
}

function asset_url(string $path): string
{
    return '/' . ltrim(str_replace('\\', '/', $path), '/');
}

function redirect_to(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function current_theme(): string
{
    $theme = isset($_COOKIE['site_theme']) ? (string) $_COOKIE['site_theme'] : 'light';

    return $theme === 'dark' ? 'dark' : 'light';
}

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf_token(mixed $token): bool
{
    return is_string($token) && hash_equals(csrf_token(), $token);
}

function push_flash(string $type, string $message): void
{
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message,
    ];
}

function pull_flash_messages(): array
{
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);

    return is_array($messages) ? $messages : [];
}

function set_form_state(array $state): void
{
    $_SESSION['form_state'] = $state;
}

function pull_form_state(): array
{
    $state = $_SESSION['form_state'] ?? [];
    unset($_SESSION['form_state']);

    return is_array($state) ? $state : [];
}

$usersStore = new JsonStore(__DIR__ . '/donnees/users.json');
$articlesStore = new JsonStore(__DIR__ . '/donnees/articles.json');

$userRepository = new UserRepository($usersStore);
$articleRepository = new ArticleRepository($articlesStore);
$actionController = new ActionController($userRepository, $articleRepository);
$actionController->handle();

$requestedPage = isset($_GET['page']) ? (string) $_GET['page'] : 'accueil';
$flashMessages = pull_flash_messages();
$formState = pull_form_state();
$chessDotComService = new ChessDotComService(
    __DIR__ . '/donnees/cache/chesscom',
    'association-echecs-site/1.0'
);

$controller = new PageController(
    new SiteModel(),
    $userRepository,
    $articleRepository,
    $chessDotComService,
    $flashMessages,
    $formState
);

echo $controller->handle($requestedPage);
