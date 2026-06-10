<section class="section-block reveal reveal-5" data-accueil-slot="pieces_echecs">
    <div class="section-head">
        <p class="eyebrow">Pièces d'échecs</p>
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
                            <span class="piece-figure" aria-hidden="true">
                                <span class="piece-figure-core">
                                    <span class="piece-glyph-shell">
                                        <span class="piece-glyph"><?= e($piece['glyph']) ?></span>
                                    </span>
                                </span>
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
