<?php

declare(strict_types=1);

$racineProjet = dirname(__DIR__);
$echecs = [];

function verifier(bool $condition, string $message): void
{
    global $echecs;

    if (!$condition) {
        $echecs[] = $message;
    }
}

function contenu_fichier_obligatoire(string $chemin): string
{
    $contenu = file_get_contents($chemin);

    if ($contenu === false) {
        throw new RuntimeException('Lecture impossible: ' . $chemin);
    }

    return $contenu;
}

$index = contenu_fichier_obligatoire($racineProjet . '/index.php');
$baseOracle = contenu_fichier_obligatoire($racineProjet . '/MVC/modeles/BaseDeDonneesOracle.php');

verifier(!str_contains($index, 'new StockageJson'), 'index.php ne doit plus instancier le stockage JSON metier.');
verifier(!preg_match('/donnees[\\\\\/][a-z_]+\.json/', $index), 'index.php ne doit plus pointer vers les fichiers JSON metier.');
verifier(str_contains($index, 'BaseDeDonneesOracle::depuisEnvironnement'), 'index.php doit construire les depots depuis Oracle.');
verifier(!str_contains($index, '/donnees/cache/'), 'index.php ne doit plus configurer de cache JSON dans donnees/cache.');

verifier(str_contains($baseOracle, 'ORACLE_CLIENT_MIN_VERSION'), 'La connexion Oracle doit verifier une version cliente minimale.');
verifier(str_contains($baseOracle, 'oci_client_version'), 'La connexion Oracle doit lire la version du client oci8.');

$schemaOracle19c = contenu_fichier_obligatoire($racineProjet . '/base_de_donnees/oracle/19c/schema.sql');
$schemaMinuscule = strtolower($schemaOracle19c);

verifier(str_contains($schemaMinuscule, 'schema oracle 19c'), 'Le schema Oracle 19c doit etre la cible officielle.');
verifier(str_contains($schemaMinuscule, 'schema_migration'), 'Le schema Oracle 19c doit tracer les migrations.');
verifier(str_contains($schemaMinuscule, 'audit_changement_base'), 'Le schema Oracle 19c doit tracer les changements ulterieurs.');
verifier(str_contains($schemaMinuscule, 'newsletter_abonnement'), 'Le schema Oracle 19c doit documenter la newsletter.');

$install19c = contenu_fichier_obligatoire($racineProjet . '/base_de_donnees/oracle/19c/install_19c.sql');
verifier(str_contains($install19c, '@@precheck_19c.sql'), 'install_19c.sql doit lancer les pre-checks.');
verifier(str_contains($install19c, '@@security_verify.sql'), 'install_19c.sql doit lancer la verification securite.');

verifier(!is_dir($racineProjet . '/base_de_donnees/oracle/19c/migrations'), 'La cible Oracle 19c doit rester lisible avec un baseline unique, sans ancien dossier migrations.');

$verificationSecurite = contenu_fichier_obligatoire($racineProjet . '/base_de_donnees/oracle/19c/security_verify.sql');
$verificationMinuscule = strtolower($verificationSecurite);
verifier(str_contains($verificationMinuscule, 'user_tab_privs_made'), 'La verification securite doit inspecter les privileges sortants.');
verifier(str_contains($verificationMinuscule, 'user_constraints'), 'La verification securite doit inspecter les contraintes.');
verifier(str_contains($verificationMinuscule, 'user_indexes'), 'La verification securite doit inspecter les index.');
verifier(!str_contains($verificationMinuscule, 'grant dba'), 'Aucun script de securite ne doit recommander GRANT DBA.');

$journalChangements = contenu_fichier_obligatoire($racineProjet . '/base_de_donnees/oracle/19c/change_journal_template.sql');
foreach (['CHECK', 'PLAN', 'APPLY', 'VERIFY', 'ROLLBACK'] as $section) {
    verifier(str_contains($journalChangements, $section), 'Le modele de changement doit contenir la section ' . $section . '.');
}

$htaccessRacine = contenu_fichier_obligatoire($racineProjet . '/.htaccess');
$htaccessDonnees = contenu_fichier_obligatoire($racineProjet . '/donnees/.htaccess');
$htaccessRuntime = contenu_fichier_obligatoire($racineProjet . '/stockage_runtime/.htaccess');
$htaccessUploads = contenu_fichier_obligatoire($racineProjet . '/ressources/media/uploads/.htaccess');

verifier(str_contains($htaccessRacine, 'Options -Indexes'), 'La racine Apache doit desactiver le listing.');
verifier(str_contains($htaccessDonnees, 'Require all denied'), 'Le dossier donnees doit etre inaccessible en HTTP.');
verifier(str_contains($htaccessRuntime, 'Require all denied'), 'Le dossier stockage_runtime doit etre inaccessible en HTTP.');
verifier(str_contains($htaccessUploads, 'FilesMatch'), 'Le dossier uploads doit bloquer les scripts executables.');
verifier(str_contains($htaccessUploads, 'php|phtml|phar'), 'Les uploads doivent bloquer les extensions PHP.');

if ($echecs !== []) {
    fwrite(STDERR, "Echecs Oracle-only runtime:\n");

    foreach ($echecs as $echec) {
        fwrite(STDERR, '- ' . $echec . "\n");
    }

    exit(1);
}

echo "Runtime Oracle 19c-only et durcissement Apache OK.\n";
