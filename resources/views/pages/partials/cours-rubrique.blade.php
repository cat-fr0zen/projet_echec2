<?php
/**
 * Partiel: rendu d'une rubrique de cours.
 *
 * Variables attendues :
 * - $courseRubriqueConfig
 * - $courseDocumentsParRubrique
 * - $coursePeutGererDocuments
 * - $courseJetonCsrf
 * - $courseRubriqueLabels
 * - $courseAncresRubriques
 * - $pageCourante
 */

$courseRubrique = (string) ($courseRubriqueConfig['rubrique'] ?? '');
$courseAncre = (string) ($courseRubriqueConfig['ancre'] ?? ($courseAncresRubriques[$courseRubrique] ?? ''));
$courseBadge = (string) ($courseRubriqueConfig['badge'] ?? ($courseRubriqueLabels[$courseRubrique] ?? 'Documents'));
$courseTitre = (string) ($courseRubriqueConfig['titre'] ?? $courseBadge);
$courseTexte = (string) ($courseRubriqueConfig['texte'] ?? '');
$courseDocuments = $courseDocumentsParRubrique[$courseRubrique] ?? [];
$courseDocuments = is_array($courseDocuments) ? $courseDocuments : [];
$courseUrlAction = url_route((string) $pageCourante);
$courseMessageVide = $coursePeutGererDocuments
    ? 'Aucun PDF dans cette rubrique pour le moment.'
    : "Les documents de cette rubrique seront geres ici par les professeurs et l'administration.";

$courseFormaterTaille = static function (int $tailleOctets): string {
    if ($tailleOctets <= 0) {
        return '';
    }

    if ($tailleOctets >= 1024 * 1024) {
        return number_format($tailleOctets / (1024 * 1024), 1, ',', ' ').' Mo';
    }

    return number_format($tailleOctets / 1024, 0, ',', ' ').' Ko';
};

$courseFormaterDate = static function (string $date): string {
    if (trim($date) === '') {
        return '';
    }

    $horodatage = strtotime($date);

    if ($horodatage === false) {
        return '';
    }

    return date('d/m/Y H:i', $horodatage);
};

$courseDocumentsGroupes = [];
$courseRechercheActive = in_array($courseRubrique, ['cours', 'methodologie', 'strategie'], true);

foreach ($courseDocuments as $courseDocument) {
    $courseGroupeDocument = trim((string) ($courseDocument['groupe_document'] ?? ''));
    $courseSousGroupeDocument = trim((string) ($courseDocument['sous_groupe_document'] ?? ''));
    $courseCleGroupe = $courseGroupeDocument !== '' ? $courseGroupeDocument : '__racine__';
    $courseCleSousGroupe = $courseSousGroupeDocument !== '' ? $courseSousGroupeDocument : '__racine__';

    if (! isset($courseDocumentsGroupes[$courseCleGroupe])) {
        $courseDocumentsGroupes[$courseCleGroupe] = [
            'label' => $courseGroupeDocument,
            'sous_groupes' => [],
        ];
    }

    if (! isset($courseDocumentsGroupes[$courseCleGroupe]['sous_groupes'][$courseCleSousGroupe])) {
        $courseDocumentsGroupes[$courseCleGroupe]['sous_groupes'][$courseCleSousGroupe] = [
            'label' => $courseSousGroupeDocument,
            'documents' => [],
        ];
    }

    $courseDocumentsGroupes[$courseCleGroupe]['sous_groupes'][$courseCleSousGroupe]['documents'][] = $courseDocument;
}
?>

