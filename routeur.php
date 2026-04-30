<?php

declare(strict_types=1);

$racineProjet = __DIR__;
$uriDemandee = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';
$cheminDemande = parse_url($uriDemandee, PHP_URL_PATH);
$cheminNormalise = '/' . ltrim(rawurldecode(is_string($cheminDemande) ? $cheminDemande : '/'), '/');

$prefixesBloques = [
    '/MVC/',
    '/controleurs/',
    '/donnees/',
    '/journaux/',
    '/modeles/',
    '/vues/',
];

$prefixesStatiquesAutorises = [
    '/ressources/',
];

foreach ($prefixesBloques as $prefixe) {
    if (str_starts_with($cheminNormalise, $prefixe)) {
        http_response_code(404);
        echo 'Not Found';

        return true;
    }
}

$fichiersBloques = [
    '/README.md',
    '/routeur.php',
    '/start-server.ps1',
];

if (in_array($cheminNormalise, $fichiersBloques, true)) {
    http_response_code(404);
    echo 'Not Found';

    return true;
}

$fichierDemande = $racineProjet . str_replace('/', DIRECTORY_SEPARATOR, $cheminNormalise);

if (is_file($fichierDemande)) {
    foreach ($prefixesStatiquesAutorises as $prefixe) {
        if (str_starts_with($cheminNormalise, $prefixe)) {
            return false;
        }
    }

    if ($cheminNormalise === '/index.php') {
        if (!isset($_GET['page']) || !is_string($_GET['page']) || trim($_GET['page']) === '') {
            $_GET['page'] = 'accueil';
        }

        require $racineProjet . '/index.php';

        return true;
    }

    http_response_code(404);
    echo 'Not Found';

    return true;
}

$_GET['page'] = $cheminNormalise === '/' ? 'accueil' : trim($cheminNormalise, '/');

require $racineProjet . '/index.php';

return true;
