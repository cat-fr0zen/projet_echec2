<?php
/**
 * Vue: Accueil.
 *
 * Page vitrine du club:
 * - hero + liens d'acces
 * - puzzle hebdomadaire "dammier"
 * - carrousel et blocs d'informations
 *
 * Donnees attendues:
 * - $pageData: textes de la page (hero_title, hero_text, etc.)
 * - $siteData: donnees globales (stats, schedule, etc.)
 */
$stats = $siteData['stats'];
$schedule = $siteData['schedule'];
$compliancePoints = $siteData['compliance_points'];
$pieceCarousel = $siteData['piece_carousel'];
$authData = $siteData['authentification'];
$dammierPuzzle = $siteData['dammier_puzzle'] ?? [];
$dammierClassement = $siteData['dammier_classement'] ?? [];
$dammierPeutVoirClassement = (bool) ($siteData['dammier_peut_voir_classement'] ?? false);
$dammierPayload = [
    'dammier_puzzle' => $dammierPuzzle,
    'dammier_classement' => $dammierClassement,
    'dammier_submit_url' => url_route('accueil'),
    'dammier_is_authenticated' => (bool) ($authData['is_authenticated'] ?? false),
];
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

    <aside class="panel dammier_panel reveal reveal-3">
        <div
            class="dammier_widget"
            data-dammier-root
            data-dammier-is-authenticated="<?= ($authData['is_authenticated'] ?? false) ? 'true' : 'false' ?>"
            data-dammier-submit-url="<?= e(url_route('accueil')) ?>"
            data-dammier-csrf="<?= e((string) ($siteData['jeton_csrf'] ?? '')) ?>"
        >
            <div class="dammier_header">
                <div>
                    <p class="eyebrow">Casse-tête hebdomadaire</p>
                    <h2><?= e((string) ($dammierPuzzle['dammier_title'] ?? 'Puzzle hebdomadaire')) ?></h2>
                </div>
            </div>

            <p class="dammier_intro"><?= e((string) ($dammierPuzzle['dammier_description'] ?? '')) ?></p>
            <p class="dammier_hint"><?= e((string) ($dammierPuzzle['dammier_instruction'] ?? '')) ?></p>

            <div class="dammier_layout">
                <div class="dammier_board_panel">
                    <div class="dammier_board" data-dammier-board aria-label="Damier interactif"></div>
                    <div class="dammier_meta">
                        <span class="dammier_side">Trait: <?= (($dammierPuzzle['dammier_side_to_move'] ?? 'w') === 'w') ? 'Blancs' : 'Noirs' ?></span>
                        <span class="dammier_timer" data-dammier-timer>00:00</span>
                    </div>
                </div>

                <div class="dammier_play_panel">
                    <p class="dammier_prompt" data-dammier-prompt>Clique sur une pièce, puis sur sa case d'arrivée.</p>
                    <div class="dammier_status">
                        <span class="dammier_status_chip" data-dammier-selection>Aucune pièce sélectionnée.</span>
                    </div>
                    <p class="dammier_feedback" data-dammier-feedback>Le score compte le nombre total de tentatives jusqu’à la résolution.</p>
                    <p class="dammier_hint_text" data-dammier-hint-text hidden></p>

                    <div class="dammier_actions">
                        <button type="button" class="button button-secondary dammier_action" data-dammier-reset>Rejouer</button>
                        <div class="dammier_side_actions">
                            <button type="button" class="button button-secondary dammier_icon_action" data-dammier-hint-toggle aria-label="Afficher un indice">&#128161;</button>
                            <details class="dammier_classement"<?= $dammierPeutVoirClassement ? '' : ' data-dammier-locked="true"' ?>>
                                <summary>+ classement</summary>
                                <?php if ($dammierPeutVoirClassement): ?>
                                    <ol class="dammier_ranking_list" data-dammier-ranking-list>
                                        <?php foreach ($dammierClassement as $score): ?>
                                            <li class="dammier_ranking_item">
                                                <span><?= e((string) ($score['dammier_display_name'] ?? 'Membre')) ?></span>
                                                <span><?= e((string) ($score['dammier_moves_count'] ?? 0)) ?> coups</span>
                                                <span><?= e(gmdate('i:s', (int) ($score['dammier_elapsed_seconds'] ?? 0))) ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ol>
                                    <?php if ($dammierClassement === []): ?>
                                        <p class="dammier_ranking_empty" data-dammier-ranking-empty>Aucun score enregistr? cette semaine.</p>
                                    <?php else: ?>
                                        <p class="dammier_ranking_empty" data-dammier-ranking-empty hidden>Aucun score enregistr? cette semaine.</p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p class="dammier_ranking_locked">Connecte-toi pour voir le classement hebdomadaire.</p>
                                <?php endif; ?>
                            </details>
                        </div>
                    </div>
                </div>
            </div>

            <script type="application/json" data-dammier-payload><?= json_encode($dammierPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
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
