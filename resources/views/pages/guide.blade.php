<?php
/**
 * Vue: Cours.
 *
 * La route historique reste `guide`, mais l'interface membre expose
 * maintenant une page Cours compacte avec :
 * - trois blocs d'entree cliquables
 * - les niveaux de livrets A a E
 * - des zones PDF gerees par prof/admin
 */

$livretsCours = is_array($donneesSite['livrets_cours'] ?? null) ? $donneesSite['livrets_cours'] : [];
$cartesCoursStrategie = is_array($donneesSite['cartes_cours_strategie'] ?? null) ? $donneesSite['cartes_cours_strategie'] : [];
$documentsCoursParRubrique = is_array($donneesSite['documents_cours_par_rubrique'] ?? null) ? $donneesSite['documents_cours_par_rubrique'] : [];
$peutGererDocumentsCours = (bool) ($donneesSite['peut_gerer_documents_cours'] ?? false);
$jetonCsrf = (string) ($donneesSite['jeton_csrf'] ?? '');

$blocsCours = [
    [
        'tag' => 'Parcours',
        'title' => 'Livrets',
        'text' => 'Acceder rapidement aux niveaux A a E.',
        'anchor' => '#cours-livrets',
    ],
    [
        'tag' => 'Pedagogie',
        'title' => 'Cours',
        'text' => 'Retrouver les supports de seance du club.',
        'anchor' => '#cours-cours',
    ],
    [
        'tag' => 'Progression',
        'title' => 'Methodologie / strategie',
        'text' => 'Ranger les PDF de methode et de plan de jeu.',
        'anchor' => '#cours-methodologie',
    ],
];

