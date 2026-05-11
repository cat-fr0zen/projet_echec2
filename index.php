<?php

declare(strict_types=1);

/**
 * Point d'entree unique (Front Controller).
 *
 * Role:
 * - initialise la session PHP et les cookies de session
 * - charge les classes (modeles/depots/services/controleurs)
 * - expose quelques helpers globaux (echappement, URLs, CSRF, flash)
 * - instancie les depots (stockage JSON), puis execute:
 *   1) le controleur d'actions (POST)
 *   2) le controleur de pages (GET) pour produire le HTML
 *
 * Donnees:
 * - ce prototype utilise des fichiers JSON dans `donnees/` (pas de base Oracle en runtime)
 */

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
require_once __DIR__ . '/MVC/modeles/DepotMedias.php';
require_once __DIR__ . '/MVC/modeles/DepotCommandes.php';
require_once __DIR__ . '/MVC/modeles/DepotDammier.php';
require_once __DIR__ . '/MVC/modeles/ServiceChessCom.php';
require_once __DIR__ . '/MVC/modeles/ServiceGoogleAvis.php';
require_once __DIR__ . '/MVC/controleurs/ControleurActions.php';
require_once __DIR__ . '/MVC/controleurs/ControleurPages.php';

/**
 * Echappe une chaine pour un affichage HTML sur (XSS prevention).
 *
 * @param ?string $valeur Valeur a afficher.
 * @return string Valeur echappee en UTF-8.
 */
function e(?string $valeur): string
{
    return htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
}

/**
 * Construit une URL "propre" basee sur un segment de page (routeur unique).
 *
 * Exemple:
 * - url_route('accueil') => '/'
 * - url_route('articles') => '/articles'
 * - url_route('articles', ['q' => 'x']) => '/articles?q=x'
 *
 * @param string $segment Segment de page (slug).
 * @param array $parametres Parametres querystring.
 * @return string URL relative.
 */
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

/**
 * Construit une URL publique vers une ressource statique (CSS/JS/uploads).
 *
 * @param string $chemin Chemin relatif a la racine du projet.
 * @return string URL relative web (slash normalise).
 */
function url_ressource(string $chemin): string
{
    return '/' . ltrim(str_replace('\\', '/', $chemin), '/');
}

/**
 * Redirige le navigateur et stoppe l'execution.
 *
 * @param string $url URL cible.
 * @return never
 */
function rediriger_vers(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/**
 * Lit le theme courant a partir du cookie `site_theme`.
 *
 * @return string 'light' ou 'dark'
 */
function theme_courant(): string
{
    $theme = isset($_COOKIE['site_theme']) ? (string) $_COOKIE['site_theme'] : 'light';

    return $theme === 'dark' ? 'dark' : 'light';
}

/**
 * Retourne (et cree si besoin) un jeton CSRF en session.
 *
 * Utilisation:
 * - injecter `jeton_csrf()` dans les formulaires
 * - verifier via `verifier_jeton_csrf(...)` dans les actions POST
 *
 * @return string Jeton aleatoire hex.
 */
function jeton_csrf(): string
{
    if (!isset($_SESSION['jeton_csrf']) || !is_string($_SESSION['jeton_csrf'])) {
        $_SESSION['jeton_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['jeton_csrf'];
}

/**
 * Verifie qu'un jeton fourni correspond au jeton de session.
 *
 * @param mixed $jeton Jeton fourni par le formulaire.
 * @return bool True si valide.
 */
function verifier_jeton_csrf(mixed $jeton): bool
{
    return is_string($jeton) && hash_equals(jeton_csrf(), $jeton);
}

/**
 * Ajoute un message "flash" (1 affichage) en session.
 *
 * @param string $type 'success' | 'error' | 'info'
 * @param string $message Texte a afficher.
 */
function ajouter_message_flash(string $type, string $message): void
{
    $_SESSION['messages_flash'][] = [
        'type' => $type,
        'message' => $message,
    ];
}

/**
 * Recupere les messages flash, puis les efface.
 *
 * @return array Liste de messages.
 */
function recuperer_messages_flash(): array
{
    $messages = $_SESSION['messages_flash'] ?? [];
    unset($_SESSION['messages_flash']);

    return is_array($messages) ? $messages : [];
}

/**
 * Memorise l'etat d'un formulaire (ex: erreurs, valeurs) dans la session.
 *
 * @param array $etat Etat serialisable.
 */
function memoriser_etat_formulaire(array $etat): void
{
    $_SESSION['etat_formulaire'] = $etat;
}

/**
 * Recupere l'etat du formulaire memorise, puis le supprime.
 *
 * @return array Etat serialisable.
 */
function recuperer_etat_formulaire(): array
{
    $etat = $_SESSION['etat_formulaire'] ?? [];
    unset($_SESSION['etat_formulaire']);

    return is_array($etat) ? $etat : [];
}

$stockageUtilisateurs = new StockageJson(__DIR__ . '/donnees/utilisateurs.json');
$stockageArticles = new StockageJson(__DIR__ . '/donnees/articles.json');
$stockageMedias = new StockageJson(__DIR__ . '/donnees/medias.json');
$stockageCommandes = new StockageJson(__DIR__ . '/donnees/commandes.json');
$stockageDammierPuzzles = new StockageJson(__DIR__ . '/donnees/dammier_puzzles.json');
$stockageDammierClassements = new StockageJson(__DIR__ . '/donnees/dammier_classements.json');

$depotUtilisateurs = new DepotUtilisateurs($stockageUtilisateurs);
$depotArticles = new DepotArticles($stockageArticles);
$depotMedias = new DepotMedias($stockageMedias);
$depotCommandes = new DepotCommandes($stockageCommandes);
$depotDammier = new DepotDammier($stockageDammierPuzzles, $stockageDammierClassements);
$controleurActions = new ControleurActions($depotUtilisateurs, $depotArticles, $depotMedias, $depotCommandes, $depotDammier, __DIR__ . '/ressources/media/uploads');
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
$cleGooglePlaces = getenv('GOOGLE_PLACES_API_KEY');
$serviceGoogleAvis = new ServiceGoogleAvis(
    __DIR__ . '/donnees/cache/google-avis',
    is_string($cleGooglePlaces) ? $cleGooglePlaces : '',
    'association-echecs-site/1.0'
);

$controleurPages = new ControleurPages(
    new ModeleSite(),
    $depotUtilisateurs,
    $depotArticles,
    $depotMedias,
    $depotCommandes,
    $depotDammier,
    $serviceChessCom,
    $serviceGoogleAvis,
    $messagesFlash,
    $etatFormulaire
);

echo $controleurPages->afficher($pageDemandee);
