<?php
/**
 * Vue: Parametres.
 *
 * Page accessibilite et informations RGPD:
 * - acces direct aux reglages de lecture confortable
 * - recap cookies/consentement
 *
 * Variables attendues:
 * - $donneesSite['registre_cookies']
 */
$registreCookies = $donneesSite['registre_cookies'] ?? $donneesSite['cookie_register'] ?? [];
?>

<section class="page-banner reveal reveal-2">
    <p class="eyebrow">Parametres</p>
    <h1><?= e($donneesPage['titre']) ?></h1>
    <p><?= e($donneesPage['intro']) ?></p>
</section>

<section class="split-grid reveal reveal-3">
    <article class="panel">
        <div class="section-head section-head--compact">
            <p class="eyebrow">Accessibilite</p>
            <h2>Lecture confortable et aide visuelle.</h2>
            <p>Active un mode plus lisible pour les malvoyants, les personnes fatiguees visuellement ou celles qui veulent une lecture plus confortable.</p>
        </div>

        <div class="stack-list">
            <div class="schedule-item">
                <h3>Ameliorations disponibles</h3>
                <p>Taille du texte, contraste renforce, police plus lisible, espacement du texte, boutons plus visibles et lecture vocale.</p>
            </div>
            <div class="schedule-item">
                <h3>Ouvrir les reglages</h3>
                <p>Tu peux lancer le mode lecture confortable en un clic, puis affiner chaque option selon ton confort visuel.</p>
                <button type="button" class="button button-primary" data-accessibility-open>Ouvrir les options d'accessibilite</button>
            </div>
        </div>
    </article>

    <article class="panel panel-contrast">
        <div class="section-head section-head--compact">
            <p class="eyebrow">Consentement</p>
            <h2>Gestion du consentement</h2>
            <p>Le consentement obligatoire a l'entree du site peut etre reinitialise si tu veux revoir la fenetre d'information.</p>
        </div>

        <div class="stack-list">
            <div class="schedule-item">
                <h3>Revoir la fenetre d'information</h3>
                <p>Le site reaffichera le cadre de consentement lors du prochain chargement.</p>
                <button type="button" class="button button-secondary" data-reset-consent>Revoir le consentement</button>
            </div>
        </div>
    </article>
</section>

<section class="section-block reveal reveal-4">
    <div class="section-head">
        <p class="eyebrow">Registre cookies</p>
        <h2>Cookies et usages declares.</h2>
        <p>Voici les usages actuellement prevus par le prototype.</p>
    </div>

    <div class="card-grid card-grid--three">
        <?php foreach ($registreCookies as $cookie): ?>
            <article class="info-card">
                <p class="card-tag"><?= e($cookie['type']) ?></p>
                <h3><?= e($cookie['nom']) ?></h3>
                <p><?= e($cookie['finalite']) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>
