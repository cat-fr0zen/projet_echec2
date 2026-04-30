<?php $cartesMediatheque = $donneesSite['cartes_mediatheque']; ?>

<section class="page-banner reveal reveal-2">
    <p class="eyebrow">Médiathèque</p>
    <h1><?= e($donneesPage['titre']) ?></h1>
    <p><?= e($donneesPage['intro']) ?></p>
</section>

<section class="section-block reveal reveal-3">
    <div class="section-head">
        <p class="eyebrow">Photos et vidéos</p>
        <h2>Un cadre propre pour les futurs médias du club.</h2>
        <p>La publication de photos et vidéos devra respecter les droits, les autorisations et la validation associative.</p>
    </div>

    <div class="card-grid card-grid--three">
        <?php foreach ($cartesMediatheque as $carteMedia): ?>
            <article class="info-card media-card">
                <p class="card-tag"><?= e($carteMedia['type']) ?></p>
                <h3><?= e($carteMedia['titre']) ?></h3>
                <p><?= e($carteMedia['texte']) ?></p>
                <p class="status-pill"><?= e($carteMedia['statut']) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>
