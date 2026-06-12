<?php
/**
 * Vue: page dédiée d'un livret.
 */

$courseJetonCsrf = (string) ($siteData['jeton_csrf'] ?? csrf_token());
$coursePeutGererDocuments = (bool) ($siteData['peut_gerer_documents_cours'] ?? false);
$courseDocumentsParRubrique = is_array($siteData['documents_cours_par_rubrique'] ?? null)
    ? $siteData['documents_cours_par_rubrique']
    : [];
$livretsCours = is_array($siteData['livrets_cours'] ?? null)
    ? array_values($siteData['livrets_cours'])
    : [];
$rubriqueActive = (string) ($donneesPage['rubrique_document_cours'] ?? 'livret_a');
$courseDocumentsBibliotheque = is_array($courseDocumentsParRubrique['livrets'] ?? null)
    ? array_values($courseDocumentsParRubrique['livrets'])
    : [];

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
$coursePagesRubriques = [
    'livret_a' => 'cours-livret-a',
    'livret_b' => 'cours-livret-b',
    'livret_c' => 'cours-livret-c',
    'livret_d' => 'cours-livret-d',
    'livret_e' => 'cours-livret-e',
];
$courseObtenirDocumentsLivret = static function (string $rubriqueLivret) use ($courseDocumentsParRubrique, $courseDocumentsBibliotheque): array {
    $documentsDirects = is_array($courseDocumentsParRubrique[$rubriqueLivret] ?? null)
        ? array_values($courseDocumentsParRubrique[$rubriqueLivret])
        : [];

    if ($documentsDirects !== []) {
        return $documentsDirects;
    }

    $lettreLivret = strtoupper((string) preg_replace('/^livret_/', '', $rubriqueLivret));

    return array_values(array_filter(
        $courseDocumentsBibliotheque,
        static function (array $document) use ($lettreLivret): bool {
            $titre = (string) ($document['titre_document'] ?? '');
            $nomOriginal = (string) ($document['nom_fichier_original'] ?? '');
            $motif = '/\blivret\s+'.preg_quote($lettreLivret, '/').'\b/i';

            return preg_match($motif, $titre) === 1 || preg_match($motif, $nomOriginal) === 1;
        }
    ));
};

$configurationsLivrets = [];
$livretRubriques = ['livret_a', 'livret_b', 'livret_c', 'livret_d', 'livret_e'];

foreach ($livretRubriques as $index => $rubriqueLivret) {
    $livret = $livretsCours[$index] ?? [];
    $documentsLivret = $courseObtenirDocumentsLivret($rubriqueLivret);
    $configurationsLivrets[] = [
        'rubrique' => $rubriqueLivret,
        'ancre' => $courseAncresRubriques[$rubriqueLivret],
        'badge' => (string) ($livret['tag'] ?? $courseRubriqueLabels[$rubriqueLivret]),
        'titre' => (string) ($livret['title'] ?? $courseRubriqueLabels[$rubriqueLivret]),
        'texte' => (string) ($livret['text'] ?? ''),
        'url' => url_route($coursePagesRubriques[$rubriqueLivret]),
        'active' => $rubriqueActive === $rubriqueLivret,
        'documents_count' => count($documentsLivret),
    ];
}

$configurationsLivretsCompletes = $configurationsLivrets;
$configurationsLivrets = array_values(array_filter(
    $configurationsLivrets,
    static fn (array $configuration): bool => ((int) ($configuration['documents_count'] ?? 0)) > 0
));

$courseRubriqueConfig = current(array_filter(
    $configurationsLivretsCompletes,
    static fn (array $configuration): bool => (string) ($configuration['rubrique'] ?? '') === $rubriqueActive
));

if (! is_array($courseRubriqueConfig)) {
    $courseRubriqueConfig = $configurationsLivrets[0] ?? [
        'rubrique' => 'livret_a',
        'ancre' => 'cours-livret-a',
        'badge' => 'Livret A',
        'titre' => 'Livret A',
        'texte' => '',
    ];
}

$courseDocumentsParRubrique[$rubriqueActive] = $courseObtenirDocumentsLivret($rubriqueActive);
?>

<section class="page-banner reveal reveal-2">
    <p class="eyebrow">Cours</p>
    <h1><?= e($donneesPage['titre']) ?></h1>
    <p><?= e($donneesPage['intro']) ?></p>

    <div class="course-subpage-actions">
        <a class="button button-secondary" href="<?= e(url_route('cours-livrets')) ?>">Retour a la page Livrets</a>
    </div>
</section>

<section class="section-block course-section reveal reveal-3">
    <div class="section-head course-section-head">
        <p class="eyebrow">Navigation des niveaux</p>
        <h2>Choisir un autre livret</h2>
        <p>Seuls les livrets qui contiennent deja des PDF sont proposes ici.</p>
    </div>

    <div class="card-grid card-grid--three course-level-grid">
        <?php foreach ($configurationsLivrets as $configurationLivret): ?>
            <a
                class="info-card info-card--link course-level-card<?= ! empty($configurationLivret['active']) ? ' is-current' : '' ?>"
                href="<?= e((string) ($configurationLivret['url'] ?? url_route('guide'))) ?>"
                <?= ! empty($configurationLivret['active']) ? 'aria-current="page"' : '' ?>
            >
                <p class="card-tag"><?= e((string) ($configurationLivret['badge'] ?? 'Livret')) ?></p>
                <h3><?= e((string) ($configurationLivret['titre'] ?? 'Livret')) ?></h3>
                <p><?= e((string) ($configurationLivret['texte'] ?? '')) ?></p>
                <p class="card-meta">
                    <?= ! empty($configurationLivret['active']) ? 'Page actuelle' : 'Ouvrir ce niveau' ?>
                    · <?= e((string) ($configurationLivret['documents_count'] ?? 0)) ?> PDF
                </p>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="section-block course-section reveal reveal-4">
    <div class="section-head course-section-head">
        <p class="eyebrow">Documents du niveau</p>
        <h2><?= e((string) ($courseRubriqueConfig['titre'] ?? $donneesPage['titre'])) ?></h2>
        <p><?= e((string) ($courseRubriqueConfig['texte'] ?? $donneesPage['intro'])) ?></p>
    </div>

    <div class="course-rubrique-stack course-rubrique-stack--single">
        <?php require resource_path('views/pages/partials/cours-rubrique.blade.php'); ?>
    </div>
</section>
