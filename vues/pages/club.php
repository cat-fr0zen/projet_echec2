<?php
$values = $siteData['values'];
$schedule = $siteData['schedule'];
?>

<section class="page-banner reveal reveal-2">
    <p class="eyebrow">Le club</p>
    <h1><?= e($pageData['title']) ?></h1>
    <p><?= e($pageData['intro']) ?></p>
</section>

<section class="section-block reveal reveal-3">
    <div class="section-head">
        <p class="eyebrow">Presentation institutionnelle</p>
        <h2>Des blocs prets pour les informations officielles du club.</h2>
        <p>
            Le contenu de cette page reste volontairement neutre tant que l association
            n a pas valide son nom complet, son objet, ses horaires et ses responsables.
        </p>
    </div>

    <div class="card-grid card-grid--three">
        <?php foreach ($values as $value): ?>
            <article class="info-card">
                <h3><?= e($value['title']) ?></h3>
                <p><?= e($value['text']) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="split-grid reveal reveal-4">
    <article class="panel">
        <div class="section-head section-head--compact">
            <p class="eyebrow">Publication encadree</p>
            <h2>Trois cadres pour les informations a confirmer.</h2>
            <p>Chaque bloc peut recevoir plus tard les donnees officielles sans changer la structure du site.</p>
        </div>

        <div class="stack-list">
            <?php foreach ($schedule as $item): ?>
                <div class="schedule-item">
                    <div class="schedule-topline">
                        <span class="schedule-day"><?= e($item['day']) ?></span>
                        <span class="schedule-slot"><?= e($item['slot']) ?></span>
                    </div>
                    <h3><?= e($item['title']) ?></h3>
                    <p><?= e($item['text']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </article>

    <article class="panel">
        <div class="section-head section-head--compact">
            <p class="eyebrow">Cadre de pratique</p>
            <h2>Ce que le site doit garantir des maintenant.</h2>
            <p>
                Meme sans contenu institutionnel finalise, le site peut deja poser un cadre clair
                sur la fiabilite des informations, le respect du jeu et la responsabilite editoriale.
            </p>
        </div>

        <ul class="bullet-list bullet-list--dark">
            <li>les informations publiques doivent etre verifiees avant diffusion</li>
            <li>les coordonnees officielles doivent provenir de l association</li>
            <li>la confidentialite et le consentement restent visibles sur tout le parcours</li>
            <li>les contenus du site restent proteges par la propriete intellectuelle</li>
        </ul>
    </article>
</section>
