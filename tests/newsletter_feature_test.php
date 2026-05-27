<?php

declare(strict_types=1);

$racineProjet = dirname(__DIR__);
$echecs = [];

function verifier_newsletter(bool $condition, string $message): void
{
    global $echecs;

    if (!$condition) {
        $echecs[] = $message;
    }
}

function lire_newsletter(string $chemin): string
{
    $contenu = file_get_contents($chemin);

    if ($contenu === false) {
        throw new RuntimeException('Lecture impossible: ' . $chemin);
    }

    return $contenu;
}

$index = lire_newsletter($racineProjet . '/index.php');
$controleurActions = lire_newsletter($racineProjet . '/MVC/controleurs/ControleurActions.php');
$footer = lire_newsletter($racineProjet . '/MVC/vues/partiels/pied-de-page.php');
$admin = lire_newsletter($racineProjet . '/MVC/vues/pages/admin.php');
$schema = lire_newsletter($racineProjet . '/base_de_donnees/oracle/19c/schema.sql');
$install = lire_newsletter($racineProjet . '/base_de_donnees/oracle/19c/install_19c.sql');
$envExample = lire_newsletter($racineProjet . '/.env.example');

verifier_newsletter(file_exists($racineProjet . '/MVC/modeles/DepotNewsletterOracle.php'), 'Le depot Oracle newsletter doit exister.');
verifier_newsletter(file_exists($racineProjet . '/MVC/modeles/ServiceNewsletterMailer.php'), 'Le service mail newsletter doit exister.');
verifier_newsletter(file_exists($racineProjet . '/base_de_donnees/oracle/19c/schema.sql'), 'Le baseline Oracle 19c doit exister.');

verifier_newsletter(str_contains($index, 'DepotNewsletterOracle.php'), 'index.php doit charger le depot newsletter.');
verifier_newsletter(str_contains($index, 'ServiceNewsletterMailer.php'), 'index.php doit charger le service mail newsletter.');
verifier_newsletter(str_contains($index, 'new DepotNewsletterOracle'), 'index.php doit instancier le depot newsletter.');
verifier_newsletter(str_contains($index, 'new ServiceNewsletterMailer'), 'index.php doit instancier le mailer newsletter.');

verifier_newsletter(str_contains($controleurActions, 'newsletter_subscribe'), 'Le controleur doit accepter l action newsletter_subscribe.');
verifier_newsletter(str_contains($controleurActions, 'traiterInscriptionNewsletter'), 'Le controleur doit traiter l inscription newsletter.');
verifier_newsletter(str_contains($controleurActions, 'notifierArticlePublie'), 'La publication article doit notifier la newsletter.');
verifier_newsletter(str_contains($controleurActions, 'notifierHorairesMisAJour'), 'La mise a jour horaires doit notifier la newsletter.');
verifier_newsletter(str_contains($controleurActions, 'notify_shop_item'), 'La boutique doit pouvoir notifier une nouveaute aux abonnes.');
verifier_newsletter(str_contains($admin, 'admin-newsletter-boutique'), 'L administration doit exposer le declencheur newsletter boutique.');

verifier_newsletter(!str_contains($footer, 'newsletterMailto'), 'Le footer ne doit plus utiliser mailto pour la newsletter.');
verifier_newsletter(str_contains($footer, 'type="email"'), 'Le formulaire newsletter doit demander un email.');
verifier_newsletter(str_contains($footer, 'name="newsletter_consentement"'), 'Le consentement newsletter doit etre explicite.');
verifier_newsletter(str_contains($footer, 'aria-describedby="newsletter-help newsletter-consent-help"'), 'Le champ email doit etre decrit pour les lecteurs d ecran.');

$schemaMinuscule = strtolower($schema);
verifier_newsletter(str_contains($schemaMinuscule, 'newsletter_abonnement'), 'Le schema doit contenir newsletter_abonnement.');
verifier_newsletter(str_contains($schemaMinuscule, 'newsletter_envoi'), 'Le schema doit contenir newsletter_envoi.');
verifier_newsletter(str_contains($schemaMinuscule, 'jeton_desabonnement'), 'La newsletter doit prevoir un jeton de desabonnement.');
verifier_newsletter(str_contains($schemaMinuscule, 'consentement_version'), 'La newsletter doit tracer la version du consentement.');
verifier_newsletter(str_contains($schemaMinuscule, 'adresse_ip_hachee'), 'La newsletter doit tracer une preuve minimisee du consentement.');

verifier_newsletter(str_contains($install, '@@schema.sql'), 'install_19c.sql doit lancer le baseline newsletter inclus dans le schema.');
verifier_newsletter(str_contains($envExample, 'MAIL_FROM_ADDRESS'), '.env.example doit documenter l adresse noreply.');
verifier_newsletter(str_contains($envExample, 'NEWSLETTER_PUBLIC_BASE_URL'), '.env.example doit documenter l URL publique pour les liens.');

if ($echecs !== []) {
    fwrite(STDERR, "Echecs newsletter:\n");

    foreach ($echecs as $echec) {
        fwrite(STDERR, '- ' . $echec . "\n");
    }

    exit(1);
}

echo "Newsletter automatique OK.\n";
