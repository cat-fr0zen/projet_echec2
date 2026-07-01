<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/config.php';

session_start();

$pdo = o2switch_pdo();
$identifiantDocument = trim((string) ($_GET['id'] ?? ''));

if ($identifiantDocument === '') {
    http_response_code(404);
    exit('Document introuvable.');
}

$requete = $pdo->prepare('SELECT * FROM documents WHERE document_id = ? LIMIT 1');
$requete->execute([$identifiantDocument]);
$document = $requete->fetch();

if (! is_array($document)) {
    http_response_code(404);
    exit('Document introuvable.');
}

$identifiantUtilisateur = trim((string) ($_SESSION['identifiant_utilisateur'] ?? ''));
$peutVoir = (int) ($document['is_public'] ?? 0) === 1;

if (! $peutVoir && $identifiantUtilisateur !== '') {
    $requeteRole = $pdo->prepare('SELECT role FROM compte_membre WHERE identifiant = ? LIMIT 1');
    $requeteRole->execute([$identifiantUtilisateur]);
    $role = (string) ($requeteRole->fetchColumn() ?: '');
    $peutVoir = in_array($role, ['admin', 'prof'], true) || $identifiantUtilisateur === (string) ($document['author_identifier'] ?? '');
}

if (! $peutVoir) {
    http_response_code(403);
    exit('Acces refuse.');
}

$dossier = (string) (o2switch_app_config()['pdf_storage_path'] ?? '');
$chemin = rtrim($dossier, '/\\') . DIRECTORY_SEPARATOR . (string) ($document['relative_path'] ?? '');

if (! is_file($chemin)) {
    http_response_code(404);
    exit('Fichier manquant.');
}

header('Content-Type: application/pdf');
header('Content-Length: ' . (string) filesize($chemin));
header('Content-Disposition: inline; filename="' . basename((string) ($document['original_filename'] ?? 'document.pdf')) . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($chemin);
