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
$stylePath = public_path('assets/styles/style.css');
$siteScriptPath = public_path('assets/scripts/site.js');
$dammierScriptPath = public_path('assets/scripts/dammier.js');
$logoClubPath = public_path('assets/media/divers/Logo_LCH2025.png');
$styleUrl = url_ressource('assets/styles/style.css') . '?v=' . (string) @filemtime($stylePath);
$siteScriptUrl = url_ressource('assets/scripts/site.js') . '?v=' . (string) @filemtime($siteScriptPath);
$dammierScriptUrl = url_ressource('assets/scripts/dammier.js') . '?v=' . (string) @filemtime($dammierScriptPath);
$logoClubUrl = url_ressource('assets/media/divers/Logo_LCH2025.png') . '?v=' . (string) @filemtime($logoClubPath);
$cspNonce = (string) request()->attributes->get('csp_nonce', '');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e($descriptionMeta) ?>">
    <title><?= e($metaTitre) ?></title>
    <link rel="icon" type="image/png" href="<?= e($logoClubUrl) ?>">
    <link rel="apple-touch-icon" href="<?= e($logoClubUrl) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Source+Sans+3:wght@400;500;600;700&display=swap"
    >
    <link rel="stylesheet" href="<?= e($styleUrl) ?>">
</head>
<body data-theme="<?= e($theme) ?>">
    <a class="skip-link" href="#main-content">Aller au contenu</a>
    <div class="page-noise" aria-hidden="true"></div>
    <div class="site-shell">
        <?php require dirname(__DIR__) . '/partials/entete.blade.php'; ?>

        <?php if ($messagesFlash !== []): ?>
            <div class="flash-stack" aria-live="polite">
                <?php foreach ($messagesFlash as $messageFlash): ?>
                    <?php
                    $typeFlash = (string) ($messageFlash['type'] ?? 'info');
                    $roleFlash = $typeFlash === 'error' ? 'alert' : 'status';
                    ?>
                    <div class="flash-message flash-message--<?= e($typeFlash) ?>" role="<?= e($roleFlash) ?>">
                        <p class="flash-message__text"><?= e($messageFlash['message'] ?? '') ?></p>
                        <button type="button" class="flash-message__dismiss" data-flash-dismiss aria-label="Fermer ce message">
                            Fermer
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <main id="main-content" class="page-shell" tabindex="-1">
            <?php require $fichierVue; ?>
        </main>
    </div>
    <?php require dirname(__DIR__) . '/partials/pied-de-page.blade.php'; ?>
    <?php require dirname(__DIR__) . '/partials/modale-authentification.blade.php'; ?>
    <?php require dirname(__DIR__) . '/partials/consentement.blade.php'; ?>
    <div
        class="confirm-modal"
        data-confirm-modal
        hidden
        aria-hidden="true"
        role="dialog"
        aria-modal="true"
        aria-labelledby="confirm-modal-title"
        aria-describedby="confirm-modal-description"
    >
        <div class="confirm-modal-panel" tabindex="-1">
            <button type="button" class="confirm-modal-close" data-confirm-modal-cancel aria-label="Fermer la fenêtre">&times;</button>
            <p class="eyebrow">Confirmation</p>
            <h2 id="confirm-modal-title">Confirmer la suppression</h2>
            <p id="confirm-modal-description" class="confirm-modal-description">
                Cette action supprimera définitivement l'élément sélectionné.
            </p>
            <p class="confirm-modal-warning">
                Vérifie bien avant de continuer : cette suppression ne pourra pas être annulée depuis l'interface.
            </p>
            <div class="confirm-modal-actions">
                <button type="button" class="button button-secondary" data-confirm-modal-cancel>Annuler</button>
                <button type="button" class="button button-danger" data-confirm-modal-submit>Oui, supprimer définitivement</button>
            </div>
        </div>
    </div>
    <script src="<?= e($siteScriptUrl) ?>" defer nonce="<?= e($cspNonce) ?>"></script>
    <script src="<?= e($dammierScriptUrl) ?>" defer nonce="<?= e($cspNonce) ?>"></script>
</body>
</html>
