<?php
/**
 * Vue: hub methodologie / strategie.
 */

$blocsProgression = [
    [
        'tag' => 'Methodologie',
        'title' => "Comment reflechir devant l'echiquier",
        'text' => 'Retrouver les routines de lecture, de verification et de prise de decision.',
        'href' => url_route('cours-methodologie'),
        'meta' => 'Ouvrir la page Methodologie',
    ],
    [
        'tag' => 'Strategie',
        'title' => 'Comprendre le plan avant le coup',
        'text' => 'Rassembler les PDF de plan de jeu, de structures et de priorites positionnelles.',
        'href' => url_route('cours-strategie'),
        'meta' => 'Ouvrir la page Strategie',
    ],
];
?>

<section class="page-banner reveal reveal-2">
    <p class="eyebrow">Progression</p>
    <h1><?= e($donneesPage['titre']) ?></h1>
    <p><?= e($donneesPage['intro']) ?></p>

    <div class="course-subpage-actions">
        <a class="button button-secondary" href="<?= e(url_route('guide')) ?>">Retour a la page Cours</a>
    </div>
</section>

<section class="section-block course-section reveal reveal-3">
    <div class="section-head course-section-head">
        <p class="eyebrow">Choix du travail</p>
        <h2>Deux pages dediees</h2>
        <p>Selectionne la page qui correspond au type de contenu que tu veux consulter ou ranger.</p>
    </div>

    <div class="card-grid card-grid--three course-progression-grid">
        <?php foreach ($blocsProgression as $blocProgression): ?>
            <a class="info-card info-card--link course-level-card" href="<?= e((string) $blocProgression['href']) ?>">
                <p class="card-tag"><?= e((string) $blocProgression['tag']) ?></p>
                <h3><?= e((string) $blocProgression['title']) ?></h3>
                <p><?= e((string) $blocProgression['text']) ?></p>
                <p class="card-meta"><?= e((string) $blocProgression['meta']) ?></p>
            </a>
        <?php endforeach; ?>
    </div>
</section>
