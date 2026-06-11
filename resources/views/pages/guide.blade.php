<?php
/**
 * Vue: hub principal des cours.
 */

$blocsNavigation = [
    [
        'tag' => 'Parcours',
        'title' => 'Livrets',
        'text' => 'Acceder rapidement aux niveaux A a E.',
        'href' => url_route('cours-livrets'),
        'meta' => 'Ouvrir les niveaux',
    ],
    [
        'tag' => 'Pedagogie',
        'title' => 'Cours',
        'text' => 'Retrouver les supports de seance du club.',
        'href' => url_route('cours-seances'),
        'meta' => 'Voir les supports',
    ],
    [
        'tag' => 'Progression',
        'title' => 'Methodologie / strategie',
        'text' => 'Ranger les PDF de methode et de plan de jeu.',
        'href' => url_route('cours-progression'),
        'meta' => 'Voir les documents',
    ],
];
?>

<section class="page-banner reveal reveal-2">
    <p class="eyebrow">Cours</p>
    <h1><?= e($donneesPage['titre']) ?></h1>
    <p><?= e($donneesPage['intro']) ?></p>
</section>

<section class="card-grid card-grid--three reveal reveal-3">
    <?php foreach ($blocsNavigation as $blocNavigation): ?>
        <a class="info-card info-card--link course-nav-card" href="<?= e((string) $blocNavigation['href']) ?>">
            <p class="card-tag"><?= e((string) $blocNavigation['tag']) ?></p>
            <h2><?= e((string) $blocNavigation['title']) ?></h2>
            <p><?= e((string) $blocNavigation['text']) ?></p>
            <p class="card-meta"><?= e((string) $blocNavigation['meta']) ?></p>
        </a>
    <?php endforeach; ?>
</section>
