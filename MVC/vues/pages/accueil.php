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
$pieceCarousel = $siteData['piece_carousel'];
$authData = $siteData['authentification'];
$dammierPuzzle = $siteData['dammier_puzzle'] ?? [];
$dammierClassement = $siteData['dammier_classement'] ?? [];
$dammierPeutVoirClassement = (bool) ($siteData['dammier_peut_voir_classement'] ?? false);
$horairesClub = is_array($siteData['horaires_club'] ?? null) ? $siteData['horaires_club'] : [];
$resumeHorairesClub = is_array($siteData['resume_horaires_club'] ?? null) ? $siteData['resume_horaires_club'] : [];
$itemsHorairesClub = is_array($horairesClub['items'] ?? null) ? $horairesClub['items'] : [];
$libelleSaisonHoraires = (string) ($horairesClub['season_label'] ?? 'Horaires du club');
$messageJourFerie = (string) ($horairesClub['holiday_notice'] ?? '');
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
            <a
                class="button button-primary"
                href="https://www.helloasso.com/associations/les-cavaliers-d-herouville"
                target="_blank"
                rel="noopener noreferrer external"
                referrerpolicy="no-referrer"
                aria-label="Adhérer au club sur HelloAsso (nouvel onglet)"
            >
                Adhérer
            </a>
            <a class="button button-secondary" href="#legal-hub">Voir le cadre légal</a>
            <?php if ($authData['is_authenticated']): ?>
                <a class="button button-secondary" href="<?= e(url_route('profil')) ?>">Voir mon profil</a>
            <?php endif; ?>
        </div>

        <p class="quick-note"><?= e($pageData['hero_note']) ?></p>

        <?php if ($resumeHorairesClub !== []): ?>
            <section class="home-schedule-card" aria-labelledby="home-schedule-title">
                <p class="eyebrow">Horaires</p>
                <h2 id="home-schedule-title"><?= e($libelleSaisonHoraires) ?></h2>
                <dl class="home-schedule-list">
                    <?php foreach ($resumeHorairesClub as $horaireResume): ?>
                        <div class="home-schedule-row">
                            <dt>
                                <?= e((string) ($horaireResume['day'] ?? '')) ?>
                                <?php if (!empty($horaireResume['has_holiday'])): ?>
                                    <span class="schedule-exception-badge">Jour férié</span>
                                <?php endif; ?>
                            </dt>
                            <dd><?= e((string) ($horaireResume['times'] ?? '')) ?></dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
                <?php if ($messageJourFerie !== ''): ?>
                    <p class="home-schedule-holiday"><strong>Jour férié:</strong> <?= e($messageJourFerie) ?></p>
                <?php endif; ?>
            </section>
        <?php endif; ?>
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
                    <p id="dammier-board-help" class="sr-only">
                        Utilise Tab ou les flèches pour parcourir le damier. Sélectionne d'abord une pièce de ton camp,
                        puis sa case d'arrivée pour proposer le coup.
                    </p>
                    <div
                        class="dammier_board"
                        data-dammier-board
                        role="group"
                        aria-label="Damier interactif"
                        aria-describedby="dammier-board-help dammier-feedback"
                    ></div>
                    <div class="dammier_meta">
                        <span class="dammier_side">Trait: <?= (($dammierPuzzle['dammier_side_to_move'] ?? 'w') === 'w') ? 'Blancs' : 'Noirs' ?></span>
                        <span class="dammier_timer" data-dammier-timer role="timer" aria-live="off">00:00</span>
                    </div>
                </div>

                <div class="dammier_play_panel">
                    <p class="dammier_prompt" data-dammier-prompt>Clique sur une pièce, puis sur sa case d'arrivée.</p>
                    <div class="dammier_status">
                        <span class="dammier_status_chip" data-dammier-selection role="status" aria-live="polite">Aucune pièce sélectionnée.</span>
                    </div>
                    <p id="dammier-feedback" class="dammier_feedback" data-dammier-feedback role="status" aria-live="polite">Le score compte le nombre total de tentatives jusqu’à la résolution.</p>
                    <p id="dammier-hint-text" class="dammier_hint_text" data-dammier-hint-text hidden aria-live="polite"></p>

                    <div class="dammier_actions">
                        <button type="button" class="button button-secondary dammier_action" data-dammier-reset>Rejouer</button>
                        <div class="dammier_side_actions">
                            <button
                                type="button"
                                class="button button-secondary dammier_icon_action"
                                data-dammier-hint-toggle
                                aria-label="Afficher un indice"
                                aria-controls="dammier-hint-text"
                                aria-expanded="false"
                            >&#128161;</button>
                            <details class="dammier_classement"<?= $dammierPeutVoirClassement ? '' : ' data-dammier-locked="true"' ?>>
                                <summary>+ classement</summary>
                                <?php if ($dammierPeutVoirClassement): ?>
                                    <ol class="dammier_ranking_list" data-dammier-ranking-list aria-live="polite">
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

