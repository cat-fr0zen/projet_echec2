<?php
$year = date('Y');
$credits = $siteData['credits'];
$legalDocuments = $siteData['legal_documents'];
$cookieRegister = $siteData['cookie_register'];
?>

<footer class="site-footer reveal reveal-6">
    <div class="footer-grid">
        <section>
            <p class="eyebrow">Cadre du site</p>
            <h2 class="footer-title"><?= e($siteData['brand']) ?></h2>
            <p class="footer-text"><?= e($siteData['tagline']) ?></p>
        </section>

        <section>
            <p class="eyebrow">Crédits</p>
            <p class="footer-text"><strong>Auteur du site :</strong> <?= e($credits['site_author']) ?></p>
            <p class="footer-text"><strong>Publication associative :</strong> <?= e($credits['association_publisher']) ?></p>
        </section>

        <section>
            <p class="eyebrow">Publication officielle</p>
            <p class="footer-text"><?= e($siteData['city']) ?></p>
            <p class="footer-text"><?= e($siteData['address']) ?></p>
        </section>
    </div>

    <section id="legal-hub" class="legal-hub">
        <div class="section-head section-head--compact">
            <p class="eyebrow">Documents obligatoires</p>
            <h2 class="footer-title footer-title--legal">Mentions légales, confidentialité et conditions d'utilisation</h2>
            <p class="footer-text">
                Ces informations encadrent la consultation du site, la gestion des données, les droits des utilisateurs,
                le droit à l'image et la propriété intellectuelle des contenus.
            </p>
        </div>

        <div class="legal-grid">
            <?php foreach ($legalDocuments as $document): ?>
                <details class="legal-card" id="<?= e($document['id']) ?>">
                    <summary>
                        <span class="legal-summary-title"><?= e($document['title']) ?></span>
                        <span class="legal-summary-text"><?= e($document['summary']) ?></span>
                    </summary>

                    <div class="legal-card-body">
                        <?php foreach ($document['sections'] as $section): ?>
                            <section class="legal-section">
                                <h3><?= e($section['title']) ?></h3>
                                <ul class="legal-list">
                                    <?php foreach ($section['items'] as $item): ?>
                                        <li><?= e($item) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </section>
                        <?php endforeach; ?>
                    </div>
                </details>
            <?php endforeach; ?>
        </div>

        <div class="cookie-register">
            <div class="section-head section-head--compact">
                <p class="eyebrow">Cookies déclarés</p>
                <h3 class="footer-title footer-title--legal">Registre simplifié des cookies</h3>
                <p class="footer-text">Ce tableau résume les cookies et préférences actuellement utilisés dans le prototype.</p>
            </div>

            <div class="card-grid card-grid--three">
                <?php foreach ($cookieRegister as $cookie): ?>
                    <article class="info-card">
                        <p class="card-tag"><?= e($cookie['type']) ?></p>
                        <h3><?= e($cookie['name']) ?></h3>
                        <p><?= e($cookie['purpose']) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <p class="footer-meta">
        &copy; <?= e((string) $year) ?> <?= e($siteData['brand']) ?>.
        Conception du site : <?= e($credits['site_author']) ?>.
        Publication associative : <?= e($credits['association_publisher']) ?>.
        Tous droits réservés.
    </p>
</footer>
