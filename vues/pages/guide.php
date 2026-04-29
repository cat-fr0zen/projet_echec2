<?php $guideCards = $siteData['guide_cards']; ?>

<section class="page-banner reveal reveal-2">
    <p class="eyebrow">Guide</p>
    <h1><?= e($pageData['title']) ?></h1>
    <p><?= e($pageData['intro']) ?></p>
</section>

<section class="section-block reveal reveal-3">
    <div class="section-head">
        <p class="eyebrow">Strategie</p>
        <h2>Des cartes courtes pour progresser plus vite.</h2>
        <p>Chaque carte resume un principe utile a memoriser dans la pratique du jeu d echecs.</p>
    </div>

    <div class="card-grid card-grid--three">
        <?php foreach ($guideCards as $guide): ?>
            <article class="info-card">
                <p class="card-tag"><?= e($guide['tag']) ?></p>
                <h3><?= e($guide['title']) ?></h3>
                <p><?= e($guide['text']) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>
