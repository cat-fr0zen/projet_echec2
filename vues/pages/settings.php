<?php
$cookieRegister = $siteData['cookie_register'];
$authData = $siteData['auth'];
?>

<section class="page-banner reveal reveal-2">
    <p class="eyebrow">Parametres</p>
    <h1><?= e($pageData['title']) ?></h1>
    <p><?= e($pageData['intro']) ?></p>
</section>

<section class="split-grid reveal reveal-3">
    <article class="panel">
        <div class="section-head section-head--compact">
            <p class="eyebrow">Theme et preferences</p>
            <h2>Reglages de l interface.</h2>
            <p>Le theme clair ou sombre peut etre change a tout moment depuis l interrupteur en haut a droite.</p>
        </div>

        <div class="stack-list">
            <div class="schedule-item">
                <h3>Theme visuel</h3>
                <p>Le choix entre theme clair et theme sombre est memorise dans un cookie de preference.</p>
            </div>
            <div class="schedule-item">
                <h3>Consentement</h3>
                <p>Le consentement obligatoire a l entree du site peut etre reinitialise si tu veux revoir la fenetre d information.</p>
                <button type="button" class="button button-secondary" data-reset-consent>Revoir le consentement</button>
            </div>
        </div>
    </article>

    <article class="panel panel-contrast">
        <div class="section-head section-head--compact">
            <p class="eyebrow">Compte membre</p>
            <h2><?= $authData['is_authenticated'] ? 'Session active' : 'Connexion non active' ?></h2>
            <p>
                <?= $authData['is_authenticated']
                    ? 'Un cookie de session PHP maintient votre connexion et permet l acces au profil ainsi qu a la creation d articles.'
                    : 'Aucune session membre active pour le moment. La connexion par email ouvre l acces au profil et a la redaction d articles.' ?>
            </p>
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
        <?php foreach ($cookieRegister as $cookie): ?>
            <article class="info-card">
                <p class="card-tag"><?= e($cookie['type']) ?></p>
                <h3><?= e($cookie['name']) ?></h3>
                <p><?= e($cookie['purpose']) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>
