<?php

declare(strict_types=1);

$root = realpath(__DIR__.'/..');

if ($root === false) {
    fwrite(STDERR, "Impossible de determiner la racine du projet.\n");
    exit(1);
}

$buildRoot = $root.DIRECTORY_SEPARATOR.'build'.DIRECTORY_SEPARATOR.'o2switch-package';
$appRoot = $buildRoot.DIRECTORY_SEPARATOR.'app';
$laravelRoot = $appRoot.DIRECTORY_SEPARATOR.'laravel';
$publicRoot = $buildRoot.DIRECTORY_SEPARATOR.'public_html';
$storageRoot = $buildRoot.DIRECTORY_SEPARATOR.'storage';
$pdfRoot = $storageRoot.DIRECTORY_SEPARATOR.'pdfs';

supprimerRecursif($buildRoot);
creerDossier($laravelRoot);
creerDossier($publicRoot);
creerDossier($publicRoot.DIRECTORY_SEPARATOR.'admin');
creerDossier($publicRoot.DIRECTORY_SEPARATOR.'assets');
creerDossier($pdfRoot);

$exclusionsRacine = [
    '.git',
    'build',
    'lancement',
    'node_modules',
    'runtime',
    'scripts',
    'tests',
    'public_html',
    'vendor',
    '.env',
    '.env.o2switch.example',
    'DEPLOIEMENT_O2SWITCH.md',
    '.phpunit.result.cache',
    'phpunit.xml',
];

copierRecursifProjet($root, $laravelRoot, $exclusionsRacine);

copierFichier($root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'config.php', $appRoot.DIRECTORY_SEPARATOR.'config.php');
copierFichier($root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'mailer.php', $appRoot.DIRECTORY_SEPARATOR.'mailer.php');
copierFichier($root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'api_lichess.php', $appRoot.DIRECTORY_SEPARATOR.'api_lichess.php');
copierFichier($root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'api_chesscom.php', $appRoot.DIRECTORY_SEPARATOR.'api_chesscom.php');
copierRecursifSimple($root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'cron', $appRoot.DIRECTORY_SEPARATOR.'cron');

copierFichier($root.DIRECTORY_SEPARATOR.'.env.o2switch.example', $appRoot.DIRECTORY_SEPARATOR.'.env.o2switch.example');
copierFichier($root.DIRECTORY_SEPARATOR.'DEPLOIEMENT_O2SWITCH.md', $buildRoot.DIRECTORY_SEPARATOR.'DEPLOIEMENT_O2SWITCH.md');

copierFichier($root.DIRECTORY_SEPARATOR.'public_html'.DIRECTORY_SEPARATOR.'index.php', $publicRoot.DIRECTORY_SEPARATOR.'index.php');
copierFichier($root.DIRECTORY_SEPARATOR.'public_html'.DIRECTORY_SEPARATOR.'.htaccess', $publicRoot.DIRECTORY_SEPARATOR.'.htaccess');
copierFichier($root.DIRECTORY_SEPARATOR.'public_html'.DIRECTORY_SEPARATOR.'upload_pdf.php', $publicRoot.DIRECTORY_SEPARATOR.'upload_pdf.php');
copierFichier($root.DIRECTORY_SEPARATOR.'public_html'.DIRECTORY_SEPARATOR.'download_pdf.php', $publicRoot.DIRECTORY_SEPARATOR.'download_pdf.php');
copierFichier($root.DIRECTORY_SEPARATOR.'public_html'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'index.php', $publicRoot.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'index.php');
copierRecursifSimple($root.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'assets', $publicRoot.DIRECTORY_SEPARATOR.'assets');

file_put_contents($pdfRoot.DIRECTORY_SEPARATOR.'.gitkeep', '');

$resume = [
    'build_root' => normaliserSeparateurs($buildRoot),
    'app' => normaliserSeparateurs($appRoot),
    'laravel' => normaliserSeparateurs($laravelRoot),
    'public_html' => normaliserSeparateurs($publicRoot),
    'storage' => normaliserSeparateurs($storageRoot),
];

echo json_encode($resume, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;

function copierRecursifProjet(string $source, string $destination, array $exclusionsRacine): void
{
    $elements = scandir($source);

    if ($elements === false) {
        throw new RuntimeException('Impossible de lire '.$source);
    }

    foreach ($elements as $element) {
        if ($element === '.' || $element === '..') {
            continue;
        }

        if (in_array($element, $exclusionsRacine, true)) {
            continue;
        }

        $sourcePath = $source.DIRECTORY_SEPARATOR.$element;
        $destinationPath = $destination.DIRECTORY_SEPARATOR.$element;

        if (is_dir($sourcePath)) {
            if ($element === 'storage') {
                copierStorageProduction($sourcePath, $destinationPath);
                continue;
            }

            copierRecursifSimple($sourcePath, $destinationPath);
            continue;
        }

        copierFichier($sourcePath, $destinationPath);
    }
}

function copierStorageProduction(string $source, string $destination): void
{
    creerDossier($destination);
    creerDossier($destination.DIRECTORY_SEPARATOR.'logs');
    creerDossier($destination.DIRECTORY_SEPARATOR.'framework');
    creerDossier($destination.DIRECTORY_SEPARATOR.'app');
    creerDossier($destination.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'private');
    creerDossier($destination.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'private'.DIRECTORY_SEPARATOR.'uploads');

    $gitkeep = $source.DIRECTORY_SEPARATOR.'.gitkeep';

    if (is_file($gitkeep)) {
        copierFichier($gitkeep, $destination.DIRECTORY_SEPARATOR.'.gitkeep');
    }
}

function copierRecursifSimple(string $source, string $destination): void
{
    if (is_file($source)) {
        copierFichier($source, $destination);
        return;
    }

    creerDossier($destination);

    $elements = scandir($source);

    if ($elements === false) {
        throw new RuntimeException('Impossible de lire '.$source);
    }

    foreach ($elements as $element) {
        if ($element === '.' || $element === '..') {
            continue;
        }

        $sourcePath = $source.DIRECTORY_SEPARATOR.$element;
        $destinationPath = $destination.DIRECTORY_SEPARATOR.$element;

        if (is_dir($sourcePath)) {
            copierRecursifSimple($sourcePath, $destinationPath);
            continue;
        }

        copierFichier($sourcePath, $destinationPath);
    }
}

function copierFichier(string $source, string $destination): void
{
    if (! is_file($source)) {
        throw new RuntimeException('Fichier introuvable: '.$source);
    }

    creerDossier(dirname($destination));

    if (! copy($source, $destination)) {
        throw new RuntimeException('Copie impossible: '.$source.' -> '.$destination);
    }
}

function creerDossier(string $path): void
{
    if (is_dir($path)) {
        return;
    }

    if (! mkdir($path, 0777, true) && ! is_dir($path)) {
        throw new RuntimeException('Creation impossible: '.$path);
    }
}

function supprimerRecursif(string $path): void
{
    if (! file_exists($path)) {
        return;
    }

    if (is_file($path)) {
        unlink($path);
        return;
    }

    $elements = scandir($path);

    if ($elements === false) {
        throw new RuntimeException('Impossible de lire '.$path);
    }

    foreach ($elements as $element) {
        if ($element === '.' || $element === '..') {
            continue;
        }

        supprimerRecursif($path.DIRECTORY_SEPARATOR.$element);
    }

    rmdir($path);
}

function normaliserSeparateurs(string $path): string
{
    return str_replace('\\', '/', $path);
}
