<?php
$anneeCourante = date('Y');
$credits = $donneesSite['credits'];
$documentsLegaux = $donneesSite['documents_legaux'] ?? $donneesSite['legal_documents'] ?? [];
$registreCookies = $donneesSite['registre_cookies'] ?? $donneesSite['cookie_register'] ?? [];
?>

<footer class="site-footer reveal reveal-6">
    <div class="site-footer__inner">
        <div class="footer-overview">
            <section class="footer-intro-card">
                <p class="eyebrow">Cadre du site</p>
                <h2 class="footer-title"><?= e($donneesSite['brand']) ?></h2>
                <p class="footer-text"><?= e($donneesSite['accroche'] ?? $donneesSite['tagline'] ?? '') ?></p>

                <nav class="footer-anchor-list" aria-label="Accès rapides aux documents obligatoires">
                    <?php foreach ($documentsLegaux as $document): ?>
                        <a class="footer-anchor" href="#<?= e($document['id']) ?>"><?= e($document['titre'] ?? $document['title'] ?? '') ?></a>
                    <?php endforeach; ?>
                    <a class="footer-anchor" href="#cookie-register">Cookies</a>
                </nav>
            </section>

            <div class="footer-stack">
                <section class="footer-detail-card">
                    <p class="eyebrow">Crédits</p>
                    <p class="footer-text"><strong>Auteur du site :</strong> <?= e($credits['auteur_site'] ?? $credits['site_author'] ?? '') ?></p>
                    <p class="footer-text"><strong>Publication associative :</strong> <?= e($credits['publication_associative'] ?? $credits['association_publisher'] ?? '') ?></p>
                </section>

                <section class="footer-detail-card">
                    <p class="eyebrow">Contact</p>
                    <p class="footer-text"><?= e($donneesSite['courriel'] ?? $donneesSite['email'] ?? '') ?></p>
                    <p class="footer-text"><?= e($donneesSite['adresse'] ?? $donneesSite['address'] ?? '') ?></p>
                </section>
            </div>
        </div>

        <section id="legal-hub" class="legal-hub">
            <div class="section-head section-head--compact legal-intro">
                <p class="eyebrow">Documents obligatoires</p>
                <h2 class="footer-title footer-title--legal">Mentions légales, confidentialité et conditions d'utilisation</h2>
                <p class="footer-text">
                    Ces informations encadrent la consultation du site, la gestion des données, le droit à l'image
                    et la propriété intellectuelle des contenus publiés.
                </p>
            </div>

            <div class="legal-layout">
                <div class="legal-grid">
                    <?php foreach ($documentsLegaux as $document): ?>
                        <details class="legal-card" id="<?= e($document['id']) ?>">
                            <summary>
                                <span class="legal-summary-title"><?= e($document['titre'] ?? $document['title'] ?? '') ?></span>
                                <span class="legal-summary-text"><?= e($document['resume'] ?? $document['summary'] ?? '') ?></span>
                            </summary>

                            <div class="legal-card-body">
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
                        </details>
                    <?php endforeach; ?>
                </div>

                <aside id="cookie-register" class="cookie-register">
                    <div class="section-head section-head--compact">
                        <p class="eyebrow">Cookies déclarés</p>
                        <h3 class="footer-title footer-title--legal">Registre simplifié</h3>
                        <p class="footer-text">Cookies essentiels, consentement et préférences d'affichage.</p>
                    </div>

                    <div class="cookie-register-grid">
                        <?php foreach ($registreCookies as $cookie): ?>
                            <article class="info-card">
                                <p class="card-tag"><?= e($cookie['type']) ?></p>
                                <h3><?= e($cookie['nom'] ?? $cookie['name'] ?? '') ?></h3>
                                <p><?= e($cookie['finalite'] ?? $cookie['purpose'] ?? '') ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </aside>
            </div>
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
</footer>
