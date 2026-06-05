<?php
/**
 * Vue: Cours.
 *
 * La page reste volontairement tres simple :
 * - un bandeau de page
 * - trois petits blocs cliquables en haut
 *
 * Les autres zones de travail PDF restent disponibles cote code pour la suite,
 * mais ne sont plus affichees ici tant que le contenu final n'est pas pret.
 */

$blocsCours = [
    [
        'tag' => 'Parcours',
        'title' => 'Livrets',
        'text' => 'Acceder rapidement aux niveaux A a E.',
    ],
    [
        'tag' => 'Pedagogie',
        'title' => 'Cours',
        'text' => 'Retrouver les supports de seance du club.',
    ],
    [
        'tag' => 'Progression',
        'title' => 'Methodologie / strategie',
        'text' => 'Ranger les PDF de methode et de plan de jeu.',
    ],
];
?>

<section class="page-banner reveal reveal-2">
    <p class="eyebrow">Cours</p>
    <h1><?= e($donneesPage['titre']) ?></h1>
    <p><?= e($donneesPage['intro']) ?></p>
</section>

<section class="card-grid card-grid--three reveal reveal-3">
    <?php foreach ($blocsCours as $blocCours): ?>
        <article class="info-card">
            <p class="card-tag"><?= e((string) $blocCours['tag']) ?></p>
            <h2><?= e((string) $blocCours['title']) ?></h2>
            <p><?= e((string) $blocCours['text']) ?></p>
        </article>
    <?php endforeach; ?>
</section>
