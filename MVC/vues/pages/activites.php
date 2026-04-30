<?php $activities = $siteData['activities']; ?>

<section class="page-banner reveal reveal-2">
    <p class="eyebrow">Activités</p>
    <h1><?= e($pageData['title']) ?></h1>
    <p><?= e($pageData['intro']) ?></p>
</section>

<section class="section-block reveal reveal-3">
    <div class="section-head">
        <p class="eyebrow">Organisation des contenus</p>
        <h2>Des cartes prêtes pour les activités à venir.</h2>
        <p>
            La page garde ses cadres, ses rythmes et sa hiérarchie visuelle tout en
            attendant la validation des informations officielles à publier.
        </p>
    </div>

    <div class="card-grid card-grid--three">
        <?php foreach ($activities as $activity): ?>
            <article class="info-card">
                <p class="card-tag"><?= e($activity['tag']) ?></p>
                <h3><?= e($activity['title']) ?></h3>
                <p><?= e($activity['text']) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="split-grid reveal reveal-4">
    <article class="panel panel-contrast">
        <div class="section-head section-head--compact">
            <p class="eyebrow">Publication responsable</p>
            <h2>Rien n'est publié tant que ce n'est pas validé.</h2>
            <p>
                Cette page peut accueillir des activités réelles, des calendriers et des documents,
                mais uniquement après confirmation par les responsables de l'association.
            </p>
        </div>
    </article>

    <article class="panel">
        <div class="section-head section-head--compact">
            <p class="eyebrow">Documents et accès</p>
            <h2>Le design reste prêt pour les prochaines intégrations.</h2>
            <p>
                Bulletins, règlements, planning, inscriptions ou actualités pourront être ajoutés ici
                sans remettre en cause la structure des cartes ni la lisibilité du parcours.
            </p>
        </div>
    </article>
</section>
