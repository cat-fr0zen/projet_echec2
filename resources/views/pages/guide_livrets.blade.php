<?php
/**
 * Vue: hub des livrets.
 */

$livretsCours = is_array($siteData['livrets_cours'] ?? null)
    ? array_values($siteData['livrets_cours'])
    : [];
$courseJetonCsrf = (string) ($siteData['jeton_csrf'] ?? csrf_token());
$coursePeutGererDocuments = (bool) ($siteData['peut_gerer_documents_cours'] ?? false);
$courseDocumentsParRubrique = is_array($siteData['documents_cours_par_rubrique'] ?? null)
    ? $siteData['documents_cours_par_rubrique']
    : [];
$pageCourante = 'cours-livrets';

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
$courseDocumentsBibliotheque = is_array($courseDocumentsParRubrique['livrets'] ?? null)
    ? array_values($courseDocumentsParRubrique['livrets'])
    : [];
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

$rubriquesLivrets = [
    'livret_a' => 'cours-livret-a',
    'livret_b' => 'cours-livret-b',
    'livret_c' => 'cours-livret-c',
    'livret_d' => 'cours-livret-d',
    'livret_e' => 'cours-livret-e',
];

$configurationsLivrets = [];

foreach (array_values(array_keys($rubriquesLivrets)) as $index => $rubriqueLivret) {
    $livret = $livretsCours[$index] ?? [];
    $documentsLivret = $courseObtenirDocumentsLivret($rubriqueLivret);
    $configurationsLivrets[] = [
        'badge' => (string) ($livret['tag'] ?? strtoupper(str_replace('_', ' ', $rubriqueLivret))),
        'titre' => (string) ($livret['title'] ?? 'Livret'),
        'texte' => (string) ($livret['text'] ?? ''),
        'url' => url_route($rubriquesLivrets[$rubriqueLivret]),
        'documents_count' => count($documentsLivret),
    ];
}

$configurationsLivrets = array_values(array_filter(
    $configurationsLivrets,
    static fn (array $configuration): bool => ((int) ($configuration['documents_count'] ?? 0)) > 0
));

$courseTitreNiveaux = $configurationsLivrets !== [] ? 'Niveaux disponibles' : 'Bibliotheque des livrets';
$courseTexteNiveaux = $configurationsLivrets !== []
    ? 'Seuls les niveaux qui contiennent deja des PDF sont proposes ici.'
    : 'Les PDF sont ranges directement dans la bibliotheque ci-dessous.';

$courseRubriqueConfig = [
    'rubrique' => 'livrets',
    'ancre' => 'cours-bibliotheque-livrets',
    'badge' => 'Bibliotheque',
    'titre' => 'Bibliotheque des livrets',
    'texte' => 'Tous les PDF importes depuis les dossiers des livrets sont ranges ici par categorie.',
];
?>

<section class="page-banner reveal reveal-2">
    <p class="eyebrow">Parcours</p>
    <h1><?= e($donneesPage['titre']) ?></h1>
    <p><?= e($donneesPage['intro']) ?></p>

    <div class="course-subpage-actions">
        <a class="button button-secondary" href="<?= e(url_route('guide')) ?>">Retour a la page Cours</a>
    </div>
</section>

<section class="section-block course-section reveal reveal-3">
    <div class="section-head course-section-head">
        <p class="eyebrow">Livrets</p>
        <h2><?= e($courseTitreNiveaux) ?></h2>
        <p><?= e($courseTexteNiveaux) ?></p>
    </div>

    <?php if ($configurationsLivrets !== []): ?>
        <div class="card-grid card-grid--three course-level-grid">
            <?php foreach ($configurationsLivrets as $configurationLivret): ?>
                <a class="info-card info-card--link course-level-card" href="<?= e((string) $configurationLivret['url']) ?>">
                    <p class="card-tag"><?= e((string) $configurationLivret['badge']) ?></p>
                    <h3><?= e((string) $configurationLivret['titre']) ?></h3>
                    <p><?= e((string) $configurationLivret['texte']) ?></p>
                    <p class="card-meta"><?= e((string) ($configurationLivret['documents_count'] ?? 0)) ?> PDF disponibles</p>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="section-block course-section reveal reveal-4">
    <div class="section-head course-section-head">
        <p class="eyebrow">Bibliotheque</p>
        <h2>PDF ranges par dossiers</h2>
        <p>Les anciens et nouveaux livrets importes depuis les repertoires du projet restent accessibles dans un seul espace.</p>
    </div>

    <div class="course-rubrique-stack course-rubrique-stack--single">
        <?php require resource_path('views/pages/partials/cours-rubrique.blade.php'); ?>
    </div>
</section>
