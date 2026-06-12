<?php
/**
 * Vue: page dediee a une rubrique de cours simple.
 */

$courseJetonCsrf = (string) ($siteData['jeton_csrf'] ?? csrf_token());
$coursePeutGererDocuments = (bool) ($siteData['peut_gerer_documents_cours'] ?? false);
$courseDocumentsParRubrique = is_array($siteData['documents_cours_par_rubrique'] ?? null)
    ? $siteData['documents_cours_par_rubrique']
    : [];
$rubriqueActive = (string) ($donneesPage['rubrique_document_cours'] ?? 'cours');
$pageRetour = (string) ($donneesPage['retour_page_cours'] ?? 'guide');

$courseRubriqueLabels = [
    'livret_a' => 'Livret A',
    'livret_b' => 'Livret B',
    'livret_c' => 'Livret C',
    'livret_d' => 'Livret D',
    'livret_e' => 'Livret E',
    'livrets' => 'Bibliotheque des livrets',
    'cours' => 'Cours',
    'methodologie' => 'Methodologie',
    'strategie' => 'Strategie',
];
$courseAncresRubriques = [
    'livret_a' => 'cours-livret-a',
    'livret_b' => 'cours-livret-b',
    'livret_c' => 'cours-livret-c',
    'livret_d' => 'cours-livret-d',
    'livret_e' => 'cours-livret-e',
    'livrets' => 'cours-bibliotheque-livrets',
    'cours' => 'cours-cours',
    'methodologie' => 'cours-methodologie',
    'strategie' => 'cours-strategie',
];

$courseConfigRubriques = [
    'cours' => [
        'rubrique' => 'cours',
        'ancre' => 'cours-cours',
        'badge' => 'Cours',
        'titre' => 'Le fil des seances',
        'texte' => 'Retrouver les supports de seance du club.',
    ],
    'methodologie' => [
        'rubrique' => 'methodologie',
        'ancre' => 'cours-methodologie',
        'badge' => 'Methodologie',
        'titre' => "Comment reflechir devant l'echiquier",
        'texte' => 'Apprendre une routine simple : observer, comparer, calculer, verifier et choisir.',
    ],
    'strategie' => [
        'rubrique' => 'strategie',
        'ancre' => 'cours-strategie',
        'badge' => 'Strategie',
        'titre' => 'Comprendre le plan avant le coup',
        'texte' => 'Reperer les faiblesses, les bonnes cases, les colonnes ouvertes et les priorites de la position.',
    ],
];

$courseRubriqueConfig = $courseConfigRubriques[$rubriqueActive] ?? $courseConfigRubriques['cours'];
?>

<section class="page-banner reveal reveal-2">
    <p class="eyebrow">Cours</p>
    <h1><?= e($donneesPage['titre']) ?></h1>
    <p><?= e($donneesPage['intro']) ?></p>

    <div class="course-subpage-actions">
        <a class="button button-secondary" href="<?= e(url_route($pageRetour)) ?>">Retour</a>
    </div>
</section>

<section class="section-block course-section reveal reveal-3">
    <div class="section-head course-section-head">
        <p class="eyebrow">Documents</p>
        <h2><?= e((string) ($courseRubriqueConfig['titre'] ?? $donneesPage['titre'])) ?></h2>
        <p><?= e((string) ($courseRubriqueConfig['texte'] ?? $donneesPage['intro'])) ?></p>
    </div>

    <div class="course-rubrique-stack course-rubrique-stack--single">
        <?php require resource_path('views/pages/partials/cours-rubrique.blade.php'); ?>
    </div>
</section>
