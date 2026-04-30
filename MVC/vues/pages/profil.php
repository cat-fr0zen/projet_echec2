<?php
$authData = $siteData['authentification'];
$chessData = $siteData['chess_com'] ?? ['status' => 'missing'];
?>

<section class="page-banner reveal reveal-2">
    <p class="eyebrow">Profil</p>
    <h1><?= e($pageData['title']) ?></h1>
    <p><?= e($pageData['intro']) ?></p>
</section>

<?php if (!$authData['is_authenticated']): ?>
    <section class="section-block reveal reveal-3">
        <div class="empty-state">
            <p class="card-tag">Connexion requise</p>
            <h2>Connecte-toi pour accéder à ton profil.</h2>
            <p>Le profil membre, la description personnelle, les préférences du compte et la liaison Chess.com sont accessibles après connexion.</p>
            <button type="button" class="button button-primary" data-auth-open data-auth-tab="login">Connexion</button>
        </div>
    </section>
<?php else: ?>
    <?php
    $user = $authData['user'];
    $playerData = is_array($chessData['player'] ?? null) ? $chessData['player'] : [];
    $playerUsername = (string) ($playerData['username'] ?? '');
    $playerInitials = $playerUsername !== '' ? mb_strtoupper(mb_substr($playerUsername, 0, 2)) : 'CC';
    ?>

    <section class="split-grid reveal reveal-3">
        <article class="panel">
            <div class="section-head section-head--compact">
                <p class="eyebrow">Identité membre</p>
                <h2>Modifier les informations du profil.</h2>
                <p>La description de profil et la liaison Chess.com restent éditables à tout moment depuis cette page.</p>
            </div>

            <form method="post" action="<?= e(url_route('profil')) ?>" class="article-form">
                <input type="hidden" name="action" value="update_profile">
                <input type="hidden" name="jeton_csrf" value="<?= e($siteData['jeton_csrf']) ?>">

                <div class="auth-grid">
                    <label class="form-group">
                        <span>Nom</span>
                        <input type="text" name="last_name" required maxlength="100" value="<?= e((string) ($user['last_name'] ?? '')) ?>">
                    </label>
                    <label class="form-group">
                        <span>Prénom</span>
                        <input type="text" name="first_name" required maxlength="100" value="<?= e((string) ($user['first_name'] ?? '')) ?>">
                    </label>
                </div>

                <label class="form-group">
                    <span>Date de naissance facultative</span>
                    <input type="date" name="birth_date" value="<?= e((string) ($user['birth_date'] ?? '')) ?>">
                </label>

                <label class="form-group">
                    <span>Email de connexion</span>
                    <input type="email" value="<?= e((string) ($user['email'] ?? '')) ?>" disabled>
                </label>

                <label class="form-group">
                    <span>Pseudo Chess.com facultatif</span>
                    <input
                        type="text"
                        name="chess_username"
                        maxlength="50"
                        value="<?= e((string) ($user['chess_username'] ?? '')) ?>"
                        placeholder="ex. hikaru"
                        autocomplete="off"
                    >
                </label>
                <p class="form-helper">Renseigne un pseudo Chess.com pour afficher uniquement les statistiques publiques du compte dans ton profil.</p>

                <label class="form-group">
                    <span>Description du profil</span>
                    <textarea name="profile_description" rows="7" maxlength="1200"><?= e((string) ($user['profile_description'] ?? '')) ?></textarea>
                </label>

                <button type="submit" class="button button-primary">Mettre à jour mon profil</button>
            </form>
        </article>

        <article class="panel panel-contrast">
            <div class="section-head section-head--compact">
                <p class="eyebrow">Compte</p>
                <h2>Informations utiles sur la session.</h2>
                <p>Le compte repose sur une session PHP, un mot de passe hashé et une lecture purement publique des statistiques Chess.com.</p>
            </div>

            <p class="status-pill chess-link-status">
                <?php if (($chessData['status'] ?? 'missing') === 'linked'): ?>
                    Liaison Chess.com active
                <?php elseif (($chessData['status'] ?? 'missing') === 'error'): ?>
                    Liaison Chess.com à vérifier
                <?php else: ?>
                    Liaison Chess.com non renseignée
                <?php endif; ?>
            </p>

            <ul class="bullet-list">
                <li>La connexion du site se fait avec votre email et votre mot de passe.</li>
                <li>La date de naissance reste facultative.</li>
                <li>Le pseudo Chess.com, s'il est ajouté, sert seulement à lire des données publiques.</li>
                <li>Le thème clair ou sombre reste mémorisé via un cookie de préférence.</li>
            </ul>
        </article>
    </section>

    <section class="section-block reveal reveal-4">
        <div class="section-head">
            <p class="eyebrow">Chess.com</p>
            <h2>Statistiques publiques liées au profil.</h2>
            <p>La page peut afficher les données publiques d'un compte Chess.com sans connexion OAuth et sans accès aux informations privées.</p>
        </div>

        <?php if (($chessData['status'] ?? 'missing') === 'missing'): ?>
            <div class="empty-state">
                <p class="card-tag">Liaison optionnelle</p>
                <h3>Ajoute un pseudo Chess.com pour voir les stats publiques.</h3>
                <p>Une fois le pseudo enregistré dans ton profil, le site pourra afficher les notes Rapid, Blitz, Bullet et d'autres données publiques du compte.</p>
            </div>
        <?php elseif (($chessData['status'] ?? '') === 'error'): ?>
            <div class="empty-state">
                <p class="card-tag">Synchronisation indisponible</p>
                <h3>Les données publiques Chess.com ne sont pas disponibles pour le moment.</h3>
                <p><?= e((string) ($chessData['message'] ?? "Une erreur temporaire s'est produite.")) ?></p>

                <?php if (($chessData['profile_url'] ?? '') !== ''): ?>
                    <a
                        class="button button-secondary chess-external-link"
                        href="<?= e((string) $chessData['profile_url']) ?>"
                        target="_blank"
                        rel="noreferrer noopener"
                    >
                        Ouvrir le profil Chess.com
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="chess-shell">
                <article class="chess-player-card">
                    <div class="chess-player-media">
                        <?php if (($playerData['avatar'] ?? '') !== ''): ?>
                            <img
                                class="chess-player-avatar"
                                src="<?= e((string) $playerData['avatar']) ?>"
                                alt="Avatar Chess.com de <?= e($playerData['display_name'] ?? $playerUsername) ?>"
                                loading="lazy"
                            >
                        <?php else: ?>
                            <div class="chess-player-fallback" aria-hidden="true"><?= e($playerInitials) ?></div>
                        <?php endif; ?>

                        <div class="chess-player-meta">
                            <p class="card-tag">Profil public</p>
                            <h3>
                                <?php if (($playerData['title'] ?? '') !== ''): ?>
                                    <span class="chess-player-title"><?= e((string) $playerData['title']) ?></span>
                                <?php endif; ?>
                                <?= e((string) ($playerData['display_name'] ?? $playerUsername)) ?>
                            </h3>
                            <p class="card-subtitle">@<?= e($playerUsername) ?></p>
                        </div>
                    </div>

                    <ul class="chess-player-facts">
                        <?php if (($playerData['country'] ?? '') !== ''): ?>
                            <li><strong>Pays :</strong> <?= e((string) $playerData['country']) ?></li>
                        <?php endif; ?>
                        <?php if (($playerData['followers'] ?? null) !== null): ?>
                            <li><strong>Followers :</strong> <?= e(number_format((int) $playerData['followers'], 0, ',', ' ')) ?></li>
                        <?php endif; ?>
                        <?php if (($playerData['fide'] ?? null) !== null): ?>
                            <li><strong>Classement FIDE :</strong> <?= e((string) $playerData['fide']) ?></li>
                        <?php endif; ?>
                        <?php if (($playerData['last_online_label'] ?? '') !== ''): ?>
                            <li><?= e((string) $playerData['last_online_label']) ?></li>
                        <?php endif; ?>
                    </ul>

                    <p class="quick-note chess-sync-note">
                        <?= e((string) ($chessData['stats_note'] ?? '')) ?>
                        <?php if (($chessData['fetched_at_label'] ?? '') !== ''): ?>
                            <br><?= e((string) $chessData['fetched_at_label']) ?>
                        <?php endif; ?>
                    </p>

                    <a
                        class="button button-secondary chess-external-link"
                        href="<?= e((string) ($chessData['profile_url'] ?? 'https://www.chess.com/')) ?>"
                        target="_blank"
                        rel="noreferrer noopener"
                    >
                        Voir le profil public Chess.com
                    </a>
                </article>

                <?php if (($chessData['ratings'] ?? []) === []): ?>
                    <div class="empty-state">
                        <p class="card-tag">Données partielles</p>
                        <h3>Le profil public a bien été trouvé.</h3>
                        <p>Chess.com ne renvoie simplement pas encore de statistiques détaillées exploitables pour les formats suivis ici.</p>
                    </div>
                <?php else: ?>
                    <div class="card-grid chess-rating-grid">
                        <?php foreach (($chessData['ratings'] ?? []) as $rating): ?>
                            <article class="info-card chess-rating-card">
                                <p class="card-tag"><?= e((string) ($rating['label'] ?? 'Stat')) ?></p>
                                <span class="metric-value chess-rating-value">
                                    <?= e((string) (($rating['rating'] ?? null) !== null ? $rating['rating'] : '—')) ?>
                                </span>
                                <h3><?= e((string) ($rating['label'] ?? 'Statistique')) ?></h3>

                                <ul class="chess-rating-list">
                                    <?php if (($rating['games'] ?? null) !== null): ?>
                                        <li><strong>Parties :</strong> <?= e(number_format((int) $rating['games'], 0, ',', ' ')) ?></li>
                                    <?php endif; ?>
                                    <?php if (($rating['wins'] ?? null) !== null): ?>
                                        <li><strong>Victoires :</strong> <?= e(number_format((int) $rating['wins'], 0, ',', ' ')) ?></li>
                                    <?php endif; ?>
                                    <?php if (($rating['losses'] ?? null) !== null): ?>
                                        <li><strong>Défaites :</strong> <?= e(number_format((int) $rating['losses'], 0, ',', ' ')) ?></li>
                                    <?php endif; ?>
                                    <?php if (($rating['draws'] ?? null) !== null): ?>
                                        <li><strong>Nulles :</strong> <?= e(number_format((int) $rating['draws'], 0, ',', ' ')) ?></li>
                                    <?php endif; ?>
                                    <?php if (($rating['best_rating'] ?? null) !== null): ?>
                                        <li><strong>Meilleure perf. :</strong> <?= e((string) $rating['best_rating']) ?></li>
                                    <?php endif; ?>
                                    <?php if (($rating['best_date_label'] ?? '') !== ''): ?>
                                        <li><strong>Date :</strong> <?= e((string) $rating['best_date_label']) ?></li>
                                    <?php endif; ?>
                                </ul>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>


