<?php
$primaryNav = $siteData['primary_nav'];
$secondaryNav = $siteData['secondary_nav'];
$authData = $siteData['auth'];
?>

<header class="site-header reveal reveal-1">
    <div class="brand-lockup">
        <p class="eyebrow">Association d'échecs de proximité</p>
        <a class="brand" href="<?= e(route_url('accueil')) ?>"><?= e($siteData['brand']) ?></a>
        <p class="brand-caption"><?= e($siteData['city']) ?></p>
    </div>

    <div class="header-main-nav">
        <nav class="primary-nav" aria-label="Navigation principale">
            <?php foreach ($primaryNav as $item): ?>
                <?php $isActive = $item['slug'] === $currentPage; ?>
                <a
                    class="nav-link<?= $isActive ? ' is-active' : '' ?>"
                    href="<?= e(route_url($item['slug'])) ?>"
                    <?= $isActive ? 'aria-current="page"' : '' ?>
                >
                    <?= e($item['label']) ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>

    <div class="header-actions">
        <button
            type="button"
            class="theme-toggle"
            data-theme-toggle
            aria-label="<?= $siteData['theme'] === 'dark' ? 'Activer le thème clair' : 'Activer le thème sombre' ?>"
            aria-pressed="<?= $siteData['theme'] === 'dark' ? 'true' : 'false' ?>"
        >
            <span class="theme-icon theme-icon--sun" aria-hidden="true">☀</span>
            <span class="theme-icon theme-icon--moon" aria-hidden="true">☾</span>
        </button>

        <?php if ($authData['is_authenticated']): ?>
            <a class="header-cta" href="<?= e(route_url('profil')) ?>">Mon profil</a>
        <?php else: ?>
            <button type="button" class="header-cta" data-auth-open data-auth-tab="login">Connexion</button>
        <?php endif; ?>

        <button
            type="button"
            class="burger-toggle"
            data-burger-toggle
            aria-expanded="false"
            aria-controls="burger-panel"
            aria-label="Ouvrir le menu"
        >
            <span class="burger-label">Menu</span>
            <span class="burger-lines" aria-hidden="true">
                <span></span>
                <span></span>
                <span></span>
            </span>
        </button>
    </div>

    <div id="burger-panel" class="burger-panel" data-burger-panel hidden aria-label="Menu secondaire">
        <div class="burger-columns">
            <section class="burger-group">
                <p class="eyebrow">Navigation</p>
                <div class="burger-links">
                    <?php foreach ($secondaryNav as $item): ?>
                        <a class="burger-link" href="<?= e(route_url($item['slug'])) ?>"><?= e($item['label']) ?></a>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="burger-group">
                <p class="eyebrow">Espace membre</p>
                <?php if ($authData['is_authenticated']): ?>
                    <div class="burger-user-card">
                        <p class="burger-user-name"><?= e($authData['display_name']) ?></p>
                        <p class="burger-user-mail"><?= e($authData['user']['email'] ?? '') ?></p>
                    </div>
                    <div class="burger-links">
                        <a class="burger-link" href="<?= e(route_url('profil')) ?>">Profil</a>
                        <a class="burger-link" href="<?= e(route_url('parametres')) ?>">Paramètres</a>
                    </div>
                    <form method="post" action="<?= e(route_url($currentPage)) ?>" class="burger-logout-form">
                        <input type="hidden" name="action" value="logout">
                        <input type="hidden" name="csrf_token" value="<?= e($siteData['csrf_token']) ?>">
                        <button type="submit" class="button button-secondary burger-logout-button">Déconnexion</button>
                    </form>
                <?php else: ?>
                    <p class="burger-helper">Connecte-toi pour accéder au profil, aux réglages et à la rédaction d'articles.</p>
                    <div class="burger-auth-actions">
                        <button type="button" class="button button-primary" data-auth-open data-auth-tab="login">Connexion</button>
                        <button type="button" class="button button-secondary" data-auth-open data-auth-tab="register">Inscription</button>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>
</header>
