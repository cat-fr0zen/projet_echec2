<?php

declare(strict_types=1);

/**
 * Verification CLI appelee par lancer_site_usb.bat.
 *
 * Le script ne modifie rien en base. Il controle seulement:
 * - la presence de l'extension oci8;
 * - la version minimale du client Oracle;
 * - les variables .env necessaires;
 * - la connexion au schema applicatif.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

/**
 * @return array<string, string>
 */
function charger_env_cli(string $chemin): array
{
    if (!is_file($chemin) || !is_readable($chemin)) {
        throw new RuntimeException('Fichier .env introuvable ou illisible: ' . $chemin);
    }

    $variables = [];
    $lignes = file($chemin, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if ($lignes === false) {
        throw new RuntimeException('Lecture impossible du fichier .env: ' . $chemin);
    }

    foreach ($lignes as $ligne) {
        $ligne = trim($ligne);

        if ($ligne === '' || str_starts_with($ligne, '#')) {
            continue;
        }

        $positionSeparateur = strpos($ligne, '=');

        if ($positionSeparateur === false) {
            continue;
        }

        $cle = trim(substr($ligne, 0, $positionSeparateur));
        $valeur = trim(substr($ligne, $positionSeparateur + 1));

        if (!preg_match('/^[A-Z0-9_]+$/', $cle)) {
            continue;
        }

        if (
            strlen($valeur) >= 2
            && (($valeur[0] === '"' && str_ends_with($valeur, '"'))
                || ($valeur[0] === "'" && str_ends_with($valeur, "'")))
        ) {
            $valeur = substr($valeur, 1, -1);
        }

        $variables[$cle] = $valeur;

        if (getenv($cle) === false) {
            putenv($cle . '=' . $valeur);
        }
    }

    return $variables;
}

/**
 * @param array<string, string> $variables
 */
function valeur_env_cli(array $variables, string $cle, string $defaut = ''): string
{
    $valeurProcessus = getenv($cle);

    if (is_string($valeurProcessus) && $valeurProcessus !== '') {
        return $valeurProcessus;
    }

    return $variables[$cle] ?? $defaut;
}

function echouer_controle(string $message): never
{
    fwrite(STDERR, '[ERREUR] ' . $message . PHP_EOL);
    exit(1);
}

$cheminEnv = isset($argv[1]) && is_string($argv[1])
    ? $argv[1]
    : dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';

try {
    $variables = charger_env_cli($cheminEnv);
} catch (Throwable $exception) {
    echouer_controle($exception->getMessage());
}

if (!extension_loaded('oci8')) {
    echouer_controle("Extension PHP oci8 absente. Dans XAMPP, active extension=oci8_19 dans php.ini.");
}

$versionClient = function_exists('oci_client_version') ? (string) oci_client_version() : '';
$versionClientMajeure = (int) strtok($versionClient, '.');
$versionClientMinimale = (int) valeur_env_cli($variables, 'ORACLE_CLIENT_MIN_VERSION', '19');

if ($versionClientMinimale > 0 && $versionClientMajeure > 0 && $versionClientMajeure < $versionClientMinimale) {
    echouer_controle(
        'Client Oracle trop ancien: version ' . $versionClient . ', version minimale attendue '
        . $versionClientMinimale . '.'
    );
}

$clesObligatoires = [
    'ORACLE_HOST',
    'ORACLE_SERVICE',
    'ORACLE_USER',
    'ORACLE_PASSWORD',
];

foreach ($clesObligatoires as $cle) {
    if (valeur_env_cli($variables, $cle) === '') {
        echouer_controle('Variable manquante dans .env: ' . $cle . '.');
    }
}

if (valeur_env_cli($variables, 'ORACLE_PASSWORD') === 'change_me') {
    echouer_controle('ORACLE_PASSWORD vaut encore change_me dans .env.');
}

if (valeur_env_cli($variables, 'NEWSLETTER_CONSENT_SALT') === 'change_me_long_random_value') {
    echouer_controle('NEWSLETTER_CONSENT_SALT doit etre remplace par une valeur longue et aleatoire.');
}

$hote = valeur_env_cli($variables, 'ORACLE_HOST');
$port = valeur_env_cli($variables, 'ORACLE_PORT', '1521');
$service = valeur_env_cli($variables, 'ORACLE_SERVICE');
$utilisateur = valeur_env_cli($variables, 'ORACLE_USER');
$motDePasse = valeur_env_cli($variables, 'ORACLE_PASSWORD');
$charset = valeur_env_cli($variables, 'ORACLE_CHARSET', 'AL32UTF8');
$chaineConnexion = '//' . $hote . ':' . $port . '/' . $service;

$connexion = @oci_connect($utilisateur, $motDePasse, $chaineConnexion, $charset);

if ($connexion === false) {
    $erreur = oci_error();
    $message = is_array($erreur) && isset($erreur['message'])
        ? (string) $erreur['message']
        : 'Connexion Oracle impossible.';

    echouer_controle($message);
}

oci_close($connexion);

echo 'OCI8 OK - client Oracle ' . ($versionClient !== '' ? $versionClient : 'detecte') . PHP_EOL;
echo 'Connexion Oracle OK - ' . $utilisateur . '@' . $hote . ':' . $port . '/' . $service . PHP_EOL;
