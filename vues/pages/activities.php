<?php $activities = $siteData['activities']; ?>

<section class="page-banner reveal reveal-2">
    <p class="eyebrow">Activites</p>
    <h1><?= e($pageData['title']) ?></h1>
    <p><?= e($pageData['intro']) ?></p>
</section>

<section class="section-block reveal reveal-3">
    <div class="section-head">
        <p class="eyebrow">Organisation des contenus</p>
        <h2>Des cartes pretes pour les activites a venir.</h2>
        <p>
            La page garde ses cadres, ses rythmes et sa hierarchie visuelle tout en
            attendant la validation des informations officielles a publier.
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
            <h2>Rien n est publie tant que ce n est pas valide.</h2>
            <p>
                Cette page peut accueillir des activites reelles, des calendriers et des documents,
                mais uniquement apres confirmation par les responsables de l association.
            </p>
        </div>
    </article>

    <article class="panel">
        <div class="section-head section-head--compact">
            <p class="eyebrow">Documents et acces</p>
            <h2>Le design reste pret pour les prochaines integrations.</h2>
            <p>
                Bulletins, reglements, planning, inscriptions ou actualites pourront etre ajoutes ici
                sans remettre en cause la structure des cartes ni la lisibilite du parcours.
            </p>
        </div>
    </article>
</section>
