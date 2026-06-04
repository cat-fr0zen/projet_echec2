<?php
/**
 * Vue: Accueil.
 *
 * Page vitrine du club:
 * - bandeau d'accueil + liens d'acces
 * - casse-tete hebdomadaire
 * - blocs interchangeables administres via le constructeur
 *
 * Donnees attendues:
 * - $pageData: textes de la page
 * - $siteData: donnees globales (stats, horaires, etc.)
 */
$stats = $siteData['stats'];
$pieceCarousel = $siteData['piece_carousel'];
$authData = $siteData['authentification'];
$dammierPuzzle = $siteData['dammier_puzzle'] ?? [];
$dammierClassement = $siteData['dammier_classement'] ?? [];
$dammierPeutVoirClassement = (bool) ($siteData['dammier_peut_voir_classement'] ?? false);
$blocsAccueilActifs = is_array($siteData['constructeur_accueil_blocs_actifs'] ?? null) ? $siteData['constructeur_accueil_blocs_actifs'] : [];
$horairesClub = is_array($siteData['horaires_club'] ?? null) ? $siteData['horaires_club'] : [];
$resumeHorairesClub = is_array($siteData['resume_horaires_club'] ?? null) ? $siteData['resume_horaires_club'] : [];
$itemsHorairesClub = is_array($horairesClub['items'] ?? null) ? $horairesClub['items'] : [];
$libelleSaisonHoraires = (string) ($horairesClub['season_label'] ?? 'Horaires du club');
$messageJourFerie = (string) ($horairesClub['holiday_notice'] ?? '');
$libelleDifficulteDammier = (string) ($dammierPuzzle['dammier_difficulty_label'] ?? 'Medium');
$nombreCoupsBlancsDammier = (int) ($dammierPuzzle['dammier_white_moves_count'] ?? count((array) ($dammierPuzzle['dammier_solution'] ?? [])));
$dammierPayload = [
    'dammier_puzzle' => $dammierPuzzle,
    'dammier_classement' => $dammierClassement,
    'dammier_submit_url' => url_route('accueil'),
    'dammier_is_authenticated' => (bool) ($authData['is_authenticated'] ?? false),
];
$blocsAccueilMobilesActifs = array_values(
    array_filter(
        $blocsAccueilActifs,
        static fn (array $bloc): bool => !in_array((string) ($bloc['code_bloc'] ?? ''), ['bandeau_accueil', 'casse_tete_hebdomadaire'], true)
    )
);
?>

<section class="hero-grid">
    <article class="panel hero-copy reveal reveal-2" data-accueil-slot="bandeau_accueil">
        <p class="eyebrow">Site officiel</p>
        <h1><?= e((string) ($pageData['titre_bandeau_accueil'] ?? $pageData['hero_title'] ?? '')) ?></h1>
        <p class="lead"><?= e((string) ($pageData['texte_bandeau_accueil'] ?? $pageData['hero_text'] ?? '')) ?></p>

        <div class="button-row">
            <a
                class="button button-primary"
                href="https://www.helloasso.com/associations/les-cavaliers-d-herouville"
                target="_blank"
                rel="noopener noreferrer external"
                referrerpolicy="no-referrer"
                aria-label="Adherer au club sur HelloAsso (nouvel onglet)"
            >
                Adherer
            </a>
            <a class="button button-secondary" href="#legal-hub">Voir le cadre legal</a>
            <?php if ($authData['is_authenticated']): ?>
                <a class="button button-secondary" href="<?= e(url_route('profil')) ?>">Voir mon profil</a>
            <?php endif; ?>
        </div>

        <p class="quick-note"><?= e((string) ($pageData['note_bandeau_accueil'] ?? $pageData['hero_note'] ?? '')) ?></p>

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
                                    <span class="schedule-exception-badge">Jour ferie</span>
                                <?php endif; ?>
                            </dt>
                            <dd><?= e((string) ($horaireResume['times'] ?? '')) ?></dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
                <?php if ($messageJourFerie !== ''): ?>
                    <p class="home-schedule-holiday"><strong>Jour ferie:</strong> <?= e($messageJourFerie) ?></p>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </article>

    <aside class="panel dammier_panel reveal reveal-3" data-accueil-slot="casse_tete_hebdomadaire">
        <div
            class="dammier_widget"
            data-dammier-root
            data-dammier-is-authenticated="<?= ($authData['is_authenticated'] ?? false) ? 'true' : 'false' ?>"
            data-dammier-submit-url="<?= e(url_route('accueil')) ?>"
            data-dammier-csrf="<?= e((string) ($siteData['jeton_csrf'] ?? '')) ?>"
        >
            <div class="dammier_header">
                <div>
                    <p class="eyebrow">Casse-tete hebdomadaire</p>
                    <h2><?= e((string) ($dammierPuzzle['dammier_title'] ?? 'Puzzle hebdomadaire')) ?></h2>
                    <p class="card-tag">Difficulte: <?= e($libelleDifficulteDammier) ?> · <?= e((string) $nombreCoupsBlancsDammier) ?> coups blancs</p>
                </div>
            </div>

            <p class="dammier_intro"><?= e((string) ($dammierPuzzle['dammier_description'] ?? '')) ?></p>
            <p class="dammier_hint"><?= e((string) ($dammierPuzzle['dammier_instruction'] ?? '')) ?></p>

            <div class="dammier_layout">
                <div class="dammier_board_panel">
                    <p id="dammier-board-help" class="sr-only">
                        Utilise Tab ou les fleches pour parcourir le damier. Selectionne d'abord une piece de ton camp,
                        puis sa case d'arrivee pour proposer le coup.
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
                    <p class="dammier_prompt" data-dammier-prompt>Clique sur une piece, puis sur sa case d'arrivee.</p>
                    <div class="dammier_status">
                        <span class="dammier_status_chip" data-dammier-selection role="status" aria-live="polite">Aucune piece selectionnee.</span>
                    </div>
                    <p id="dammier-feedback" class="dammier_feedback" data-dammier-feedback role="status" aria-live="polite">Le score compte le nombre total de tentatives jusqu'a la resolution.</p>
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
                                        <p class="dammier_ranking_empty" data-dammier-ranking-empty>Aucun score enregistre cette semaine.</p>
                                    <?php else: ?>
                                        <p class="dammier_ranking_empty" data-dammier-ranking-empty hidden>Aucun score enregistre cette semaine.</p>
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

<?php foreach ($blocsAccueilMobilesActifs as $blocAccueil): ?>
    <?php
    $codeBlocAccueil = (string) ($blocAccueil['code_bloc'] ?? '');
    $vueBlocAccueil = resource_path('views/pages/accueil/blocs/' . $codeBlocAccueil . '.blade.php');
    ?>
    <?php if (is_file($vueBlocAccueil)): ?>
        <?php require $vueBlocAccueil; ?>
    <?php endif; ?>
<?php endforeach; ?>
