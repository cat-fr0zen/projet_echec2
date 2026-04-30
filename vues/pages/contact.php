<?php
$email = (string) ($siteData['email'] ?? '');
$address = (string) ($siteData['address'] ?? '');
$phone = (string) ($siteData['phone'] ?? '');
$isEmailLinkable = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
?>

<section class="page-banner reveal reveal-2">
    <p class="eyebrow">Contact</p>
    <h1><?= e($pageData['title']) ?></h1>
    <p><?= e($pageData['intro']) ?></p>
</section>

<section class="section-block reveal reveal-3">
    <div class="section-head">
        <p class="eyebrow">Coordonnées du club</p>
        <h2>Adresse, email et repères officiels.</h2>
        <p>Cette page reste volontairement simple pour aller directement à l'essentiel.</p>
    </div>

    <div class="card-grid card-grid--three">
        <article class="info-card">
            <p class="card-tag">Email du club</p>
            <h3><?= e($email) ?></h3>
            <?php if ($isEmailLinkable): ?>
                <a class="button button-secondary contact-link" href="mailto:<?= e($email) ?>">Écrire au club</a>
            <?php else: ?>
                <p>Adresse officielle à compléter par l'association.</p>
            <?php endif; ?>
        </article>

        <article class="info-card">
            <p class="card-tag">Adresse</p>
            <h3><?= e($address) ?></h3>
            <p>Adresse postale et lieu de référence du club.</p>
        </article>

        <article class="info-card">
            <p class="card-tag">Téléphone</p>
            <h3><?= e($phone) ?></h3>
            <p>Coordonnée complémentaire publiée lorsqu'elle est validée.</p>
        </article>
    </div>
</section>

<section class="split-grid reveal reveal-4">
    <article class="panel">
        <div class="section-head section-head--compact">
            <p class="eyebrow">Publication</p>
            <h2>Référents du site et de l'association.</h2>
            <p>Les responsabilités éditoriales et techniques restent clairement séparées.</p>
        </div>

        <div class="stack-list">
            <div class="schedule-item">
                <p class="card-tag">Publication associative</p>
                <h3><?= e($siteData['credits']['association_publisher']) ?></h3>
                <p>Validation institutionnelle des informations publiques du site.</p>
            </div>
            <div class="schedule-item">
                <p class="card-tag">Conception du site</p>
                <h3><?= e($siteData['credits']['site_author']) ?></h3>
                <p>Structure du site, design, intégration et maintenance technique.</p>
            </div>
        </div>
    </article>

    <article class="panel panel-contrast">
        <div class="section-head section-head--compact">
            <p class="eyebrow">Contact utile</p>
            <h2>Un point d'entrée sobre et direct.</h2>
            <p>Pour le reste, les informations légales détaillées demeurent disponibles dans le footer.</p>
        </div>
    </article>
</section>
