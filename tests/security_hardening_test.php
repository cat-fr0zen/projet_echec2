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
$serviceChessCom = contenu_fichier($racineProjet . '/MVC/modeles/ServiceChessCom.php');
$serviceGoogleAvis = contenu_fichier($racineProjet . '/MVC/modeles/ServiceGoogleAvis.php');
$schemaOracle19c = contenu_fichier($racineProjet . '/base_de_donnees/oracle/19c/schema.sql');
$verificationOracle19c = contenu_fichier($racineProjet . '/base_de_donnees/oracle/19c/security_verify.sql');
$journalOracle19c = contenu_fichier($racineProjet . '/base_de_donnees/oracle/19c/change_journal_template.sql');
$installOracle19c = contenu_fichier($racineProjet . '/base_de_donnees/oracle/19c/install_19c.sql');
$readmeOracle19c = contenu_fichier($racineProjet . '/base_de_donnees/oracle/19c/README.md');
$schemaOracle19cMinuscule = strtolower($schemaOracle19c);

verifier(!str_contains($index, '0777'), 'index.php ne doit pas creer le dossier de session en 0777.');
verifier(!str_contains($controleurActions, '0777'), 'ControleurActions ne doit pas creer les dossiers upload en 0777.');
verifier(!str_contains($serviceChessCom, '0777'), 'ServiceChessCom ne doit pas creer de cache en 0777.');
verifier(!str_contains($serviceGoogleAvis, '0777'), 'ServiceGoogleAvis ne doit pas creer de cache en 0777.');
verifier(str_contains($index, 'Content-Security-Policy'), 'index.php doit envoyer une Content-Security-Policy.');
verifier(str_contains($index, 'X-Content-Type-Options: nosniff'), 'index.php doit envoyer X-Content-Type-Options.');
verifier(str_contains($index, 'X-Frame-Options: SAMEORIGIN'), 'index.php doit envoyer X-Frame-Options.');
verifier(str_contains($index, 'Referrer-Policy: strict-origin-when-cross-origin'), 'index.php doit envoyer Referrer-Policy.');
verifier(str_contains($index, 'Permissions-Policy: camera=(), microphone=(), geolocation=()'), 'index.php doit envoyer Permissions-Policy.');

verifier(str_contains($schemaOracle19c, 'MERGE INTO ref_role_compte'), 'Le schema 19c doit installer les roles de reference.');
verifier(str_contains($schemaOracle19c, 'MERGE INTO ref_type_bloc_article'), 'Le schema 19c doit installer les types de blocs article.');
verifier(str_contains($schemaOracle19cMinuscule, 'numero_licence_federale'), 'Le schema 19c doit contenir le numero de licence federale.');
verifier(str_contains($schemaOracle19cMinuscule, 'create unique index uq_compte_membre_licence_ffe'), 'La licence federale doit etre unique quand elle est renseignee.');
verifier(str_contains($schemaOracle19cMinuscule, 'newsletter_abonnement'), 'Le schema 19c doit contenir les abonnements newsletter.');
verifier(str_contains($verificationOracle19c, 'user_tab_privs_made'), 'La verification 19c doit inspecter les privileges sortants.');
verifier(str_contains($journalOracle19c, 'CHECK -> PLAN -> APPLY -> VERIFY -> ROLLBACK'), 'Le modele de changement 19c doit cadrer les futures migrations.');
verifier(str_contains($installOracle19c, '@@precheck_19c.sql'), 'Oracle 19c doit avoir des pre-checks avant installation.');
verifier(str_contains($installOracle19c, '@@security_verify.sql'), 'Oracle 19c doit lancer la verification securite apres installation.');
verifier(str_contains($readmeOracle19c, 'extension=oci8_19'), 'La documentation Oracle 19c doit expliquer l extension oci8_19.');
verifier(!str_contains(strtolower($readmeOracle19c), 'grant dba to'), 'La documentation Oracle 19c ne doit pas recommander GRANT DBA.');
verifier(!is_dir($racineProjet . '/base_de_donnees/oracle/10g'), 'Le dossier Oracle 10g obsolete doit etre supprime.');
verifier(!is_dir($racineProjet . '/base_de_donnees/oracle/v2'), 'Le dossier Oracle v2 obsolete doit etre supprime.');

if ($echecs !== []) {
    fwrite(STDERR, "Echecs de durcissement:\n");

    foreach ($echecs as $echec) {
        fwrite(STDERR, '- ' . $echec . "\n");
    }

    exit(1);
}

echo "Durcissement securite/migrations OK.\n";
