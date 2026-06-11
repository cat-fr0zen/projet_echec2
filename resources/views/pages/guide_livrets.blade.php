<?php
/**
 * Vue: hub des livrets.
 */

$livretsCours = is_array($siteData['livrets_cours'] ?? null)
    ? array_values($siteData['livrets_cours'])
    : [];

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
    $configurationsLivrets[] = [
        'badge' => (string) ($livret['tag'] ?? strtoupper(str_replace('_', ' ', $rubriqueLivret))),
        'titre' => (string) ($livret['title'] ?? 'Livret'),
        'texte' => (string) ($livret['text'] ?? ''),
        'url' => url_route($rubriquesLivrets[$rubriqueLivret]),
    ];
}
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
        <h2>Choisir un niveau</h2>
        <p>Chaque carte ouvre la page dediee du niveau correspondant avec ses PDF de travail.</p>
    </div>

    <div class="card-grid card-grid--three course-level-grid">
        <?php foreach ($configurationsLivrets as $configurationLivret): ?>
            <a class="info-card info-card--link course-level-card" href="<?= e((string) $configurationLivret['url']) ?>">
                <p class="card-tag"><?= e((string) $configurationLivret['badge']) ?></p>
                <h3><?= e((string) $configurationLivret['titre']) ?></h3>
                <p><?= e((string) $configurationLivret['texte']) ?></p>
                <p class="card-meta">Ouvrir la page du niveau</p>
            </a>
        <?php endforeach; ?>
    </div>
</section>
