<?php $cartesBoutique = $donneesSite['cartes_boutique']; ?>

<section class="page-banner reveal reveal-2">
    <p class="eyebrow">Merch</p>
    <h1><?= e($donneesPage['titre']) ?></h1>
    <p><?= e($donneesPage['intro']) ?></p>
</section>

<section class="section-block reveal reveal-3">
    <div class="section-head">
        <p class="eyebrow">Catalogue</p>
        <h2>Un espace de vente prêt à être publié.</h2>
        <p>Le merchandising pourra s'afficher ici une fois les produits, tarifs et conditions de vente confirmés.</p>
    </div>

    <div class="card-grid card-grid--three">
        <?php foreach ($cartesBoutique as $carteBoutique): ?>
            <article class="info-card merch-card">
                <p class="card-tag"><?= e($carteBoutique['type']) ?></p>
                <h3><?= e($carteBoutique['titre']) ?></h3>
                <p><?= e($carteBoutique['texte']) ?></p>
                <p class="status-pill"><?= e($carteBoutique['statut']) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>
