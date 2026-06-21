<?php
/**
 * Vue d'entree pour Methodologie et Strategie.
 */

$courseDocumentsParRubrique = is_array($siteData['documents_cours_par_rubrique'] ?? null)
    ? $siteData['documents_cours_par_rubrique']
    : [];

$blocsProgression = [
    [
        'tag' => 'Methodologie',
        'title' => "Comment reflechir devant l'echiquier",
        'text' => 'Retrouver les routines de lecture, de verification et de prise de decision.',
        'href' => url_route('cours-methodologie'),
        'meta' => 'Ouvrir la page Methodologie',
        'rubrique' => 'methodologie',
    ],
    [
        'tag' => 'Strategie',
        'title' => 'Comprendre le plan avant le coup',
        'text' => 'Rassembler les PDF de plan de jeu, de structures et de priorites positionnelles.',
        'href' => url_route('cours-strategie'),
        'meta' => 'Ouvrir la page Strategie',
        'rubrique' => 'strategie',
    ],
];

$blocsProgression = array_values(array_filter(
    $blocsProgression,
    static function (array $blocProgression) use ($courseDocumentsParRubrique): bool {
        $rubrique = (string) ($blocProgression['rubrique'] ?? '');
        $documents = is_array($courseDocumentsParRubrique[$rubrique] ?? null)
            ? $courseDocumentsParRubrique[$rubrique]
            : [];

        return $documents !== [];
    }
));

$courseTitreProgression = $blocsProgression !== [] ? 'Rubriques disponibles' : 'Aucune rubrique remplie';
$courseTexteProgression = $blocsProgression !== []
    ? 'Selectionne uniquement une rubrique qui contient deja des PDF.'
    : "Aucun PDF n'est encore classe ici. Utilise une rubrique cible pour commencer le rangement.";
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
        <h2><?= e($courseTitreProgression) ?></h2>
        <p><?= e($courseTexteProgression) ?></p>
    </div>

    <?php if ($blocsProgression !== []): ?>
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
    <?php endif; ?>
</section>
