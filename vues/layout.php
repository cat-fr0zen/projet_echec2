<?php
$theme = $siteData['theme'];
$flashMessages = $siteData['flash_messages'] ?? [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e($metaDescription) ?>">
    <title><?= e($metaTitle) ?></title>
    <link rel="stylesheet" href="<?= e(asset_url('ressources/styles/style.css')) ?>">
</head>
<body data-theme="<?= e($theme) ?>">
    <a class="skip-link" href="#main-content">Aller au contenu</a>
    <div class="page-noise" aria-hidden="true"></div>
    <div class="site-shell">
        <?php require __DIR__ . '/partiels/header.php'; ?>

        <?php if ($flashMessages !== []): ?>
            <div class="flash-stack" aria-live="polite">
                <?php foreach ($flashMessages as $flashMessage): ?>
                    <div class="flash-message flash-message--<?= e($flashMessage['type'] ?? 'info') ?>">
                        <?= e($flashMessage['message'] ?? '') ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <main id="main-content" class="page-shell" tabindex="-1">
            <?php require $viewFile; ?>
        </main>

        <?php require __DIR__ . '/partiels/footer.php'; ?>
    </div>
    <?php require __DIR__ . '/partiels/auth-modal.php'; ?>
    <?php require __DIR__ . '/partiels/consent.php'; ?>
    <script src="<?= e(asset_url('ressources/scripts/site.js')) ?>" defer></script>
</body>
</html>
