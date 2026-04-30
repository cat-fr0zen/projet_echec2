<?php
$allUsers = $siteData['all_users'] ?? [];
$allArticles = $siteData['all_articles'] ?? [];
$allMedia = $siteData['all_media'] ?? [];
$allOrders = $siteData['all_orders'] ?? [];
?>

<section class="page-banner reveal reveal-2">
    <p class="eyebrow">Administration</p>
    <h1><?= e($pageData['title']) ?></h1>
    <p><?= e($pageData['intro']) ?></p>
</section>

<section class="section-block reveal reveal-3">
    <div class="section-head">
        <p class="eyebrow">Tableau de bord</p>
        <h2>Piloter les comptes, les articles et les medias.</h2>
        <p>Cette page est reservee au president administrateur. Toutes les decisions se prennent ici sans quitter le site.</p>
    </div>

    <div class="admin-summary-grid">
        <article class="info-card">
            <p class="card-tag">Utilisateurs</p>
            <span class="metric-value"><?= e((string) count($allUsers)) ?></span>
            <h3>Comptes suivis</h3>
        </article>
        <article class="info-card">
            <p class="card-tag">Articles</p>
            <span class="metric-value"><?= e((string) count($allArticles)) ?></span>
            <h3>Articles en base</h3>
        </article>
        <article class="info-card">
            <p class="card-tag">Medias</p>
            <span class="metric-value"><?= e((string) count($allMedia)) ?></span>
            <h3>Medias deposes</h3>
        </article>
        <article class="info-card">
            <p class="card-tag">Commandes</p>
            <span class="metric-value"><?= e((string) count($allOrders)) ?></span>
            <h3>Merch commande</h3>
        </article>
    </div>
</section>

<section class="section-block reveal reveal-4">
    <div class="section-head">
        <p class="eyebrow">Comptes</p>
        <h2>Gerer les roles et les statuts.</h2>
        <p>Le role determine les droits, et le statut permet de suspendre un acces si besoin.</p>
    </div>

    <div class="admin-list">
        <?php foreach ($allUsers as $user): ?>
            <article class="info-card admin-card">
                <p class="card-tag"><?= e((string) ($user['role'] ?? 'connecte')) ?></p>
                <h3><?= e(trim((string) ($user['prenom'] ?? '') . ' ' . (string) ($user['nom'] ?? ''))) ?></h3>
                <p><?= e((string) ($user['courriel'] ?? '')) ?></p>

                <form method="post" action="<?= e(url_route('admin')) ?>" class="admin-form">
                    <input type="hidden" name="action" value="update_user_access">
                    <input type="hidden" name="jeton_csrf" value="<?= e($siteData['jeton_csrf']) ?>">
                    <input type="hidden" name="identifiant_utilisateur_cible" value="<?= e((string) ($user['identifiant'] ?? '')) ?>">

                    <label class="form-group">
                        <span>Role</span>
                        <select name="role_utilisateur">
                            <option value="connecte"<?= ($user['role'] ?? '') === 'connecte' ? ' selected' : '' ?>>Connecte</option>
                            <option value="adherent"<?= ($user['role'] ?? '') === 'adherent' ? ' selected' : '' ?>>Adherent</option>
                            <option value="admin"<?= ($user['role'] ?? '') === 'admin' ? ' selected' : '' ?>>Admin</option>
                        </select>
                    </label>

                    <label class="form-group">
                        <span>Statut compte</span>
                        <select name="statut_compte_utilisateur">
                            <option value="actif"<?= ($user['statut_compte'] ?? '') === 'actif' ? ' selected' : '' ?>>Actif</option>
                            <option value="suspendu"<?= ($user['statut_compte'] ?? '') === 'suspendu' ? ' selected' : '' ?>>Suspendu</option>
                        </select>
                    </label>

                    <label class="form-group">
                        <span>Adhesion</span>
                        <select name="statut_adhesion_utilisateur">
                            <option value="aucune"<?= ($user['statut_adhesion'] ?? '') === 'aucune' ? ' selected' : '' ?>>Non adherent</option>
                            <option value="active"<?= ($user['statut_adhesion'] ?? '') === 'active' ? ' selected' : '' ?>>Adherent actif</option>
                        </select>
                    </label>

                    <button type="submit" class="button button-primary">Mettre a jour</button>
                </form>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="split-grid reveal reveal-5">
    <article class="panel">
        <div class="section-head section-head--compact">
            <p class="eyebrow">Moderation articles</p>
            <h2>Valider ou refuser les articles.</h2>
            <p>Chaque article soumis peut rester en attente, etre publie ou etre refuse.</p>
        </div>

        <div class="admin-list">
            <?php if ($allArticles === []): ?>
                <div class="empty-state">
                    <p class="card-tag">Aucun article</p>
                    <h3>Aucune soumission pour le moment.</h3>
                </div>
            <?php else: ?>
                <?php foreach ($allArticles as $article): ?>
                    <article class="info-card admin-card">
                        <p class="card-tag"><?= e((string) ($article['libelle_statut'] ?? 'En attente')) ?></p>
                        <h3><?= e((string) ($article['titre'] ?? 'Article')) ?></h3>
                        <p><?= e((string) ($article['resume'] ?? '')) ?></p>
                        <p class="card-subtitle">Auteur: <?= e((string) ($article['nom_auteur'] ?? '')) ?></p>

                        <form method="post" action="<?= e(url_route('admin')) ?>" class="admin-form admin-inline-form">
                            <input type="hidden" name="action" value="review_article">
                            <input type="hidden" name="jeton_csrf" value="<?= e($siteData['jeton_csrf']) ?>">
                            <input type="hidden" name="identifiant_article" value="<?= e((string) ($article['identifiant'] ?? '')) ?>">

                            <button type="submit" name="statut_article" value="publie" class="button button-primary">Publier</button>
                            <button type="submit" name="statut_article" value="refuse" class="button button-secondary">Refuser</button>
                            <button type="submit" name="statut_article" value="en_attente_validation" class="button button-secondary">Remettre en attente</button>
                        </form>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </article>

    <article class="panel panel-contrast">
        <div class="section-head section-head--compact">
            <p class="eyebrow">Moderation medias</p>
            <h2>Valider ou refuser les photos et videos.</h2>
            <p>Le president choisit ici ce qui devient visible publiquement sur la mediatheque.</p>
        </div>

        <div class="admin-list">
            <?php if ($allMedia === []): ?>
                <div class="empty-state empty-state--contrast">
                    <p class="card-tag">Aucun media</p>
                    <h3>Aucun depot de media pour le moment.</h3>
                </div>
            <?php else: ?>
                <?php foreach ($allMedia as $media): ?>
                    <article class="info-card admin-card admin-card--contrast">
                        <p class="card-tag"><?= e((string) ($media['libelle_statut'] ?? 'En attente')) ?></p>
                        <h3><?= e((string) ($media['titre'] ?? 'Media')) ?></h3>
                        <p><?= e((string) ($media['description'] ?? '')) ?></p>
                        <p class="card-subtitle">Auteur: <?= e((string) ($media['nom_auteur'] ?? '')) ?></p>

                        <?php if (($media['type_media'] ?? '') === 'video'): ?>
                            <video class="media-preview media-preview--small" controls preload="metadata">
                                <source src="<?= e((string) ($media['chemin_public'] ?? '')) ?>" type="<?= e((string) ($media['type_mime'] ?? 'video/mp4')) ?>">
                            </video>
                        <?php else: ?>
                            <img
                                class="media-preview media-preview--small"
                                src="<?= e((string) ($media['chemin_public'] ?? '')) ?>"
                                alt="<?= e((string) ($media['titre'] ?? 'Media')) ?>"
                                loading="lazy"
                            >
                        <?php endif; ?>

                        <form method="post" action="<?= e(url_route('admin')) ?>" class="admin-form admin-inline-form">
                            <input type="hidden" name="action" value="review_media">
                            <input type="hidden" name="jeton_csrf" value="<?= e($siteData['jeton_csrf']) ?>">
                            <input type="hidden" name="identifiant_media" value="<?= e((string) ($media['identifiant'] ?? '')) ?>">

                            <button type="submit" name="statut_media" value="publie" class="button button-primary">Publier</button>
                            <button type="submit" name="statut_media" value="refuse" class="button button-secondary">Refuser</button>
                            <button type="submit" name="statut_media" value="en_attente_validation" class="button button-secondary">Remettre en attente</button>
                        </form>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </article>
