<?php
/**
 * Vue: Evenements.
 */
$events = is_array($siteData['evenements_speciaux'] ?? null) ? $siteData['evenements_speciaux'] : [];
?>

<section class="page-banner reveal reveal-2">
    <p class="eyebrow">Evenements</p>
    <h1><?= e($pageData['title']) ?></h1>
    <p><?= e($pageData['intro']) ?></p>
</section>

<section class="section-block reveal reveal-3">
    <div class="section-head">
        <p class="eyebrow">Calendrier special</p>
        <h2>Les prochains rendez-vous officiels du club.</h2>
        <p>Les evenements ajoutes par l'administration apparaissent ici et peuvent etre relayes par newsletter.</p>
    </div>

    <?php if ($events === []): ?>
        <div class="empty-state">
            <p class="card-tag">Aucun evenement</p>
            <h3>Aucun evenement special n'est annonce pour le moment.</h3>
            <p>Les prochaines animations, tournois, stages ou rendez-vous speciaux apparaitront ici.</p>
        </div>
    <?php else: ?>
        <div class="card-grid card-grid--three">
            <?php foreach ($events as $event): ?>
                <article class="info-card">
                    <p class="card-tag"><?= e((string) ($event['date'] ?? '')) ?></p>
                    <h3><?= e((string) ($event['titre'] ?? 'Evenement')) ?></h3>
                    <?php if (($event['lieu'] ?? '') !== ''): ?>
                        <p class="card-subtitle"><?= e((string) $event['lieu']) ?></p>
                    <?php endif; ?>
                    <p><?= e((string) ($event['description'] ?? '')) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
