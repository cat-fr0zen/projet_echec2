<?php
/**
 * Vue: Mise en page (layout global).
 *
 * Cette vue sert de gabarit HTML commun a toutes les pages:
 * - charge le CSS/JS
 * - affiche l'entete + pied de page
 * - affiche les messages flash
 * - inclut la vue de page demandee (`$fichierVue`)
 *
 * Variables attendues (fournies par le controleur de pages):
 * - $donneesSite: donnees globales (theme, utilisateur, etc.)
 * - $donneesPage: metadonnees de la page (title/intro/SEO)
 * - $metaTitre / $descriptionMeta: SEO
 * - $fichierVue: chemin du fichier de vue "page" a inclure
 */
$theme = $donneesSite['theme'];
$messagesFlash = $donneesSite['messages_flash'] ?? [];
$siteData = $donneesSite;
$pageData = $donneesPage;
$metaTitle = $metaTitre;
$metaDescription = $descriptionMeta;
$viewFile = $fichierVue;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e($descriptionMeta) ?>">
    <title><?= e($metaTitre) ?></title>
    <link rel="stylesheet" href="<?= e(url_ressource('ressources/styles/style.css')) ?>">
</head>
<body data-theme="<?= e($theme) ?>">
    <a class="skip-link" href="#main-content">Aller au contenu</a>
    <div class="page-noise" aria-hidden="true"></div>
    <div class="site-shell">
        <?php require __DIR__ . '/partiels/entete.php'; ?>

        <?php if ($messagesFlash !== []): ?>
            <div class="flash-stack" aria-live="polite">
                <?php foreach ($messagesFlash as $messageFlash): ?>
                    <div class="flash-message flash-message--<?= e($messageFlash['type'] ?? 'info') ?>">
                        <?= e($messageFlash['message'] ?? '') ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <main id="main-content" class="page-shell" tabindex="-1">
            <?php require $fichierVue; ?>
        </main>
    </div>
    <?php require __DIR__ . '/partiels/pied-de-page.php'; ?>
    <?php require __DIR__ . '/partiels/modale-authentification.php'; ?>
    <?php require __DIR__ . '/partiels/consentement.php'; ?>
    <script src="<?= e(url_ressource('ressources/scripts/site.js')) ?>" defer></script>
</body>
</html>


