<?php

declare(strict_types=1);

$dossierSessions = __DIR__ . '/donnees/sessions';
$utiliserCookiesSecurises = (
    (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443')
);

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');

    if (!is_dir($dossierSessions)) {
        mkdir($dossierSessions, 0777, true);
    }

    if (is_dir($dossierSessions) && is_writable($dossierSessions)) {
        session_save_path($dossierSessions);
    }

    session_name('association_echecs_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => $utiliserCookiesSecurises,
    ]);

    session_start();
}

require_once __DIR__ . '/MVC/modeles/ModeleSite.php';
require_once __DIR__ . '/MVC/modeles/StockageJson.php';
require_once __DIR__ . '/MVC/modeles/DepotUtilisateurs.php';
require_once __DIR__ . '/MVC/modeles/DepotArticles.php';
require_once __DIR__ . '/MVC/modeles/ServiceChessCom.php';
require_once __DIR__ . '/MVC/controleurs/ControleurActions.php';
require_once __DIR__ . '/MVC/controleurs/ControleurPages.php';

function e(?string $valeur): string
{
    return htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
}

function url_route(string $segment, array $parametres = []): string
{
    $segmentNormalise = trim($segment, '/');
    $chemin = $segmentNormalise === '' || $segmentNormalise === 'accueil'
        ? '/'
        : '/' . rawurlencode($segmentNormalise);

    if ($parametres === []) {
        return $chemin;
    }

    return $chemin . '?' . http_build_query($parametres);
}

function url_ressource(string $chemin): string
{
    return '/' . ltrim(str_replace('\\', '/', $chemin), '/');
}

function rediriger_vers(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function theme_courant(): string
{
    $theme = isset($_COOKIE['site_theme']) ? (string) $_COOKIE['site_theme'] : 'light';

    return $theme === 'dark' ? 'dark' : 'light';
}

function jeton_csrf(): string
{
    if (!isset($_SESSION['jeton_csrf']) || !is_string($_SESSION['jeton_csrf'])) {
        $_SESSION['jeton_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['jeton_csrf'];
}

function verifier_jeton_csrf(mixed $jeton): bool
{
    return is_string($jeton) && hash_equals(jeton_csrf(), $jeton);
}

function ajouter_message_flash(string $type, string $message): void
{
    $_SESSION['messages_flash'][] = [
        'type' => $type,
        'message' => $message,
    ];
}

function recuperer_messages_flash(): array
{
    $messages = $_SESSION['messages_flash'] ?? [];
    unset($_SESSION['messages_flash']);

    return is_array($messages) ? $messages : [];
}

function memoriser_etat_formulaire(array $etat): void
{
    $_SESSION['etat_formulaire'] = $etat;
}

function recuperer_etat_formulaire(): array
{
    $etat = $_SESSION['etat_formulaire'] ?? [];
    unset($_SESSION['etat_formulaire']);

    return is_array($etat) ? $etat : [];
}

$stockageUtilisateurs = new StockageJson(__DIR__ . '/donnees/utilisateurs.json');
$stockageArticles = new StockageJson(__DIR__ . '/donnees/articles.json');

$depotUtilisateurs = new DepotUtilisateurs($stockageUtilisateurs);
$depotArticles = new DepotArticles($stockageArticles);
$controleurActions = new ControleurActions($depotUtilisateurs, $depotArticles);
$controleurActions->traiter();

$pageDemandee = isset($_GET['page']) ? (string) $_GET['page'] : 'accueil';
$aliasPages = [
    'merch' => 'boutique',
];

if (isset($aliasPages[$pageDemandee])) {
    $pageDemandee = $aliasPages[$pageDemandee];
}

$messagesFlash = recuperer_messages_flash();
$etatFormulaire = recuperer_etat_formulaire();
$serviceChessCom = new ServiceChessCom(
    __DIR__ . '/donnees/cache/chesscom',
    'association-echecs-site/1.0'
);

$controleurPages = new ControleurPages(
    new ModeleSite(),
    $depotUtilisateurs,
    $depotArticles,
    $serviceChessCom,
    $messagesFlash,
    $etatFormulaire
);

echo $controleurPages->afficher($pageDemandee);
