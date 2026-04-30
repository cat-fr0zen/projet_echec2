<?php
$navigationPrincipale = $donneesSite['navigation_principale'] ?? $donneesSite['primary_nav'] ?? [];
$navigationSecondaire = $donneesSite['navigation_secondaire'] ?? $donneesSite['secondary_nav'] ?? [];
$donneesAuthentification = $donneesSite['authentification'];
?>

<header class="site-header reveal reveal-1" data-site-header>
    <div class="header-rail">
        <div class="brand-lockup">
            <a class="brand" href="<?= e(url_route('accueil')) ?>"><?= e($donneesSite['brand']) ?></a>
            <p class="brand-caption"><?= e($donneesSite['ville'] ?? $donneesSite['city'] ?? '') ?></p>
        </div>

        <div class="header-main-nav">
            <nav class="primary-nav" aria-label="Navigation principale">
                <?php foreach ($navigationPrincipale as $elementNavigation): ?>
                    <?php $estActive = $elementNavigation['slug'] === $pageCourante; ?>
                    <a
                        class="nav-link<?= $estActive ? ' is-active' : '' ?>"
                        href="<?= e(url_route($elementNavigation['slug'])) ?>"
                        <?= $estActive ? 'aria-current="page"' : '' ?>
                    >
                        <?= e($elementNavigation['label']) ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>

        <div class="header-actions">
            <?php if ($donneesAuthentification['est_connecte']): ?>
                <a class="header-cta" href="<?= e(url_route('profil')) ?>">Profil</a>
            <?php else: ?>
                <button type="button" class="header-cta" data-auth-open data-auth-tab="connexion">Connexion</button>
            <?php endif; ?>

            <button
                type="button"
                class="theme-toggle"
                data-theme-toggle
                aria-label="<?= $donneesSite['theme'] === 'dark' ? 'Activer le thème clair' : 'Activer le thème sombre' ?>"
                aria-pressed="<?= $donneesSite['theme'] === 'dark' ? 'true' : 'false' ?>"
            >
                <span class="theme-icon theme-icon--sun" aria-hidden="true">☀</span>
                <span class="theme-icon theme-icon--moon" aria-hidden="true">☾</span>
            </button>

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
    </div>

        <div id="burger-panel" class="burger-panel" data-burger-panel hidden aria-label="Menu secondaire">
        <div class="burger-columns">
            <section class="burger-group">
                <p class="eyebrow">Navigation</p>
                <div class="burger-links">
                    <?php foreach ($navigationSecondaire as $elementNavigation): ?>
                        <a class="burger-link" href="<?= e(url_route($elementNavigation['slug'])) ?>"><?= e($elementNavigation['label']) ?></a>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="burger-group">
                <p class="eyebrow">Espace membre</p>
                <?php if ($donneesAuthentification['est_connecte']): ?>
                    <div class="burger-user-card">
                        <p class="burger-user-name"><?= e($donneesAuthentification['nom_affichage']) ?></p>
                        <p class="burger-user-mail"><?= e($donneesAuthentification['utilisateur']['courriel'] ?? '') ?></p>
                    </div>
                    <div class="burger-links">
                        <a class="burger-link" href="<?= e(url_route('profil')) ?>">Profil</a>
                        <a class="burger-link" href="<?= e(url_route('parametres')) ?>">Paramètres</a>
                    </div>
                    <form method="post" action="<?= e(url_route($pageCourante)) ?>" class="burger-logout-form">
                        <input type="hidden" name="action" value="deconnexion">
                        <input type="hidden" name="jeton_csrf" value="<?= e($donneesSite['jeton_csrf']) ?>">
                        <button type="submit" class="button button-secondary burger-logout-button">Déconnexion</button>
                    </form>
                <?php else: ?>
                    <p class="burger-helper">Connecte-toi pour accéder au profil, aux réglages et à la rédaction d’articles.</p>
                    <button type="button" class="button button-primary" data-auth-open data-auth-tab="connexion">Connexion</button>
                <?php endif; ?>
            </section>
        </div>
    </div>
</header>


