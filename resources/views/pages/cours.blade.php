<?php
/**
 * Vue principale de la rubrique Cours.
 */

$courseDocumentsParRubrique = is_array($siteData['documents_cours_par_rubrique'] ?? null)
    ? $siteData['documents_cours_par_rubrique']
    : [];
$courseDocumentsBibliotheque = is_array($courseDocumentsParRubrique['livrets'] ?? null)
    ? $courseDocumentsParRubrique['livrets']
    : [];
$courseProgressionDisponibles = array_filter([
    'methodologie' => is_array($courseDocumentsParRubrique['methodologie'] ?? null) ? $courseDocumentsParRubrique['methodologie'] : [],
    'strategie' => is_array($courseDocumentsParRubrique['strategie'] ?? null) ? $courseDocumentsParRubrique['strategie'] : [],
], static fn (array $documents): bool => $documents !== []);
$courseLienProgression = count($courseProgressionDisponibles) === 1
    ? url_route(array_key_first($courseProgressionDisponibles) === 'methodologie' ? 'cours-methodologie' : 'cours-strategie')
    : url_route('cours-progression');
$courseMetaLivrets = count($courseDocumentsBibliotheque) > 0
    ? count($courseDocumentsBibliotheque).' PDF classes par dossiers'
    : 'Ouvrir les niveaux';
$courseMetaCours = is_array($courseDocumentsParRubrique['cours'] ?? null) && $courseDocumentsParRubrique['cours'] !== []
    ? count($courseDocumentsParRubrique['cours']).' PDF de seance'
    : 'Voir les supports';
$courseMetaProgression = count($courseProgressionDisponibles) > 0
    ? count($courseProgressionDisponibles).' rubrique(s) disponible(s)'
    : 'Voir les documents';

$blocsNavigation = [
    [
        'tag' => 'Parcours',
        'title' => 'Livrets',
        'text' => 'Consulter la bibliotheque des livrets et les niveaux qui ont deja des PDF.',
        'href' => url_route('cours-livrets'),
        'meta' => $courseMetaLivrets,
    ],
    [
        'tag' => 'Pedagogie',
        'title' => 'Cours',
        'text' => 'Retrouver les supports de seance du club.',
        'href' => url_route('cours-seances'),
        'meta' => $courseMetaCours,
    ],
    [
        'tag' => 'Progression',
        'title' => 'Methodologie / strategie',
        'text' => 'Retrouver seulement les rubriques qui contiennent deja des PDF utiles.',
        'href' => $courseLienProgression,
        'meta' => $courseMetaProgression,
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
