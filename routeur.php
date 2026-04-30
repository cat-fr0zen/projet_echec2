<?php

declare(strict_types=1);

$projectRoot = __DIR__;
$requestUri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';
$requestPath = parse_url($requestUri, PHP_URL_PATH);
$normalizedPath = '/' . ltrim(rawurldecode(is_string($requestPath) ? $requestPath : '/'), '/');

$blockedPrefixes = [
    '/controleurs/',
    '/donnees/',
    '/journaux/',
    '/modeles/',
    '/vues/',
];

$allowedStaticPrefixes = [
    '/ressources/',
];

// Le routeur sert uniquement les ressources publiques et renvoie tout le reste vers l'entrée MVC.
foreach ($blockedPrefixes as $prefix) {
    if (str_starts_with($normalizedPath, $prefix)) {
        http_response_code(404);
        echo 'Not Found';

        return true;
    }
}

$blockedFiles = [
    '/README.md',
    '/routeur.php',
    '/start-server.ps1',
];

if (in_array($normalizedPath, $blockedFiles, true)) {
    http_response_code(404);
    echo 'Not Found';

    return true;
}

$requestedFile = $projectRoot . str_replace('/', DIRECTORY_SEPARATOR, $normalizedPath);

if (is_file($requestedFile)) {
    foreach ($allowedStaticPrefixes as $prefix) {
        if (str_starts_with($normalizedPath, $prefix)) {
            return false;
        }
    }

    if ($normalizedPath === '/index.php') {
        if (!isset($_GET['page']) || !is_string($_GET['page']) || trim($_GET['page']) === '') {
            $_GET['page'] = 'accueil';
        }

        require $projectRoot . '/index.php';

        return true;
    }

    http_response_code(404);
    echo 'Not Found';

    return true;
}

$_GET['page'] = $normalizedPath === '/' ? 'accueil' : trim($normalizedPath, '/');

require $projectRoot . '/index.php';

return true;
