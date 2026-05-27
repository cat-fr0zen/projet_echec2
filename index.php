<?php

declare(strict_types=1);

/**
 * Point d'entree unique (Front Controller).
 *
 * Role:
 * - initialise la session PHP et les cookies de session
 * - charge les classes (modeles/depots/services/controleurs)
 * - expose quelques helpers globaux (echappement, URLs, CSRF, flash)
 * - instancie les depots Oracle, puis execute:
 *   1) le controleur d'actions (POST)
 *   2) le controleur de pages (GET) pour produire le HTML
 *
 * Donnees:
 * - les donnees metier persistantes sont lues/ecrites en base Oracle.
 */

$dossierSessions = __DIR__ . '/stockage_runtime/sessions';
$utiliserCookiesSecurises = (
    (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443')
);

/**
 * Cree un dossier runtime avec des permissions restrictives.
 */
function creer_dossier_runtime(string $chemin, int $mode): bool
{
    if (!is_dir($chemin) && !mkdir($chemin, $mode, true)) {
        return false;
    }

    if (is_dir($chemin) && DIRECTORY_SEPARATOR !== '\\') {
        chmod($chemin, $mode);
    }

    return is_dir($chemin);
}

/**
 * Envoie les en-tetes de securite communs a toutes les pages HTML.
 */
function envoyer_entetes_securite(bool $connexionSecurisee): void
{
    $politiqueSecuriteContenu = implode('; ', [
        "default-src 'self'",
        "base-uri 'self'",
        "object-src 'none'",
        "frame-ancestors 'self'",
        "form-action 'self'",
        "script-src 'self'",
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
        "font-src 'self' https://fonts.gstatic.com",
        "img-src 'self' data: blob: https:",
        "media-src 'self' blob:",
        "frame-src https://www.google.com https://maps.google.com",
        "connect-src 'self'",
    ]);

    if ($connexionSecurisee) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }

    header('Content-Type: text/html; charset=UTF-8');
    header('Content-Security-Policy: ' . $politiqueSecuriteContenu);
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');

    if (creer_dossier_runtime($dossierSessions, 0700)) {
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

ini_set('default_charset', 'UTF-8');

if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

if (function_exists('mb_http_output')) {
    mb_http_output('UTF-8');
}

envoyer_entetes_securite($utiliserCookiesSecurises);

require_once __DIR__ . '/MVC/modeles/ModeleSite.php';
require_once __DIR__ . '/MVC/modeles/DepotUtilisateurs.php';
require_once __DIR__ . '/MVC/modeles/DepotArticles.php';
require_once __DIR__ . '/MVC/modeles/DepotMedias.php';
require_once __DIR__ . '/MVC/modeles/DepotCommandes.php';
require_once __DIR__ . '/MVC/modeles/DepotDammier.php';
require_once __DIR__ . '/MVC/modeles/DepotHoraires.php';
require_once __DIR__ . '/MVC/modeles/BaseDeDonneesOracle.php';
require_once __DIR__ . '/MVC/modeles/DepotUtilisateursOracle.php';
require_once __DIR__ . '/MVC/modeles/DepotArticlesOracle.php';
require_once __DIR__ . '/MVC/modeles/DepotMediasOracle.php';
require_once __DIR__ . '/MVC/modeles/DepotCommandesOracle.php';
require_once __DIR__ . '/MVC/modeles/DepotDammierOracle.php';
require_once __DIR__ . '/MVC/modeles/DepotHorairesOracle.php';
require_once __DIR__ . '/MVC/modeles/DepotNewsletterOracle.php';
require_once __DIR__ . '/MVC/modeles/ServiceChessCom.php';
require_once __DIR__ . '/MVC/modeles/ServiceGoogleAvis.php';
require_once __DIR__ . '/MVC/modeles/ServiceNewsletterMailer.php';
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
    return htmlspecialchars(normaliser_texte_utf8((string) $valeur), ENT_QUOTES, 'UTF-8');
}

/**
 * Repare les cas de mojibake les plus courants sans dependre de l'encodage
 * du terminal local.
 *
 * @param ?string $valeur Texte source.
 * @return string Texte normalise pour l'affichage UTF-8.
 */
function normaliser_texte_utf8(?string $valeur): string
{
    $texte = (string) $valeur;

    if ($texte === '') {
        return '';
    }

    if (!preg_match('//u', $texte) && function_exists('mb_convert_encoding')) {
        $texte = mb_convert_encoding($texte, 'UTF-8', 'Windows-1252,ISO-8859-1,UTF-8');
    }

    static $corrections = null;

    if ($corrections === null) {
        $corrections = [
            hex2bin('C383C2A0') => "\u{00E0}",
            hex2bin('C383C2A2') => "\u{00E2}",
            hex2bin('C383C2A4') => "\u{00E4}",
            hex2bin('C383C2A7') => "\u{00E7}",
            hex2bin('C383C2A8') => "\u{00E8}",
            hex2bin('C383C2A9') => "\u{00E9}",
            hex2bin('C383C2AA') => "\u{00EA}",
            hex2bin('C383C2AB') => "\u{00EB}",
            hex2bin('C383C2AE') => "\u{00EE}",
            hex2bin('C383C2AF') => "\u{00EF}",
            hex2bin('C383C2B4') => "\u{00F4}",
            hex2bin('C383C2B6') => "\u{00F6}",
            hex2bin('C383C2B9') => "\u{00F9}",
            hex2bin('C383C2BB') => "\u{00FB}",
            hex2bin('C383C2BC') => "\u{00FC}",
            hex2bin('C383E282AC') => "\u{00C0}",
            hex2bin('C383E280A1') => "\u{00C7}",
            hex2bin('C383CB86') => "\u{00C8}",
            hex2bin('C383E280B0') => "\u{00C9}",
            hex2bin('C385E28099') => "\u{0152}",
            hex2bin('C385E2809C') => "\u{0153}",
            hex2bin('C3A2E282ACE284A2') => "\u{2019}",
            hex2bin('C3A2E282ACC593') => "\u{201C}",
            hex2bin('C3A2E282ACC29D') => "\u{201D}",
            hex2bin('C3A2E282ACE28093') => "\u{2013}",
            hex2bin('C3A2E282ACE28094') => "\u{2014}",
            hex2bin('C3A2E282ACC2A6') => "\u{2026}",
            hex2bin('C382C2AB') => "\u{00AB}",
            hex2bin('C382C2BB') => "\u{00BB}",
            hex2bin('C382C2B0') => "\u{00B0}",
            hex2bin('C382C2B7') => "\u{00B7}",
            hex2bin('C382') => '',
        ];
    }

    return strtr($texte, $corrections);
}

/**
 * Normalise recursivement toutes les chaines d'un tableau de donnees.
 *
 * @param mixed $valeur Structure scalaire / tableau.
 * @return mixed Structure nettoyee.
 */
function normaliser_structure_utf8(mixed $valeur): mixed
{
    if (is_array($valeur)) {
        $resultat = [];

        foreach ($valeur as $cle => $element) {
            $resultat[$cle] = normaliser_structure_utf8($element);
        }

        return $resultat;
    }

    return is_string($valeur) ? normaliser_texte_utf8($valeur) : $valeur;
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
        'message' => normaliser_texte_utf8($message),
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

/**
 * Termine proprement si Oracle n'est pas configure.
 *
 * Le detail exact reste dans les logs PHP pour ne pas exposer la configuration.
 *
 * @return never
 */
function terminer_sur_erreur_configuration_base(Throwable $exception): never
{
    error_log('[oracle-runtime] ' . $exception->getMessage());
    http_response_code(500);

    echo '<!doctype html><html lang="fr"><head><meta charset="UTF-8"><title>Base indisponible</title></head>';
    echo '<body style="font-family: sans-serif; padding: 2rem; line-height: 1.5;">';
    echo '<h1>Base de donnees indisponible</h1>';
    echo '<p>Le site est configure pour fonctionner uniquement avec Oracle 19c. Verifie l extension PHP oci8_19, Instant Client 19c et les variables ORACLE_HOST, ORACLE_SERVICE, ORACLE_USER et ORACLE_PASSWORD dans XAMPP.</p>';
    echo '</body></html>';

    exit;
}

try {
    $baseDeDonnees = BaseDeDonneesOracle::depuisEnvironnement();

    $depotUtilisateurs = new DepotUtilisateursOracle($baseDeDonnees);
    $depotArticles = new DepotArticlesOracle($baseDeDonnees);
    $depotMedias = new DepotMediasOracle($baseDeDonnees);
    $depotCommandes = new DepotCommandesOracle($baseDeDonnees);
    $depotDammier = new DepotDammierOracle($baseDeDonnees);
    $depotHoraires = new DepotHorairesOracle($baseDeDonnees);
    $depotNewsletter = new DepotNewsletterOracle($baseDeDonnees);
    $serviceNewsletterMailer = new ServiceNewsletterMailer(
        $depotNewsletter,
        (string) (getenv('MAIL_FROM_ADDRESS') ?: 'noreply@cavaliers-herouville.fr'),
        (string) (getenv('MAIL_FROM_NAME') ?: "Cavaliers d'Herouville"),
        (string) (getenv('NEWSLETTER_PUBLIC_BASE_URL') ?: '/')
    );

    $jetonDesabonnementNewsletter = trim((string) ($_GET['newsletter_unsubscribe'] ?? ''));
    if ($jetonDesabonnementNewsletter !== '') {
        $desabonnementEffectue = $depotNewsletter->desabonner($jetonDesabonnementNewsletter);
        ajouter_message_flash(
            $desabonnementEffectue ? 'success' : 'error',
            $desabonnementEffectue
                ? 'Votre desabonnement a bien ete pris en compte.'
                : 'Le lien de desabonnement est invalide ou deja utilise.'
        );
        rediriger_vers(url_route('accueil') . '#footer-newsletter-title');
    }

    $controleurActions = new ControleurActions(
        $depotUtilisateurs,
        $depotArticles,
        $depotMedias,
        $depotCommandes,
        $depotDammier,
        $depotHoraires,
        __DIR__ . '/ressources/media/uploads',
        $depotNewsletter,
        $serviceNewsletterMailer
    );
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
    $serviceChessCom = new ServiceChessCom(null, 'association-echecs-site/1.0');
    $cleGooglePlaces = getenv('GOOGLE_PLACES_API_KEY');
    $serviceGoogleAvis = new ServiceGoogleAvis(
        null,
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
        $depotHoraires,
        $serviceChessCom,
        $serviceGoogleAvis,
        $messagesFlash,
        $etatFormulaire
    );

    echo $controleurPages->afficher($pageDemandee);
} catch (Throwable $exception) {
    terminer_sur_erreur_configuration_base($exception);
}
