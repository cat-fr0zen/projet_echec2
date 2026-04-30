<?php
$joinSteps = $siteData['join_steps'];
$authData = $siteData['auth'];
?>

<section class="page-banner reveal reveal-2">
    <p class="eyebrow">Contact</p>
    <h1><?= e($pageData['title']) ?></h1>
    <p><?= e($pageData['intro']) ?></p>
</section>

<section class="section-block reveal reveal-3">
    <div class="section-head">
        <p class="eyebrow">Responsables et publication</p>
        <h2>Un contact propre, un espace membre et une publication encadrée.</h2>
        <p>Les noms fournis sont intégrés. Les coordonnées pratiques restent en attente de validation officielle par l'association.</p>
    </div>

    <div class="card-grid card-grid--three">
        <article class="info-card">
            <p class="card-tag">Publication associative</p>
            <h3><?= e($siteData['credits']['association_publisher']) ?></h3>
            <p>Référence pour la publication institutionnelle, la validation des contenus et le cadre associatif.</p>
        </article>

        <article class="info-card">
            <p class="card-tag">Conception du site</p>
            <h3><?= e($siteData['credits']['site_author']) ?></h3>
            <p>Auteur de la structure visuelle, du front, du footer légal et des composants techniques du site.</p>
        </article>

        <article class="info-card">
            <p class="card-tag">Coordonnées officielles</p>
            <h3>À publier</h3>
            <p>Adresse, email et téléphone seront affichés ici dès que l'association les aura communiqués officiellement.</p>
        </article>
    </div>
</section>

<section class="split-grid reveal reveal-4">
    <article class="panel">
        <div class="section-head section-head--compact">
            <p class="eyebrow">Avant toute prise de contact</p>
            <h2>Trois étapes simples et vérifiables.</h2>
            <p>Le parcours reste sobre tant que les informations définitives de l'association ne sont pas publiées.</p>
        </div>

        <ol class="step-list">
            <?php foreach ($joinSteps as $step): ?>
                <li><?= e($step) ?></li>
            <?php endforeach; ?>
        </ol>
    </article>

    <article class="panel panel-contrast">
        <div class="section-head section-head--compact">
            <p class="eyebrow">Compte et publication</p>
            <h2><?= $authData['is_authenticated'] ? 'Ton compte est actif.' : 'Le compte membre est optionnel mais prêt.' ?></h2>
            <p>
                <?= $authData['is_authenticated']
                    ? "Tu peux maintenant modifier ton profil, gérer ta description et proposer des articles soumis à modération future."
                    : "La connexion par email ouvre un profil éditable, un espace de rédaction d'articles et des réglages de compte protégés." ?>
            </p>
        </div>
    </article>
</section>
