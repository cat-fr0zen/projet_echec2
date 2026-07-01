<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/config.php';

session_start();

$pdo = o2switch_pdo();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$identifiantUtilisateur = trim((string) ($_SESSION['identifiant_utilisateur'] ?? ''));

if ($identifiantUtilisateur === '') {
    http_response_code(403);
    exit('Connexion requise.');
}

$requeteRole = $pdo->prepare('SELECT identifiant, role FROM compte_membre WHERE identifiant = ? LIMIT 1');
$requeteRole->execute([$identifiantUtilisateur]);
$utilisateur = $requeteRole->fetch();

if (! is_array($utilisateur) || ! in_array((string) ($utilisateur['role'] ?? ''), ['admin', 'prof'], true)) {
    http_response_code(403);
    exit('Droits insuffisants.');
}

$fichier = $_FILES['pdf'] ?? null;

if (! is_array($fichier) || (int) ($fichier['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    http_response_code(422);
    exit('Fichier PDF manquant.');
}

$tailleMax = 20 * 1024 * 1024;

if ((int) ($fichier['size'] ?? 0) > $tailleMax) {
    http_response_code(422);
    exit('Le PDF depasse 20 Mo.');
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = (string) $finfo->file((string) ($fichier['tmp_name'] ?? ''));

if ($mime !== 'application/pdf') {
    http_response_code(422);
    exit('Seuls les PDF sont acceptes.');
}

$dossier = (string) (o2switch_app_config()['pdf_storage_path'] ?? '');
o2switch_ensure_directory($dossier);

$nomStocke = 'pdf_' . bin2hex(random_bytes(16)) . '.pdf';
$chemin = rtrim($dossier, '/\\') . DIRECTORY_SEPARATOR . $nomStocke;

if (! move_uploaded_file((string) $fichier['tmp_name'], $chemin)) {
    http_response_code(500);
    exit('Impossible de stocker le PDF.');
}

$titre = trim((string) ($_POST['titre'] ?? pathinfo((string) ($fichier['name'] ?? 'document.pdf'), PATHINFO_FILENAME)));
$estPublic = (string) ($_POST['est_public'] ?? '0') === '1' ? 1 : 0;
$identifiantDocument = 'doc_' . bin2hex(random_bytes(10));
$maintenant = date('Y-m-d H:i:s');

$requeteInsertion = $pdo->prepare(
    'INSERT INTO documents
    (document_id, title, original_filename, stored_filename, mime_type, size_bytes, relative_path, is_public, author_identifier, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);
$requeteInsertion->execute([
    $identifiantDocument,
    $titre,
    (string) ($fichier['name'] ?? 'document.pdf'),
    $nomStocke,
    $mime,
    (int) ($fichier['size'] ?? 0),
    $nomStocke,
    $estPublic,
    $identifiantUtilisateur,
    $maintenant,
    $maintenant,
]);

header('Content-Type: application/json; charset=UTF-8');
echo json_encode([
    'success' => true,
    'document_id' => $identifiantDocument,
    'download_url' => '/download_pdf.php?id=' . rawurlencode($identifiantDocument),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