<article id="<?= e($courseAncre) ?>" class="course-rubrique">
    <div class="course-rubrique-head">
        <div class="course-rubrique-copy">
            <p class="card-tag"><?= e($courseBadge) ?></p>
            <h3><?= e($courseTitre) ?></h3>
            <?php if ($courseTexte !== ''): ?>
                <p><?= e($courseTexte) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($courseRechercheActive): ?>
        <div
            class="course-search"
            data-course-search
            data-course-search-empty-message="Aucun document ne correspond a cette recherche."
        >
            <label class="course-search-label" for="<?= e($courseRubrique) ?>-search">
                Rechercher dans <?= e(mb_strtolower($courseTitre, 'UTF-8')) ?>
            </label>
            <div class="course-search-field">
                <input
                    id="<?= e($courseRubrique) ?>-search"
                    class="course-search-input"
                    type="search"
                    name="<?= e($courseRubrique) ?>_search"
                    placeholder="Titre, dossier, description, PDF..."
                    autocomplete="off"
                    spellcheck="false"
                    data-course-search-input
                    aria-describedby="<?= e($courseRubrique) ?>-search-status"
                >
                <button type="button" class="course-search-reset" data-course-search-reset hidden>Effacer</button>
            </div>
            <p id="<?= e($courseRubrique) ?>-search-status" class="course-search-status" data-course-search-status aria-live="polite"></p>
        </div>
    <?php endif; ?>

    <div class="course-document-list">
        <?php if ($courseDocuments === []): ?>
            <div class="empty-state course-empty-state">
                <p class="card-tag"><?= e($courseBadge) ?></p>
                <h3>Aucun document disponible.</h3>
                <p><?= e($courseMessageVide) ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($courseDocumentsGroupes as $courseGroupeDocuments): ?>
                <?php
                $courseLabelGroupe = (string) ($courseGroupeDocuments['label'] ?? '');
                $courseSousGroupes = is_array($courseGroupeDocuments['sous_groupes'] ?? null)
                    ? $courseGroupeDocuments['sous_groupes']
                    : [];
                ?>

                <section
                    class="course-document-group<?= $courseLabelGroupe === '' ? ' course-document-group--root' : '' ?>"
                    data-course-search-group
                    data-course-search-text="<?= e(mb_strtolower($courseLabelGroupe, 'UTF-8')) ?>"
                >
                    <?php if ($courseLabelGroupe !== ''): ?>
                        <div class="course-document-group-head">
                            <p class="card-tag">Dossier</p>
                            <h4><?= e($courseLabelGroupe) ?></h4>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($courseSousGroupes as $courseSousGroupeDocuments): ?>
                        <?php
                        $courseLabelSousGroupe = (string) ($courseSousGroupeDocuments['label'] ?? '');
                        $courseListeDocuments = is_array($courseSousGroupeDocuments['documents'] ?? null)
                            ? $courseSousGroupeDocuments['documents']
                            : [];
                        ?>

                        <div
                            class="course-document-subgroup<?= $courseLabelSousGroupe === '' ? ' course-document-subgroup--root' : '' ?>"
                            data-course-search-subgroup
                            data-course-search-text="<?= e(mb_strtolower($courseLabelSousGroupe, 'UTF-8')) ?>"
                        >
                            <?php if ($courseLabelSousGroupe !== ''): ?>
                                <p class="course-document-subgroup-title"><?= e($courseLabelSousGroupe) ?></p>
                            <?php endif; ?>

                            <div class="course-document-subgroup-list">
                                <?php foreach ($courseListeDocuments as $courseDocument): ?>
                                    <?php
                                    $courseTitreDocument = (string) ($courseDocument['titre_document'] ?? 'Document PDF');
                                    $courseDescriptionDocument = trim((string) ($courseDocument['description_document'] ?? ''));
                                    $courseLienTelechargement = route('fichiers.cours.show', [
                                        'nomFichier' => (string) ($courseDocument['nom_fichier_stocke'] ?? ''),
                                    ]);
                                    $courseMetaDocument = ['PDF'];
                                    $courseTailleDocument = $courseFormaterTaille((int) ($courseDocument['taille_octets'] ?? 0));
                                    $courseDateCreation = $courseFormaterDate((string) ($courseDocument['cree_le'] ?? ''));
                                    $courseDateMiseAJour = $courseFormaterDate((string) ($courseDocument['mis_a_jour_le'] ?? ''));
                                    $courseRubriqueDocument = (string) ($courseDocument['code_rubrique'] ?? $courseRubrique);

                                    if ($courseTailleDocument !== '') {
                                        $courseMetaDocument[] = $courseTailleDocument;
                                    }

                                    if ($courseDateCreation !== '') {
                                        $courseMetaDocument[] = 'Ajoute le '.$courseDateCreation;
                                    }

                                    if ($courseDateMiseAJour !== '') {
                                        $courseMetaDocument[] = 'Mis a jour le '.$courseDateMiseAJour;
                                    }

                                    $courseSearchChunks = array_filter([
                                        $courseTitreDocument,
                                        $courseDescriptionDocument,
                                        $courseLabelGroupe,
                                        $courseLabelSousGroupe,
                                        implode(' ', $courseMetaDocument),
                                    ], static fn ($value): bool => trim((string) $value) !== '');
                                    $courseSearchText = mb_strtolower(implode(' ', $courseSearchChunks), 'UTF-8');
                                    ?>

                                    <article
                                        class="course-document-row"
                                        data-course-search-item
                                        data-course-search-text="<?= e($courseSearchText) ?>"
                                    >
                                        <div class="course-document-row-main">
                                            <div class="course-document-main">
                                                <?php if ($coursePeutGererDocuments): ?>
                                                    <a class="course-document-download" href="<?= e($courseLienTelechargement) ?>">
                                                        <?= e($courseTitreDocument) ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="course-document-title"><?= e($courseTitreDocument) ?></span>
                                                <?php endif; ?>

                                                <?php if ($courseDescriptionDocument !== ''): ?>
                                                    <p class="course-document-description"><?= e($courseDescriptionDocument) ?></p>
                                                <?php endif; ?>

                                                <p class="card-subtitle course-document-meta"><?= e(implode(' | ', $courseMetaDocument)) ?></p>
                                            </div>

                                            <?php if ($coursePeutGererDocuments): ?>
                                                <div class="course-document-actions">
                                                    <details class="course-document-edit">
                                                        <summary
                                                            class="course-action-button"
                                                            aria-label="<?= e('Modifier le document '.$courseTitreDocument) ?>"
                                                        >
                                                            <span aria-hidden="true">&#128296;</span>
                                                            <span class="sr-only">Modifier</span>
                                                        </summary>

                                                        <div class="course-document-edit-panel">
                                                            <form
                                                                method="post"
                                                                action="<?= e($courseUrlAction) ?>"
                                                                class="course-upload-form course-upload-form--inline"
                                                                enctype="multipart/form-data"
                                                            >
                                                                <input type="hidden" name="action" value="modifier_document_cours">
                                                                <input type="hidden" name="_token" value="<?= e($courseJetonCsrf) ?>">
                                                                <input type="hidden" name="jeton_csrf" value="<?= e($courseJetonCsrf) ?>">
                                                                <input type="hidden" name="page_redirection" value="<?= e((string) $pageCourante) ?>">
                                                                <input
                                                                    type="hidden"
                                                                    name="identifiant_document_cours"
                                                                    value="<?= e((string) ($courseDocument['identifiant_document'] ?? '')) ?>"
                                                                >

                                                                <div class="course-upload-grid">
                                                                    <label>
                                                                        Rubrique
                                                                        <select name="rubrique_document_cours">
                                                                            <?php foreach ($courseRubriqueLabels as $courseCodeRubrique => $courseLibelleRubrique): ?>
                                                                                <option
                                                                                    value="<?= e($courseCodeRubrique) ?>"
                                                                                    <?= $courseCodeRubrique === $courseRubriqueDocument ? 'selected' : '' ?>
                                                                                >
                                                                                    <?= e($courseLibelleRubrique) ?>
                                                                                </option>
                                                                            <?php endforeach; ?>
                                                                        </select>
                                                                    </label>

                                                                    <label>
                                                                        Remplacer le PDF
                                                                        <input
                                                                            type="file"
                                                                            name="fichier_document_cours_remplacement"
                                                                            accept="application/pdf,.pdf"
                                                                        >
                                                                    </label>
                                                                </div>

                                                                <label>
                                                                    Titre du document
                                                                    <input
                                                                        type="text"
                                                                        name="titre_document_cours"
                                                                        maxlength="160"
                                                                        value="<?= e($courseTitreDocument) ?>"
                                                                        required
                                                                    >
                                                                </label>

                                                                <label>
                                                                    Description
                                                                    <textarea
                                                                        name="description_document_cours"
                                                                        rows="4"
                                                                        maxlength="2000"
                                                                    ><?= e($courseDescriptionDocument) ?></textarea>
                                                                </label>

                                                                <button type="submit" class="button button-primary">Enregistrer</button>
                                                            </form>
                                                        </div>
                                                    </details>

                                                    <form
                                                        method="post"
                                                        action="<?= e($courseUrlAction) ?>"
                                                        class="inline-form"
                                                        data-confirm-delete
                                                        data-confirm-message="Supprimer definitivement ce document PDF ?"
                                                    >
                                                        <input type="hidden" name="action" value="supprimer_document_cours">
                                                        <input type="hidden" name="_token" value="<?= e($courseJetonCsrf) ?>">
                                                        <input type="hidden" name="jeton_csrf" value="<?= e($courseJetonCsrf) ?>">
                                                        <input type="hidden" name="page_redirection" value="<?= e((string) $pageCourante) ?>">
                                                        <input
                                                            type="hidden"
                                                            name="identifiant_document_cours"
                                                            value="<?= e((string) ($courseDocument['identifiant_document'] ?? '')) ?>"
                                                        >
                                                        <button
                                                            type="submit"
                                                            class="course-action-button course-action-button--danger"
                                                            aria-label="<?= e('Supprimer le document '.$courseTitreDocument) ?>"
                                                        >
                                                            <span aria-hidden="true">&#128465;</span>
                                                            <span class="sr-only">Supprimer</span>
                                                        </button>
                                                    </form>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </section>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if ($courseRechercheActive): ?>
        <p class="course-search-empty" data-course-search-empty hidden>Aucun document ne correspond a cette recherche.</p>
    <?php endif; ?>

    <?php if ($coursePeutGererDocuments): ?>
        <details class="course-upload-toggle">
            <summary class="button button-secondary">Ajouter un PDF</summary>

            <form
                method="post"
                action="<?= e($courseUrlAction) ?>"
                class="course-upload-form"
                enctype="multipart/form-data"
            >
                <input type="hidden" name="action" value="ajouter_document_cours">
                <input type="hidden" name="_token" value="<?= e($courseJetonCsrf) ?>">
                <input type="hidden" name="jeton_csrf" value="<?= e($courseJetonCsrf) ?>">
                <input type="hidden" name="page_redirection" value="<?= e((string) $pageCourante) ?>">
                <input type="hidden" name="rubrique_document_cours" value="<?= e($courseRubrique) ?>">

                <div class="course-upload-grid">
                    <label>
                        Titre du document
                        <input type="text" name="titre_document_cours" maxlength="160" required>
                    </label>

                    <label>
                        PDF
                        <input
                            type="file"
                            name="fichier_document_cours"
                            accept="application/pdf,.pdf"
                            required
                        >
                    </label>
                </div>

                <label>
                    Description
                    <textarea name="description_document_cours" rows="4" maxlength="2000"></textarea>
                </label>

                <button type="submit" class="button button-primary">Ajouter le PDF</button>
            </form>
        </details>
    <?php endif; ?>
</article>
