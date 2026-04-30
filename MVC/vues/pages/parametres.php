<?php
$registreCookies = $donneesSite['registre_cookies'] ?? $donneesSite['cookie_register'] ?? [];
$donneesAuthentification = $donneesSite['authentification'];
?>

<section class="page-banner reveal reveal-2">
    <p class="eyebrow">Paramètres</p>
    <h1><?= e($donneesPage['titre']) ?></h1>
    <p><?= e($donneesPage['intro']) ?></p>
</section>

<section class="split-grid reveal reveal-3">
    <article class="panel">
        <div class="section-head section-head--compact">
            <p class="eyebrow">Thème et préférences</p>
            <h2>Réglages de l'interface.</h2>
            <p>Le thème clair ou sombre peut être changé à tout moment depuis l'interrupteur en haut à droite.</p>
        </div>

        <div class="stack-list">
            <div class="schedule-item">
                <h3>Thème visuel</h3>
                <p>Le choix entre thème clair et thème sombre est mémorisé dans un cookie de préférence.</p>
            </div>
            <div class="schedule-item">
                <h3>Consentement</h3>
                <p>Le consentement obligatoire à l'entrée du site peut être réinitialisé si tu veux revoir la fenêtre d'information.</p>
                <button type="button" class="button button-secondary" data-reset-consent>Revoir le consentement</button>
            </div>
        </div>
    </article>

    <article class="panel panel-contrast">
        <div class="section-head section-head--compact">
            <p class="eyebrow">Compte membre</p>
            <h2><?= $donneesAuthentification['est_connecte'] ? 'Session active' : 'Connexion non active' ?></h2>
            <p>
                <?= $donneesAuthentification['est_connecte']
                    ? "Un cookie de session PHP maintient votre connexion et permet l'accès au profil ainsi qu'à la création d'articles."
                    : "Aucune session membre active pour le moment. La connexion par email ouvre l'accès au profil et à la rédaction d'articles." ?>
            </p>
        </div>
    </article>
</section>

<section class="section-block reveal reveal-4">
    <div class="section-head">
        <p class="eyebrow">Registre cookies</p>
        <h2>Cookies et usages déclarés.</h2>
        <p>Voici les usages actuellement prévus par le prototype.</p>
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

