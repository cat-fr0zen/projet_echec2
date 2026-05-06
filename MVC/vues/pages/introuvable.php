<?php
/**
 * Vue: Introuvable (404).
 *
 * Affiche un message d'erreur et un lien de retour vers l'accueil.
 */
?>
<section class="page-banner reveal reveal-2">
    <p class="eyebrow">Erreur 404</p>
    <h1>Page introuvable</h1>
    <p><?= e($donneesPage['message']) ?></p>
    <div class="button-row">
        <a class="button button-primary" href="<?= e(url_route('accueil')) ?>">Revenir à l'accueil</a>
    </div>
</section>