<section class="split-grid reveal reveal-4">
    <article class="panel">
        <div class="section-head section-head--compact">
            <h2>Présentation</h2>
            <p>Bienvenue chez Les Cavaliers d’Hérouville, un club d’échecs pas comme les autres ! Notre mission ? Faire découvrir et partager la passion du jeu d’échecs à tous. Dès 5 ans jusqu’à 105 ans. Débutants curieux ou pros de la stratégie. Convivialité, apprentissage, progression… le tout dans la bonne humeur ! Que vous vouliez apprendre, progresser ou simplement jouer pour le plaisir… Venez faire travailler vos neurones avec nous dans une ambiance chaleureuse et stimulante ! Rejoignez-nous et faites partie d’une communauté passionnée !</p>
        </div>
    </article>

    <article class="panel panel-contrast">
        <div class="section-head section-head--compact">
            <h2>Liste de liens utiles</h2>
        </div>

        <ul class="bullet-list useful-links-list">
            <li>
                <a
                    class="useful-link"
                    href="https://www.echecs.asso.fr/ListeJoueurs.aspx?Action=JOUEURCLUBREF&amp;ClubRef=3012"
                    target="_blank"
                    rel="noopener noreferrer external"
                    referrerpolicy="no-referrer"
                >
                    Fédération Française D'Échecs(FFE)
                </a>
            </li>
            <li>
                <a
                    class="useful-link"
                    href="https://www.normandie-echecs.fr/cdje14"
                    target="_blank"
                    rel="noopener noreferrer external"
                    referrerpolicy="no-referrer"
                >
                    Ligue de Normandie des échecs
                </a>
            </li>
        </ul>
    </article>
</section>

<section class="section-block reveal reveal-5">
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

    <?php if ($itemsHorairesClub !== []): ?>
        <details id="emploi-du-temps-complet" class="schedule-full-card">
            <summary>
                <span>Consulter l'emploi du temps complet</span>
            </summary>
            <div class="schedule-full-content">
                <div class="section-head section-head--compact">
                    <p class="eyebrow">Emploi du temps</p>
                    <h3><?= e($libelleSaisonHoraires) ?></h3>
                    <?php if ($messageJourFerie !== ''): ?>
                        <p><strong>Jour férié:</strong> <?= e($messageJourFerie) ?></p>
                    <?php endif; ?>
                </div>

                <div class="schedule-full-grid">
                    <?php foreach ($itemsHorairesClub as $horaire): ?>
                        <article class="schedule-full-item">
                            <p class="card-tag">
                                <?= e((string) ($horaire['day'] ?? '')) ?>
                                <?php if (!empty($horaire['is_holiday'])): ?>
                                    · Jour férié
                                <?php endif; ?>
                            </p>
                            <h4><?= e((string) ($horaire['time'] ?? '')) ?></h4>
                            <p class="card-subtitle"><?= e((string) ($horaire['title'] ?? '')) ?></p>
                            <?php if (!empty($horaire['details_lines']) && is_array($horaire['details_lines'])): ?>
                                <ul>
                                    <?php foreach ($horaire['details_lines'] as $ligneDetail): ?>
                                        <li><?= e((string) $ligneDetail) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </details>
    <?php endif; ?>
</section>
