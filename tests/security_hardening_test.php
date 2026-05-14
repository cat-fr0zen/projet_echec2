<?php

declare(strict_types=1);

$racineProjet = dirname(__DIR__);
$echecs = [];

/**
 * @param bool $condition Condition attendue.
 * @param string $message Message affiche en cas d'echec.
 */
function verifier(bool $condition, string $message): void
{
    global $echecs;

    if (!$condition) {
        $echecs[] = $message;
    }
}

function contenu_fichier(string $chemin): string
{
    $contenu = file_get_contents($chemin);

    if ($contenu === false) {
        throw new RuntimeException('Lecture impossible: ' . $chemin);
    }

    return $contenu;
}

$index = contenu_fichier($racineProjet . '/index.php');
$controleurActions = contenu_fichier($racineProjet . '/MVC/controleurs/ControleurActions.php');
$migrationPackages = contenu_fichier($racineProjet . '/base_de_donnees/oracle/v2/migrations/2.0.5_views_and_packages.sql');
$migrationSeed = contenu_fichier($racineProjet . '/base_de_donnees/oracle/v2/migrations/2.0.6_security_and_seed.sql');
$migrationArticles = contenu_fichier($racineProjet . '/base_de_donnees/oracle/v2/migrations/2.0.7_article_editor_blocks.sql');
$migrationArticlesMinuscule = strtolower($migrationArticles);

verifier(!str_contains($index, '0777'), 'index.php ne doit pas creer le dossier de session en 0777.');
verifier(!str_contains($controleurActions, '0777'), 'ControleurActions ne doit pas creer les dossiers upload en 0777.');
verifier(str_contains($index, 'Content-Security-Policy'), 'index.php doit envoyer une Content-Security-Policy.');
verifier(str_contains($index, 'X-Content-Type-Options: nosniff'), 'index.php doit envoyer X-Content-Type-Options.');
verifier(str_contains($index, 'X-Frame-Options: SAMEORIGIN'), 'index.php doit envoyer X-Frame-Options.');
verifier(str_contains($index, 'Referrer-Policy: strict-origin-when-cross-origin'), 'index.php doit envoyer Referrer-Policy.');
verifier(str_contains($index, 'Permissions-Policy: camera=(), microphone=(), geolocation=()'), 'index.php doit envoyer Permissions-Policy.');

verifier(!preg_match('/^\\s{8}COMMIT;$/m', $migrationPackages), 'Les packages PL/SQL ne doivent pas valider les transactions eux-memes.');
verifier(str_contains($migrationPackages, 'Transaction controlled by caller.'), 'Les packages doivent documenter le commit cote appelant.');

verifier(str_contains($migrationSeed, 'MERGE INTO ref_role_compte'), 'La seed 2.0.6 doit etre rejouable via MERGE.');
verifier(str_contains($migrationSeed, 'MERGE INTO parametre_application'), 'Les parametres applicatifs doivent etre rejouables via MERGE.');
verifier(str_contains($migrationSeed, 'COMMIT;'), 'La migration 2.0.6 doit valider explicitement son lot.');

verifier(str_contains($migrationArticlesMinuscule, 'user_tables'), 'La migration 2.0.7 doit verifier les tables avant creation.');
verifier(str_contains($migrationArticlesMinuscule, 'user_tab_columns'), 'La migration 2.0.7 doit verifier la colonne auteur_affiche avant ALTER TABLE.');
verifier(str_contains($migrationArticles, 'MERGE INTO ref_type_bloc_article'), 'Les types de blocs article doivent etre rejouables via MERGE.');
verifier(str_contains($migrationArticles, 'COMMIT;'), 'La migration 2.0.7 doit valider explicitement son lot.');

if ($echecs !== []) {
    fwrite(STDERR, "Echecs de durcissement:\n");

    foreach ($echecs as $echec) {
        fwrite(STDERR, '- ' . $echec . "\n");
    }

    exit(1);
}

echo "Durcissement securite/migrations OK.\n";
