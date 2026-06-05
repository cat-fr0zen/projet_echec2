<?php
/**
 * Vue: Cours.
 *
 * La route historique reste `guide`, mais l'interface membre presente
 * maintenant une vraie page Cours avec deux grandes portes d'entree :
 * - les livrets de A a E
 * - les cours / la methodologie / la strategie
 *
 * Donnees attendues:
 * - $donneesSite['livrets_cours']
 * - $donneesSite['cartes_cours_strategie']
 */
$livretsCours = is_array($donneesSite['livrets_cours'] ?? null) ? $donneesSite['livrets_cours'] : [];
$cartesCoursStrategie = is_array($donneesSite['cartes_cours_strategie'] ?? null) ? $donneesSite['cartes_cours_strategie'] : [];
?>

<section class="page-banner reveal reveal-2">
    <p class="eyebrow">Cours</p>
    <h1><?= e($donneesPage['titre']) ?></h1>
    <p><?= e($donneesPage['intro']) ?></p>
</section>

<section class="split-grid reveal reveal-3">
    <article class="panel">
        <div class="section-head section-head--compact">
            <p class="eyebrow">Entrée 01</p>
            <h2>Livrets de A à E</h2>
            <p>
                Un accès simple aux supports de progression par niveau, pour retrouver
                rapidement le bon livret sans se perdre dans le reste du site.
            </p>
        </div>

        <div class="button-row">
            <a class="button button-primary" href="#livrets-cours">Ouvrir les livrets</a>
        </div>
    </article>

    <article class="panel panel-contrast">
        <div class="section-head section-head--compact">
            <p class="eyebrow">Entrée 02</p>
            <h2>Cours / méthodologie / stratégie</h2>
            <p>
                Tout ce qui aide à mieux réfléchir pendant une partie : thèmes de cours,
                méthode de réflexion, lecture de position et progression stratégique.
            </p>
        </div>

        <div class="button-row">
            <a class="button button-secondary" href="#cours-strategie">Voir les contenus</a>
        </div>
    </article>
</section>

<section id="livrets-cours" class="section-block reveal reveal-4">
    <div class="section-head">
        <p class="eyebrow">Livrets</p>
        <h2>Les livrets de progression du club.</h2>
        <p>
            Une organisation simple de A à E pour ranger les repères techniques,
            les notions vues au club et les bases à revoir à la maison.
        </p>
    </div>

    <div class="card-grid card-grid--three">
        <?php foreach ($livretsCours as $livret): ?>
            <article class="info-card">
                <p class="card-tag"><?= e((string) ($livret['tag'] ?? 'Livret')) ?></p>
                <h3><?= e((string) ($livret['title'] ?? 'Support')) ?></h3>
                <p><?= e((string) ($livret['text'] ?? '')) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section id="cours-strategie" class="section-block reveal reveal-5">
    <div class="section-head">
        <p class="eyebrow">Ressources</p>
        <h2>Cours, méthodologie et stratégie au même endroit.</h2>
        <p>
            Cette zone rassemble les thèmes utiles au joueur de club pour mieux
            structurer son raisonnement et progresser d'une séance à l'autre.
        </p>
    </div>

    <div class="card-grid card-grid--three">
        <?php foreach ($cartesCoursStrategie as $carteCours): ?>
            <article class="info-card">
                <p class="card-tag"><?= e((string) ($carteCours['tag'] ?? 'Cours')) ?></p>
                <h3><?= e((string) ($carteCours['title'] ?? 'Contenu')) ?></h3>
                <p><?= e((string) ($carteCours['text'] ?? '')) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>