$rubriquesDocuments = [
    'cours' => [
        'tag' => 'Ressource',
        'title' => 'Cours',
        'text' => 'Supports de seances et documents a relire entre deux cours.',
        'anchor' => 'cours-cours',
    ],
    'methodologie' => [
        'tag' => 'Ressource',
        'title' => 'Methodologie',
        'text' => 'Methode de reflexion, check-list et habitudes de calcul.',
        'anchor' => 'cours-methodologie',
    ],
    'strategie' => [
        'tag' => 'Ressource',
        'title' => 'Strategie',
        'text' => 'Plans, structures de pions et lecture de position.',
        'anchor' => 'cours-strategie',
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
        <a class="info-card info-card--link" href="<?= e((string) $blocCours['anchor']) ?>">
            <p class="card-tag"><?= e((string) $blocCours['tag']) ?></p>
            <h2><?= e((string) $blocCours['title']) ?></h2>
            <p><?= e((string) $blocCours['text']) ?></p>
        </a>
    <?php endforeach; ?>
</section>

<section id="cours-livrets" class="section-block reveal reveal-4">
    <div class="section-head">
        <p class="eyebrow">Livrets</p>
        <h2>Niveaux de livret.</h2>
        <p>Choisis un niveau pour ranger ou retrouver les PDF du bon groupe.</p>
    </div>

    <div class="card-grid card-grid--three">
        <?php foreach ($livretsCours as $livret): ?>
            <?php
            $codeLivret = (string) ($livret['code'] ?? '');
            $ancreLivret = (string) ($livret['anchor'] ?? '#cours-livrets');
            ?>
            <a class="info-card info-card--link" href="<?= e($ancreLivret) ?>">
                <p class="card-tag"><?= e((string) ($livret['tag'] ?? 'Livret')) ?></p>
                <h3><?= e((string) ($livret['title'] ?? 'Livret')) ?></h3>
                <p><?= e((string) ($livret['text'] ?? '')) ?></p>
                <?php if ($peutGererDocumentsCours): ?>
                    <p class="card-meta">
                        <?= count($documentsCoursParRubrique[$codeLivret] ?? []) ?> document(s)
                    </p>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="section-block reveal reveal-5">
    <div class="section-head">
        <p class="eyebrow">Ressources</p>
        <h2>Cours, methodologie et strategie.</h2>
        <p>Ces cadres servent a ranger les autres supports PDF du club.</p>
    </div>

    <div class="card-grid card-grid--three">
        <?php foreach ($rubriquesDocuments as $codeRubrique => $rubrique): ?>
            <a class="info-card info-card--link" href="#<?= e((string) $rubrique['anchor']) ?>">
                <p class="card-tag"><?= e((string) $rubrique['tag']) ?></p>
                <h3><?= e((string) $rubrique['title']) ?></h3>
                <p><?= e((string) $rubrique['text']) ?></p>
                <?php if ($peutGererDocumentsCours): ?>
                    <p class="card-meta">
                        <?= count($documentsCoursParRubrique[$codeRubrique] ?? []) ?> document(s)
                    </p>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<?php if (! $peutGererDocumentsCours): ?>
    <section class="panel reveal reveal-6">
        <div class="section-head section-head--compact">
            <p class="eyebrow">Acces</p>
            <h2>Gestion reservee.</h2>
            <p>Les PDF de cours sont geres ici par les professeurs et l'administrateur.</p>
        </div>
    </section>
<?php endif; ?>

<?php foreach ($livretsCours as $index => $livret): ?>
    <?php
    $codeLivret = (string) ($livret['code'] ?? '');
    $documentsLivret = is_array($documentsCoursParRubrique[$codeLivret] ?? null) ? $documentsCoursParRubrique[$codeLivret] : [];
    $ancreLivret = ltrim((string) ($livret['anchor'] ?? '#cours-livrets'), '#');
    ?>
    <section id="<?= e($ancreLivret) ?>" class="section-block reveal reveal-<?= 6 + $index ?>">
        <div class="section-head">
            <p class="eyebrow"><?= e((string) ($livret['tag'] ?? 'Livret')) ?></p>
            <h2><?= e((string) ($livret['title'] ?? 'Livret')) ?></h2>
            <p><?= e((string) ($livret['text'] ?? '')) ?></p>
        </div>

        <div class="course-documents">
            <?php if ($documentsLivret === []): ?>
                <article class="info-card">
                    <p class="card-tag">PDF</p>
                    <h3>Aucun document pour le moment</h3>
                    <p>Cette rubrique est prete a accueillir les PDF du niveau.</p>
                </article>
            <?php else: ?>
                <?php foreach ($documentsLivret as $document): ?>
                    <article class="info-card course-document-card">
                        <p class="card-tag">PDF</p>
                        <h3><?= e((string) ($document['titre_document'] ?? 'Document')) ?></h3>
                        <p><?= e((string) ($document['description_document'] ?? '')) ?></p>
                        <p class="card-meta"><?= e((string) ($document['nom_fichier_original'] ?? 'document.pdf')) ?></p>
                        <?php if ($peutGererDocumentsCours): ?>
                            <div class="button-row">
                                <a class="button button-secondary" href="/<?= e(ltrim((string) ($document['chemin_fichier'] ?? ''), '/')) ?>">
                                    Telecharger
                                </a>
                                <form method="post" action="/guide" class="inline-form">
                                    <input type="hidden" name="_token" value="<?= e($jetonCsrf) ?>">
                                    <input type="hidden" name="jeton_csrf" value="<?= e($jetonCsrf) ?>">
                                    <input type="hidden" name="action" value="supprimer_document_cours">
                                    <input type="hidden" name="page_redirection" value="guide">
                                    <input type="hidden" name="identifiant_document_cours" value="<?= e((string) ($document['identifiant_document'] ?? '')) ?>">
                                    <button type="submit" class="button button-ghost">Supprimer</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if ($peutGererDocumentsCours): ?>
            <form method="post" action="/guide" enctype="multipart/form-data" class="course-upload-form">
                <input type="hidden" name="_token" value="<?= e($jetonCsrf) ?>">
                <input type="hidden" name="jeton_csrf" value="<?= e($jetonCsrf) ?>">
                <input type="hidden" name="action" value="ajouter_document_cours">
                <input type="hidden" name="page_redirection" value="guide">
                <input type="hidden" name="rubrique_document_cours" value="<?= e($codeLivret) ?>">

                <div class="course-upload-grid">
                    <label>
                        <span>Titre du PDF</span>
                        <input type="text" name="titre_document_cours" maxlength="160" required>
                    </label>

                    <label>
                        <span>Fichier PDF</span>
                        <input type="file" name="fichier_document_cours" accept="application/pdf,.pdf" required>
                    </label>
                </div>

                <label>
                    <span>Description courte</span>
                    <textarea name="description_document_cours" rows="3" maxlength="2000"></textarea>
                </label>

                <div class="button-row">
                    <button type="submit" class="button button-primary">Ajouter le PDF</button>
                </div>
            </form>
        <?php endif; ?>
    </section>
<?php endforeach; ?>

<?php foreach ($rubriquesDocuments as $codeRubrique => $rubrique): ?>
    <?php
    $documentsRubrique = is_array($documentsCoursParRubrique[$codeRubrique] ?? null) ? $documentsCoursParRubrique[$codeRubrique] : [];
    ?>
    <section id="<?= e((string) $rubrique['anchor']) ?>" class="section-block reveal reveal-8">
        <div class="section-head">
            <p class="eyebrow"><?= e((string) $rubrique['tag']) ?></p>
            <h2><?= e((string) $rubrique['title']) ?></h2>
            <p><?= e((string) $rubrique['text']) ?></p>
        </div>

        <div class="course-documents">
            <?php if ($documentsRubrique === []): ?>
                <article class="info-card">
                    <p class="card-tag">PDF</p>
                    <h3>Aucun document pour le moment</h3>
                    <p>Cette rubrique est prete a accueillir les supports PDF correspondants.</p>
                </article>
            <?php else: ?>
                <?php foreach ($documentsRubrique as $document): ?>
                    <article class="info-card course-document-card">
                        <p class="card-tag">PDF</p>
                        <h3><?= e((string) ($document['titre_document'] ?? 'Document')) ?></h3>
                        <p><?= e((string) ($document['description_document'] ?? '')) ?></p>
                        <p class="card-meta"><?= e((string) ($document['nom_fichier_original'] ?? 'document.pdf')) ?></p>
                        <?php if ($peutGererDocumentsCours): ?>
                            <div class="button-row">
                                <a class="button button-secondary" href="/<?= e(ltrim((string) ($document['chemin_fichier'] ?? ''), '/')) ?>">
                                    Telecharger
                                </a>
                                <form method="post" action="/guide" class="inline-form">
                                    <input type="hidden" name="_token" value="<?= e($jetonCsrf) ?>">
                                    <input type="hidden" name="jeton_csrf" value="<?= e($jetonCsrf) ?>">
                                    <input type="hidden" name="action" value="supprimer_document_cours">
                                    <input type="hidden" name="page_redirection" value="guide">
                                    <input type="hidden" name="identifiant_document_cours" value="<?= e((string) ($document['identifiant_document'] ?? '')) ?>">
                                    <button type="submit" class="button button-ghost">Supprimer</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if ($peutGererDocumentsCours): ?>
            <form method="post" action="/guide" enctype="multipart/form-data" class="course-upload-form">
                <input type="hidden" name="_token" value="<?= e($jetonCsrf) ?>">
                <input type="hidden" name="jeton_csrf" value="<?= e($jetonCsrf) ?>">
                <input type="hidden" name="action" value="ajouter_document_cours">
                <input type="hidden" name="page_redirection" value="guide">
                <input type="hidden" name="rubrique_document_cours" value="<?= e($codeRubrique) ?>">

                <div class="course-upload-grid">
                    <label>
                        <span>Titre du PDF</span>
                        <input type="text" name="titre_document_cours" maxlength="160" required>
                    </label>

                    <label>
                        <span>Fichier PDF</span>
                        <input type="file" name="fichier_document_cours" accept="application/pdf,.pdf" required>
                    </label>
                </div>

                <label>
                    <span>Description courte</span>
                    <textarea name="description_document_cours" rows="3" maxlength="2000"></textarea>
                </label>

                <div class="button-row">
                    <button type="submit" class="button button-primary">Ajouter le PDF</button>
                </div>
            </form>
        <?php endif; ?>
    </section>
<?php endforeach; ?>
