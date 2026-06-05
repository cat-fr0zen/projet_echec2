<?php
/**
 * Partiel: Consentement cookies.
 *
 * Presente une information claire sur les cookies indispensables, avec
 * un choix simple entre "essentiels seulement" et "essentiels + theme".
 */
$donneesConsentement = $donneesSite['consentement'] ?? $donneesSite['consent'];
$documentsLegaux = $donneesSite['documents_legaux'] ?? $donneesSite['legal_documents'] ?? [];
?>

<div
    class="consent-gate"
    data-consent-root
    data-consent-cookie="<?= e($donneesConsentement['nom_cookie'] ?? $donneesConsentement['cookie_name'] ?? 'site_consent') ?>"
    role="dialog"
    aria-modal="true"
    aria-labelledby="consent-title"
    aria-describedby="consent-description"
>
    <div class="consent-panel">
        <p class="eyebrow">Accès au site</p>
        <h2 id="consent-title"><?= e($donneesConsentement['titre'] ?? $donneesConsentement['title'] ?? '') ?></h2>
        <p id="consent-description" class="consent-text"><?= e($donneesConsentement['introduction'] ?? $donneesConsentement['intro'] ?? '') ?></p>

        <div class="consent-docs" aria-label="Documents à consulter">
            <?php foreach ($documentsLegaux as $document): ?>
                <article class="consent-mini-card">
                    <h3><?= e($document['titre'] ?? $document['title'] ?? '') ?></h3>
                    <p><?= e($document['resume'] ?? $document['summary'] ?? '') ?></p>
                </article>
            <?php endforeach; ?>
        </div>

        <p class="consent-text">
            Les cookies essentiels restent nécessaires à la sécurité, à la session membre et au bon fonctionnement du site.
            Le cookie de thème est facultatif.
        </p>

        <div class="button-row consent-actions">
            <button type="button" class="button button-secondary" data-consent-continue>
                Continuer avec les cookies essentiels
            </button>
            <button type="button" class="button button-primary" data-consent-accept>
                Autoriser aussi le cookie de thème
            </button>
        </div>
        <p class="consent-text consent-note">Les documents complets restent accessibles en permanence dans le footer du site.</p>
    </div>
</div>
