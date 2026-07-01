<?php
/**
 * Vue: Introuvable (404).
 *
 * Affiche une page d'erreur simple et themed pour les URLs inconnues.
 */
?>
<section class="page-banner page-banner--error reveal reveal-2">
    <div class="error-page">
        <div class="error-page__visual" aria-hidden="true">
            <img
                class="error-page__logo"
                src="<?= e($logoClubUrl ?? url_ressource('assets/media/divers/Logo_LCH2025.png')) ?>"
                alt=""
                width="394"
                height="401"
            >
            <span class="error-page__badge">404</span>
        </div>

        <div class="error-page__content">
            <p class="eyebrow">Erreur 404</p>
            <h1>Vous êtes en échec &amp; mat.</h1>
            <p class="error-page__lead"><?= e($donneesPage['message']) ?></p>
            <p class="error-page__hint">La page demandée n'existe pas, a changé d'adresse ou n'est plus accessible.</p>

            <div class="button-row">
                <a class="button button-primary" href="<?= e(url_route('accueil')) ?>">Revenir à l'accueil</a>
                <a class="button button-secondary" href="<?= e(url_route('page.show', ['page' => 'contact'])) ?>">Contacter le club</a>
            </div>
        </div>
    </div>
</section>
