<?php
/**
 * Vue: Contact.
 *
 * Affiche les coordonnees du club (email, telephone, adresse) et un formulaire
 * de contact "prototype" (sans envoi reel).
 */
$email = (string) ($siteData['email'] ?? '');
$address = (string) ($siteData['address'] ?? '');
$phone = (string) ($siteData['phone'] ?? '');
$googleMapsUrl = (string) ($siteData['google_maps_url'] ?? '');
$isEmailLinkable = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
$gmailComposeUrl = $isEmailLinkable
    ? 'https://mail.google.com/mail/?view=cm&fs=1&to=' . rawurlencode($email)
    : '';
$hasGoogleMaps = $address !== '' && $googleMapsUrl !== '';
$googleMapsAriaLabel = sprintf("Ouvrir l'adresse %s dans Google Maps (nouvel onglet)", $address);
$gmailComposeAriaLabel = sprintf("Ouvrir Gmail pour ecrire a %s (nouvel onglet)", $email);
?>

<section class="page-banner reveal reveal-2">
    <p class="eyebrow">Contact</p>
    <h1><?= e($pageData['title']) ?></h1>
    <p><?= e($pageData['intro']) ?></p>
</section>

<section class="section-block reveal reveal-3">
    <div class="section-head">
        <p class="eyebrow">Coordonnees du club</p>
        <h2>Adresse, email et reperes officiels.</h2>
        <p>Cette page reste volontairement simple pour aller directement a l'essentiel.</p>
    </div>

    <div class="card-grid">
        <article class="info-card">
            <p class="card-tag">Email du club</p>
            <h3><?= e($email) ?></h3>
            <?php if ($isEmailLinkable): ?>
                <a
                    class="button button-secondary contact-link"
                    href="<?= e($gmailComposeUrl) ?>"
                    target="_blank"
                    rel="noopener noreferrer external"
                    referrerpolicy="no-referrer"
                    aria-label="<?= e($gmailComposeAriaLabel) ?>"
                >Ecrire au club</a>
            <?php else: ?>
                <p>Adresse officielle a completer par l'association.</p>
            <?php endif; ?>
        </article>

        <article class="info-card">
            <p class="card-tag">Adresse</p>
            <h3><?= e($address) ?></h3>
            <p>Adresse postale et lieu de reference du club.</p>
            <?php if ($hasGoogleMaps): ?>
                <a
                    class="button button-secondary contact-link"
                    href="<?= e($googleMapsUrl) ?>"
                    target="_blank"
                    rel="noopener noreferrer external"
                    referrerpolicy="no-referrer"
                    aria-label="<?= e($googleMapsAriaLabel) ?>"
                >
                    Voir sur Google Maps
                </a>
            <?php endif; ?>
        </article>
    </div>
</section>
