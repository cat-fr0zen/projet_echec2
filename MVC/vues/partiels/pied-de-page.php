<?php
/**
 * Partiel: Pied de page.
 *
 * Affiche:
 * - credits
 * - contact
 * - acces compact aux documents legaux et au registre cookies
 */
$anneeCourante = date('Y');
$credits = $donneesSite['credits'];
$documentsLegaux = $donneesSite['documents_legaux'] ?? $donneesSite['legal_documents'] ?? [];
$registreCookies = $donneesSite['registre_cookies'] ?? $donneesSite['cookie_register'] ?? [];
$adresse = (string) ($donneesSite['adresse'] ?? $donneesSite['address'] ?? '');
$googleMapsUrl = (string) ($donneesSite['google_maps_url'] ?? '');
$googleMapsEmbedUrl = (string) ($donneesSite['google_maps_embed_url'] ?? '');
$clubGoogleMapsUrl = (string) ($donneesSite['club_google_maps_url'] ?? '');
$clubGoogleReviewsUrl = (string) ($donneesSite['club_google_reviews_url'] ?? $clubGoogleMapsUrl);
$clubGoogleWriteReviewUrl = (string) ($donneesSite['club_google_write_review_url'] ?? $clubGoogleMapsUrl);
$clubGoogleReviewsLabel = (string) ($donneesSite['club_google_reviews_label'] ?? "Les Cavaliers d'Hérouville");
$reseauxSociaux = is_array($donneesSite['reseaux_sociaux'] ?? null) ? $donneesSite['reseaux_sociaux'] : [];
$googleReviewsData = is_array($donneesSite['google_reviews'] ?? null) ? $donneesSite['google_reviews'] : [];
$googleReviewsList = array_slice(
    is_array($googleReviewsData['avis'] ?? null) ? $googleReviewsData['avis'] : [],
    0,
    2
);
$hasLocationMap = $adresse !== '' && $googleMapsUrl !== '' && $googleMapsEmbedUrl !== '';
$hasClubGoogleReviews = $clubGoogleReviewsUrl !== '' && $clubGoogleWriteReviewUrl !== '';
$hasRenderedGoogleReviews = $googleReviewsList !== [];
$googleMapsAriaLabel = sprintf("Ouvrir l'adresse %s dans Google Maps (nouvel onglet)", $adresse);
$clubGoogleReviewsAriaLabel = sprintf("Consulter les avis Google de %s (nouvel onglet)", $clubGoogleReviewsLabel);
$clubGoogleWriteReviewAriaLabel = sprintf("Ajouter un avis Google pour %s (nouvel onglet)", $clubGoogleReviewsLabel);
$avisGoogleNotice = 'Vous devez être connecté à un compte Google pour consulter ou rédiger un avis.';
$renderReviewStars = static function (?float $note): string {
    if ($note === null) {
        return '';
    }

    $noteArrondie = max(0, min(5, (int) round($note)));

    return str_repeat('&#9733;', $noteArrondie) . str_repeat('&#9734;', 5 - $noteArrondie);
};
?>

