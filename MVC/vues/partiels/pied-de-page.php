<?php
/**
 * Partiel: Pied de page.
 *
 * Affiche:
 * - crédits
 * - contact
 * - accès compact aux documents légaux et au registre cookies
 */
$anneeCourante = date('Y');
$credits = $donneesSite['credits'];
$documentsLegaux = $donneesSite['documents_legaux'] ?? $donneesSite['legal_documents'] ?? [];
$registreCookies = $donneesSite['registre_cookies'] ?? $donneesSite['cookie_register'] ?? [];
?>

<footer id="legal-hub" class="site-footer reveal reveal-6">
    <div class="site-footer__inner">
        <section class="footer-compact">
            <div class="footer-compact-bar">
                <div class="footer-compact-brand">
                    <p class="eyebrow">Cadre du site</p>
                    <h2 class="footer-title"><?= e($donneesSite['brand']) ?></h2>
                    <p class="footer-text"><?= e($donneesSite['accroche'] ?? $donneesSite['tagline'] ?? '') ?></p>
                </div>

                <div class="footer-compact-meta">
                    <p class="footer-inline-line">
                        <strong>Crédits :</strong>
                        <?= e($credits['auteur_site'] ?? $credits['site_author'] ?? '') ?>
                    </p>
                    <p class="footer-inline-line">
                        <strong>Publication associative :</strong>
                        <?= e($credits['publication_associative'] ?? $credits['association_publisher'] ?? '') ?>
                    </p>
                    <p class="footer-inline-line">
                        <strong>Contact :</strong>
                        <?= e($donneesSite['courriel'] ?? $donneesSite['email'] ?? '') ?>
                    </p>
                    <p class="footer-inline-line">
                        <strong>Adresse :</strong>
                        <?= e($donneesSite['adresse'] ?? $donneesSite['address'] ?? '') ?>
                    </p>
                </div>
            </div>

            <nav class="footer-anchor-list" aria-label="Accès rapides aux documents obligatoires">
                <?php foreach ($documentsLegaux as $document): ?>
                    <button
                        type="button"
                        class="footer-anchor footer-anchor--button"
                        data-legal-open="<?= e($document['id']) ?>"
                    >
                        <?= e($document['titre'] ?? $document['title'] ?? '') ?>
                    </button>
                <?php endforeach; ?>
                <button
                    type="button"
                    class="footer-anchor footer-anchor--button"
                    data-legal-open="cookie-register"
                >
                    Cookies
                </button>
            </nav>
        </section>

        <div class="footer-meta-bar">
            <p class="footer-meta">
                &copy; <?= e((string) $anneeCourante) ?> <?= e($donneesSite['brand']) ?>.
                Conception du site : <?= e($credits['auteur_site'] ?? $credits['site_author'] ?? '') ?>.
                Publication associative : <?= e($credits['publication_associative'] ?? $credits['association_publisher'] ?? '') ?>.
                Tous droits réservés.
            </p>
        </div>
    </div>

    <?php foreach ($documentsLegaux as $document): ?>
        <div
            class="legal-modal"
            data-legal-modal="<?= e($document['id']) ?>"
            hidden
            role="dialog"
            aria-modal="true"
            aria-labelledby="<?= e($document['id']) ?>-title"
        >
            <div class="legal-modal-panel">
                <button type="button" class="legal-modal-close" data-legal-close aria-label="Fermer la fenêtre">×</button>
                <p class="eyebrow">Document obligatoire</p>
                <h2 id="<?= e($document['id']) ?>-title" class="footer-title footer-title--legal">
                    <?= e($document['titre'] ?? $document['title'] ?? '') ?>
                </h2>
                <p class="footer-text"><?= e($document['resume'] ?? $document['summary'] ?? '') ?></p>

                <div class="legal-modal-body">
                    <?php foreach ($document['sections'] as $section): ?>
                        <section class="legal-section">
                            <h3><?= e($section['titre'] ?? $section['title'] ?? '') ?></h3>
                            <ul class="legal-list">
                                <?php foreach (($section['elements'] ?? $section['items'] ?? []) as $element): ?>
                                    <li><?= e($element) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </section>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <div
        class="legal-modal"
        data-legal-modal="cookie-register"
        hidden
        role="dialog"
        aria-modal="true"
        aria-labelledby="cookie-register-title"
    >
        <div class="legal-modal-panel">
            <button type="button" class="legal-modal-close" data-legal-close aria-label="Fermer la fenêtre">×</button>
            <p class="eyebrow">Cookies déclarés</p>
            <h2 id="cookie-register-title" class="footer-title footer-title--legal">Registre simplifié</h2>
            <p class="footer-text">Cookies essentiels, consentement et préférences d'affichage.</p>

            <div class="legal-modal-cookie-grid">
                <?php foreach ($registreCookies as $cookie): ?>
                    <article class="info-card">
                        <p class="card-tag"><?= e($cookie['type']) ?></p>
                        <h3><?= e($cookie['nom'] ?? $cookie['name'] ?? '') ?></h3>
                        <p><?= e($cookie['finalite'] ?? $cookie['purpose'] ?? '') ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</footer>
