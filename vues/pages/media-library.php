<?php $mediaCards = $siteData['media_cards']; ?>

<section class="page-banner reveal reveal-2">
    <p class="eyebrow">Mediatheque</p>
    <h1><?= e($pageData['title']) ?></h1>
    <p><?= e($pageData['intro']) ?></p>
</section>

<section class="section-block reveal reveal-3">
    <div class="section-head">
        <p class="eyebrow">Photos et videos</p>
        <h2>Un cadre propre pour les futurs medias du club.</h2>
        <p>La publication de photos et videos devra respecter les droits, les autorisations et la validation associative.</p>
    </div>

    <div class="card-grid card-grid--three">
        <?php foreach ($mediaCards as $mediaCard): ?>
            <article class="info-card media-card">
                <p class="card-tag"><?= e($mediaCard['type']) ?></p>
                <h3><?= e($mediaCard['title']) ?></h3>
                <p><?= e($mediaCard['text']) ?></p>
                <p class="status-pill"><?= e($mediaCard['status']) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>