<footer id="legal-hub" class="site-footer reveal reveal-6">
    <div class="site-footer__inner">
        <section class="footer-compact">
            <div class="footer-compact-bar">
                <div class="footer-compact-primary">
                    <div class="footer-compact-brand">
                        <p class="eyebrow">Cadre du site</p>
                        <h2 class="footer-title"><?= e($donneesSite['brand']) ?></h2>
                        <p class="footer-text"><?= e($donneesSite['accroche'] ?? $donneesSite['tagline'] ?? '') ?></p>
                    </div>

                    <?php if ($hasClubGoogleReviews): ?>
                        <section class="footer-review-card" aria-labelledby="footer-review-title">
                            <div class="footer-review-header">
                                <p class="eyebrow">Avis</p>
                                <h3 id="footer-review-title" class="footer-review-title">Les retours publics du club</h3>
                                <p class="footer-review-copy">
                                    Les avis restent lus sur la fiche Google officielle pour conserver des retours authentiques
                                    et les plus r&eacute;cents.
                                </p>
                            </div>

                            <div class="footer-review-actions">
                                <a
                                    class="footer-anchor"
                                    href="<?= e($clubGoogleReviewsUrl) ?>"
                                    target="_blank"
                                    rel="noopener noreferrer external"
                                    referrerpolicy="no-referrer"
                                    aria-label="<?= e($clubGoogleReviewsAriaLabel) ?>"
                                >
                                    Voir les avis
                                </a>
                                <a
                                    class="footer-anchor"
                                    href="<?= e($clubGoogleWriteReviewUrl) ?>"
                                    target="_blank"
                                    rel="noopener noreferrer external"
                                    referrerpolicy="no-referrer"
                                    aria-label="<?= e($clubGoogleWriteReviewAriaLabel) ?>"
                                >
                                    Ajouter un avis
                                </a>
                            </div>

                            <div class="footer-review-summary">
                                <?php if ($hasRenderedGoogleReviews): ?>
                                    <div class="footer-review-score">
                                        <div class="footer-review-rating-line">
                                            <strong class="footer-review-average">
                                                <?= e((string) ($googleReviewsData['note_moyenne_libelle'] ?? '')) ?>
                                            </strong>
                                            <span class="footer-review-stars" aria-hidden="true">
                                                <?= $renderReviewStars($googleReviewsData['note_moyenne'] ?? null) ?>
                                            </span>
                                        </div>
                                        <p class="footer-review-count">
                                            <?= e((string) ($googleReviewsData['nombre_avis_libelle'] ?? '')) ?>
                                        </p>
                                        <p class="footer-review-meta">
                                            <?= e((string) ($googleReviewsData['tri_libelle'] ?? '')) ?>
                                        </p>
                                        <?php if ((string) ($googleReviewsData['date_recuperation_libelle'] ?? '') !== ''): ?>
                                            <p class="footer-review-meta">
                                                <?= e((string) ($googleReviewsData['date_recuperation_libelle'] ?? '')) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <p class="footer-review-note"><?= e($avisGoogleNotice) ?></p>
                            </div>

                            <?php if ($hasRenderedGoogleReviews): ?>
                                <div class="footer-review-list" aria-label="Extrait des avis Google">
                                    <?php foreach ($googleReviewsList as $avis): ?>
                                        <?php
                                        $auteurAvis = (string) ($avis['auteur'] ?? 'Avis Google');
                                        $profilAuteurAvis = (string) ($avis['profil_auteur'] ?? '');
                                        $photoAuteurAvis = (string) ($avis['photo_auteur'] ?? '');
                                        $dateRelativeAvis = (string) ($avis['date_relative'] ?? '');
                                        $lienAvisGoogle = (string) ($avis['lien_google_maps'] ?? '');
                                        $initialeAuteurAvis = strtoupper((string) mb_substr($auteurAvis, 0, 1));
                                        ?>
                                        <article class="footer-review-item">
                                            <div class="footer-review-item-head">
                                                <div class="footer-review-author-block">
                                                    <?php if ($photoAuteurAvis !== ''): ?>
                                                        <img
                                                            class="footer-review-avatar"
                                                            src="<?= e($photoAuteurAvis) ?>"
                                                            alt=""
                                                            loading="lazy"
                                                            referrerpolicy="no-referrer"
                                                        >
                                                    <?php else: ?>
                                                        <span class="footer-review-avatar footer-review-avatar--fallback" aria-hidden="true">
                                                            <?= e($initialeAuteurAvis) ?>
                                                        </span>
                                                    <?php endif; ?>

                                                    <div class="footer-review-author-text">
                                                        <?php if ($profilAuteurAvis !== ''): ?>
                                                            <a
                                                                class="footer-review-author"
                                                                href="<?= e($profilAuteurAvis) ?>"
                                                                target="_blank"
                                                                rel="noopener noreferrer external"
                                                                referrerpolicy="no-referrer"
                                                            >
                                                                <?= e($auteurAvis) ?>
                                                            </a>
                                                        <?php else: ?>
                                                            <p class="footer-review-author"><?= e($auteurAvis) ?></p>
                                                        <?php endif; ?>

                                                        <p class="footer-review-item-meta">
                                                            <?= e((string) ($avis['note_libelle'] ?? '')) ?>
                                                            <?php if ($dateRelativeAvis !== ''): ?>
                                                                &middot; <?= e($dateRelativeAvis) ?>
                                                            <?php endif; ?>
                                                        </p>
                                                    </div>
                                                </div>

                                                <?php if ($lienAvisGoogle !== ''): ?>
                                                    <a
                                                        class="footer-review-inline-link"
                                                        href="<?= e($lienAvisGoogle) ?>"
                                                        target="_blank"
                                                        rel="noopener noreferrer external"
                                                        referrerpolicy="no-referrer"
                                                    >
                                                        Voir
                                                    </a>
                                                <?php endif; ?>
                                            </div>

                                            <p class="footer-review-item-text"><?= e((string) ($avis['texte'] ?? '')) ?></p>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </section>
                    <?php endif; ?>
                </div>

                <div class="footer-compact-meta">
                    <p class="footer-inline-line">
                        <strong>Cr&eacute;dits :</strong>
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
                        <?= e($adresse) ?>
                    </p>
                    <?php if ($reseauxSociaux !== []): ?>
                        <nav class="footer-social-card" aria-label="R&eacute;seaux sociaux du club">
                            <p class="card-tag">R&eacute;seaux sociaux</p>
                            <div class="footer-social-list">
                                <?php foreach ($reseauxSociaux as $reseauSocial): ?>
                                    <?php
                                    $nomReseau = (string) ($reseauSocial['nom'] ?? '');
                                    $urlReseau = (string) ($reseauSocial['url'] ?? '');
                                    $iconeReseau = (string) ($reseauSocial['icone'] ?? '');
                                    ?>
                                    <?php if ($nomReseau !== '' && $urlReseau !== '' && $iconeReseau !== ''): ?>
                                        <a
                                            class="footer-social-link"
                                            href="<?= e($urlReseau) ?>"
                                            target="_blank"
                                            rel="noopener noreferrer external"
                                            referrerpolicy="no-referrer"
                                            aria-label="Ouvrir <?= e($nomReseau) ?> des Cavaliers d'H&eacute;rouville (nouvel onglet)"
                                        >
                                            <img
                                                class="footer-social-icon"
                                                src="<?= e(url_ressource($iconeReseau)) ?>"
                                                alt=""
                                                loading="lazy"
                                                aria-hidden="true"
                                            >
                                            <span><?= e($nomReseau) ?></span>
                                        </a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </nav>
                    <?php endif; ?>
                    <?php if ($hasLocationMap): ?>
                        <div class="footer-location-card">
                            <p class="card-tag">Plan d'acc&egrave;s</p>
                            <div class="footer-location-map">
                                <iframe
                                    src="<?= e($googleMapsEmbedUrl) ?>"
                                    title="Aper&ccedil;u de la localisation du club"
                                    loading="lazy"
                                    referrerpolicy="no-referrer"
                                    tabindex="-1"
                                    aria-hidden="true"
                                ></iframe>
                                <a
                                    class="footer-location-link"
                                    href="<?= e($googleMapsUrl) ?>"
                                    target="_blank"
                                    rel="noopener noreferrer external"
                                    referrerpolicy="no-referrer"
                                    aria-label="<?= e($googleMapsAriaLabel) ?>"
                                >
                                    <span class="footer-location-link-badge">
                                        Voir sur Google Maps
                                        <span aria-hidden="true">&#8599;</span>
                                    </span>
                                </a>
                            </div>
                            <p class="footer-location-copy">Le plan s'ouvre dans un nouvel onglet sur le lieu exact.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <nav class="footer-anchor-list" aria-label="Acc&egrave;s rapides aux documents obligatoires">
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
                Tous droits r&eacute;serv&eacute;s.
            </p>
        </div>
    </div>
</footer>

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
            <button type="button" class="legal-modal-close" data-legal-close aria-label="Fermer la fen&ecirc;tre">&times;</button>
            <p class="eyebrow">Document obligatoire</p>
            <h2 id="<?= e($document['id']) ?>-title" class="footer-title footer-title--legal">
                <?= e($document['titre'] ?? $document['title'] ?? '') ?>
            </h2>
            <p class="footer-text"><?= e($document['resume'] ?? $document['summary'] ?? '') ?></p>

            <div class="legal-modal-body">
                <?php foreach (($document['sections'] ?? []) as $section): ?>
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
        <button type="button" class="legal-modal-close" data-legal-close aria-label="Fermer la fen&ecirc;tre">&times;</button>
        <p class="eyebrow">Cookies d&eacute;clar&eacute;s</p>
        <h2 id="cookie-register-title" class="footer-title footer-title--legal">Registre simplifi&eacute;</h2>
        <p class="footer-text">Cookies essentiels, consentement et pr&eacute;f&eacute;rences d'affichage.</p>

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
