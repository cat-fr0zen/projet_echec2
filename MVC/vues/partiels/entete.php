<?php
/**
 * Partiel: Entete (header).
 *
 * Navigation principale + actions (theme, authentification, lien dashboard).
 * Les donnees de navigation viennent du modele "site" (ModeleSite).
 */
$navigationPrincipale = $donneesSite['navigation_principale'] ?? $donneesSite['primary_nav'] ?? [];
$navigationSecondaire = $donneesSite['navigation_secondaire'] ?? $donneesSite['secondary_nav'] ?? [];
$donneesAuthentification = $donneesSite['authentification'];
$themeSunIconUrl = url_ressource('ressources/media/image/theme-soleil.svg');
$themeMoonIconUrl = url_ressource('ressources/media/image/theme-lune.svg');
?>

<header class="site-header reveal reveal-1" data-site-header>
    <div class="header-rail">
        <div class="brand-lockup">
            <div class="brand-copy">
                <a class="brand" href="<?= e(url_route('accueil')) ?>"><?= e($donneesSite['brand']) ?></a>
                <p class="brand-caption"><?= e($donneesSite['ville'] ?? $donneesSite['city'] ?? '') ?></p>
            </div>
            <img
                class="brand-logo"
                src="<?= e($logoClubUrl) ?>"
                alt=""
                aria-hidden="true"
                width="394"
                height="401"
            >
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
                <span class="theme-icon theme-icon--sun" aria-hidden="true">
                    <img class="theme-icon-image" src="<?= e($themeSunIconUrl) ?>" alt="">
                </span>
                <span class="theme-icon theme-icon--moon" aria-hidden="true">
                    <img class="theme-icon-image" src="<?= e($themeMoonIconUrl) ?>" alt="">
                </span>
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

    <div
        id="burger-panel"
        class="burger-panel"
        data-burger-panel
        hidden
        role="dialog"
        aria-modal="true"
        aria-labelledby="burger-panel-title"
        aria-hidden="true"
    >
        <div class="burger-panel-topbar">
            <div class="burger-panel-heading">
                <p class="burger-panel-title" id="burger-panel-title">
                    <?= e($donneesAuthentification['est_connecte'] ? $donneesAuthentification['nom_affichage'] : 'Menu secondaire') ?>
                </p>
                <?php if ($donneesAuthentification['est_connecte']): ?>
                    <p class="burger-panel-role"><?= e($donneesAuthentification['role_label'] ?? 'Compte') ?></p>
                <?php endif; ?>
            </div>
            <button type="button" class="burger-panel-close" data-burger-close aria-label="Fermer le menu">
                Fermer
            </button>
        </div>
        <div class="burger-columns">
            <?php if ($navigationPrincipale): ?>
                <section class="burger-group burger-group--primary-mobile">
                    <p class="eyebrow">Pages principales</p>
                    <nav class="burger-links" aria-label="Navigation principale du menu">
                        <?php foreach ($navigationPrincipale as $elementNavigation): ?>
                            <?php $estActive = $elementNavigation['slug'] === $pageCourante; ?>
                            <a
                                class="burger-link<?= $estActive ? ' is-active' : '' ?>"
                                href="<?= e(url_route($elementNavigation['slug'])) ?>"
                                <?= $estActive ? 'aria-current="page"' : '' ?>
                            >
                                <?= e($elementNavigation['label']) ?>
                            </a>
                        <?php endforeach; ?>
                    </nav>
                </section>
            <?php endif; ?>

            <?php if ($navigationSecondaire): ?>
                <section class="burger-group">
                    <p class="eyebrow">Menu secondaire</p>
                    <nav class="burger-links" aria-label="Navigation secondaire du menu">
                        <?php foreach ($navigationSecondaire as $elementNavigation): ?>
                            <a class="burger-link" href="<?= e(url_route($elementNavigation['slug'])) ?>"><?= e($elementNavigation['label']) ?></a>
                        <?php endforeach; ?>
                    </nav>
                </section>
            <?php endif; ?>

            <section class="burger-group">
                <p class="eyebrow">Espace membre</p>
                <?php if ($donneesAuthentification['est_connecte']): ?>
                    <div class="burger-links">
                        <a class="burger-link" href="<?= e(url_route('profil')) ?>">Profil</a>
                        <a class="burger-link" href="<?= e(url_route('parametres')) ?>">Paramètres</a>
                        <?php if ($donneesAuthentification['est_admin'] ?? false): ?>
                            <a class="burger-link" href="<?= e(url_route('admin')) ?>">Administration</a>
                        <?php endif; ?>
                    </div>
                    <form method="post" action="<?= e(url_route($pageCourante)) ?>" class="burger-logout-form">
                        <input type="hidden" name="action" value="deconnexion">
                        <input type="hidden" name="jeton_csrf" value="<?= e($donneesSite['jeton_csrf']) ?>">
                        <button type="submit" class="button button-secondary burger-logout-button">Deconnexion</button>
                    </form>
                <?php else: ?>
                    <p class="burger-helper">Connecte-toi pour accéder aux guides, à la boutique et à ton profil membre.</p>
                    <button type="button" class="button button-primary" data-auth-open data-auth-tab="connexion">Connexion</button>
                <?php endif; ?>
            </section>
        </div>
    </div>
</header>
