<?php $merchCards = $siteData['merch_cards']; ?>

<section class="page-banner reveal reveal-2">
    <p class="eyebrow">Merch</p>
    <h1><?= e($pageData['title']) ?></h1>
    <p><?= e($pageData['intro']) ?></p>
</section>

<section class="section-block reveal reveal-3">
    <div class="section-head">
        <p class="eyebrow">Catalogue</p>
        <h2>Un espace de vente pret a etre publie.</h2>
        <p>Le merchandising pourra s afficher ici une fois les produits, tarifs et conditions de vente confirmes.</p>
    </div>

    <div class="card-grid card-grid--three">
        <?php foreach ($merchCards as $merchCard): ?>
            <article class="info-card merch-card">
                <p class="card-tag"><?= e($merchCard['type']) ?></p>
                <h3><?= e($merchCard['title']) ?></h3>
                <p><?= e($merchCard['text']) ?></p>
                <p class="status-pill"><?= e($merchCard['status']) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>
