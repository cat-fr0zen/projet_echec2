<?php
$stats = $siteData['stats'];
$schedule = $siteData['schedule'];
$compliancePoints = $siteData['compliance_points'];
$pieceCarousel = $siteData['piece_carousel'];
$authData = $siteData['authentification'];
?>

<section class="hero-grid">
    <article class="panel hero-copy reveal reveal-2">
        <p class="eyebrow">Site officiel</p>
        <h1><?= e($pageData['hero_title']) ?></h1>
        <p class="lead"><?= e($pageData['hero_text']) ?></p>

        <div class="button-row">
            <a class="button button-primary" href="#legal-hub">Voir le cadre légal</a>
            <?php if ($authData['is_authenticated']): ?>
                <a class="button button-secondary" href="<?= e(url_route('profil')) ?>">Voir mon profil</a>
            <?php endif; ?>
        </div>

        <p class="quick-note"><?= e($pageData['hero_note']) ?></p>
    </article>

    <aside class="panel hero-board reveal reveal-3" aria-hidden="true">
        <span class="hero-chip hero-chip--one">Connexion</span>
        <span class="hero-chip hero-chip--two">Cookies</span>
        <span class="hero-chip hero-chip--three">Articles</span>
        <div class="board-surface"></div>
        <div class="board-caption">
            Le site accueille maintenant un espace membre, un consentement cookies et une zone de publication en attente de modération.
        </div>
    </aside>
</section>

<section class="section-block reveal reveal-4">
    <div class="section-head">
        <p class="eyebrow">Carrousel des pièces</p>
        <h2>Chaque pièce, son mouvement et son utilité.</h2>
        <p>
            Le carrousel tourne automatiquement pour rappeler les fondamentaux du jeu d'échecs.
            Les commandes restent accessibles si l'utilisateur veut reprendre la main.
        </p>
    </div>

    <div
        class="piece-carousel"
        data-piece-carousel
        data-autoplay-ms="6800"
        style="--piece-turn-duration: 6800ms;"
        tabindex="0"
        aria-roledescription="carousel"
        aria-label="Carrousel des pièces d'échecs"
    >
        <div class="piece-stage">
            <?php foreach ($pieceCarousel as $index => $piece): ?>
                <article
                    class="piece-slide<?= $index === 0 ? ' is-active' : '' ?>"
                    data-piece-slide
                    aria-hidden="<?= $index === 0 ? 'false' : 'true' ?>"
                >
                    <div class="piece-visual">
                        <div class="piece-stage-3d" data-piece-tilt>
                            <span class="piece-aura" aria-hidden="true"></span>
                            <span class="piece-shadow-disc" aria-hidden="true"></span>
                            <span class="piece-glyph-stack" aria-hidden="true">
                                <span class="piece-glyph piece-glyph--back"><?= e($piece['glyph']) ?></span>
                                <span class="piece-glyph piece-glyph--mid"><?= e($piece['glyph']) ?></span>
                                <span class="piece-glyph piece-glyph--front"><?= e($piece['glyph']) ?></span>
                            </span>
                            <span class="piece-plinth" aria-hidden="true"></span>
                        </div>
                    </div>
                    <div class="piece-meta">
                        <p class="card-tag">Pièce <?= e((string) ($index + 1)) ?></p>
                        <h3><?= e($piece['name']) ?></h3>
                        <p class="piece-role"><?= e($piece['role']) ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="piece-controls">
            <button type="button" class="carousel-button" data-piece-prev aria-label="Voir la pièce précédente">Précédente</button>
            <div class="piece-indicators" aria-label="Sélection des pièces">
                <?php foreach ($pieceCarousel as $index => $piece): ?>
                    <button
                        type="button"
                        class="piece-indicator<?= $index === 0 ? ' is-active' : '' ?>"
                        data-piece-indicator="<?= e((string) $index) ?>"
                        aria-label="Afficher <?= e($piece['name']) ?>"
                    ></button>
                <?php endforeach; ?>
            </div>
            <button type="button" class="carousel-button" data-piece-next aria-label="Voir la pièce suivante">Suivante</button>
        </div>
    </div>
</section>

<section class="split-grid reveal reveal-5">
    <article class="panel">
        <div class="section-head section-head--compact">
            <p class="eyebrow">Fonctionnalités</p>
            <h2>Des cadres prêts pour les informations et l'espace membre.</h2>
            <p>Le design reste en place sans inventer de données tant que l'association n'a rien confirmé.</p>
        </div>

        <div class="stack-list">
            <?php foreach ($schedule as $item): ?>
                <div class="schedule-item">
                    <div class="schedule-topline">
                        <span class="schedule-day"><?= e($item['day']) ?></span>
                        <span class="schedule-slot"><?= e($item['slot']) ?></span>
                    </div>
                    <h3><?= e($item['title']) ?></h3>
                    <p><?= e($item['text']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </article>

    <article class="panel panel-contrast">
        <div class="section-head section-head--compact">
            <p class="eyebrow">Cadre juridique</p>
            <h2>Ce que le site rend visible dès la page d'accueil.</h2>
            <p>Confidentialité, consentement, propriété intellectuelle, droit à l'image et publication responsable restent explicites.</p>
        </div>

        <ul class="bullet-list">
            <?php foreach ($compliancePoints as $point): ?>
                <li><?= e($point) ?></li>
            <?php endforeach; ?>
        </ul>
    </article>
</section>

<section class="section-block reveal reveal-6">
    <div class="section-head">
        <p class="eyebrow">Informations essentielles</p>
        <h2>Trois blocs sans contenu fictif.</h2>
        <p>Les cartes conservent le design du site tout en affichant uniquement un cadre générique et vérifiable.</p>
    </div>

    <div class="card-grid card-grid--three">
        <?php foreach ($stats as $stat): ?>
            <article class="info-card">
                <p class="metric-value"><?= e($stat['value']) ?></p>
                <h3><?= e($stat['label']) ?></h3>
                <p><?= e($stat['text']) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>


