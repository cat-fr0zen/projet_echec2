<?php
$consentData = $siteData['consent'];
$legalDocuments = $siteData['legal_documents'];
?>

<div
    class="consent-gate"
    data-consent-root
    data-consent-cookie="<?= e($consentData['cookie_name']) ?>"
    role="dialog"
    aria-modal="true"
    aria-labelledby="consent-title"
    aria-describedby="consent-description"
>
    <div class="consent-panel">
        <p class="eyebrow">Accès au site</p>
        <h2 id="consent-title"><?= e($consentData['title']) ?></h2>
        <p id="consent-description" class="consent-text"><?= e($consentData['intro']) ?></p>

        <div class="consent-docs" aria-label="Documents à consulter">
            <?php foreach ($legalDocuments as $document): ?>
                <article class="consent-mini-card">
                    <h3><?= e($document['title']) ?></h3>
                    <p><?= e($document['summary']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="consent-checks">
            <?php foreach ($consentData['checks'] as $index => $checkLabel): ?>
                <label class="consent-check">
                    <input type="checkbox" data-consent-checkbox="<?= e((string) $index) ?>">
                    <span><?= e($checkLabel) ?></span>
                </label>
            <?php endforeach; ?>
        </div>

        <div class="button-row consent-actions">
            <button type="button" class="button button-primary" data-consent-accept disabled>
                <?= e($consentData['button']) ?>
            </button>
        </div>
        <p class="consent-text consent-note">Les documents complets restent accessibles en permanence dans le footer du site.</p>
    </div>
</div>
