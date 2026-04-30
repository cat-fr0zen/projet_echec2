<section class="page-banner reveal reveal-2">
    <p class="eyebrow">Erreur 404</p>
    <h1>Page introuvable</h1>
    <p><?= e($pageData['message']) ?></p>
    <div class="button-row">
        <a class="button button-primary" href="<?= e(route_url('accueil')) ?>">Revenir à l'accueil</a>
    </div>
</section>