</section>

<section class="section-block reveal reveal-6">
    <div class="section-head">
        <p class="eyebrow">Commandes</p>
        <h2>Suivre le merchandising.</h2>
        <p>Le president peut mettre a jour le statut des commandes creees depuis la boutique.</p>
    </div>

    <div class="admin-list">
        <?php if ($allOrders === []): ?>
            <div class="empty-state">
                <p class="card-tag">Aucune commande</p>
                <h3>Aucune commande pour le moment.</h3>
            </div>
        <?php else: ?>
            <?php foreach ($allOrders as $commande): ?>
                <article class="info-card admin-card">
                    <p class="card-tag"><?= e((string) ($commande['libelle_statut'] ?? 'En attente')) ?></p>
                    <h3><?= e((string) ($commande['produit'] ?? 'Commande')) ?></h3>
                    <p><?= e((string) ($commande['categorie'] ?? 'Produit')) ?></p>
                    <p class="card-subtitle">Membre: <?= e((string) ($commande['nom_utilisateur'] ?? '')) ?></p>

                    <form method="post" action="<?= e(url_route('admin')) ?>" class="admin-form admin-inline-form">
                        <input type="hidden" name="action" value="update_order_status">
                        <input type="hidden" name="jeton_csrf" value="<?= e($siteData['jeton_csrf']) ?>">
                        <input type="hidden" name="identifiant_commande" value="<?= e((string) ($commande['identifiant'] ?? '')) ?>">

                        <button type="submit" name="statut_commande" value="en_attente" class="button button-secondary">En attente</button>
                        <button type="submit" name="statut_commande" value="validee" class="button button-primary">Valider</button>
                        <button type="submit" name="statut_commande" value="annulee" class="button button-secondary">Annuler</button>
                    </form>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
