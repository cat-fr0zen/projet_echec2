<?php
$authData = $siteData['authentification'];
$publishedArticles = $siteData['published_articles'] ?? [];
$myArticles = $siteData['my_articles'] ?? [];
?>

<section class="page-banner reveal reveal-2">
    <p class="eyebrow">Articles</p>
    <h1><?= e($pageData['title']) ?></h1>
    <p><?= e($pageData['intro']) ?></p>
</section>

<section class="split-grid reveal reveal-3">
    <article class="panel">
        <div class="section-head section-head--compact">
            <p class="eyebrow">Publication publique</p>
            <h2>Articles visibles par tous.</h2>
            <p>Les articles publies apparaissent ici apres validation par l administrateur du club.</p>
        </div>

        <?php if ($publishedArticles === []): ?>
            <div class="empty-state">
                <p class="card-tag">Aucune publication</p>
                <h3>Aucun article public pour le moment.</h3>
                <p>Le cadre editorial est pret. Les publications apparaitront ici une fois moderees.</p>
            </div>
        <?php else: ?>
            <div class="stack-list">
                <?php foreach ($publishedArticles as $article): ?>
                    <article class="schedule-item">
                        <p class="card-tag"><?= e((string) ($article['libelle_statut'] ?? 'Publie')) ?></p>
                        <h3><?= e((string) ($article['titre'] ?? 'Article')) ?></h3>
                        <p><?= e((string) ($article['resume'] ?? '')) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>

    <article class="panel panel-contrast">
        <div class="section-head section-head--compact">
            <p class="eyebrow">Soumission membre</p>
            <h2>Proposer un article.</h2>
            <p>Seuls les adherents du club peuvent soumettre un article a la moderation.</p>
        </div>

        <?php if (!$authData['is_authenticated']): ?>
            <div class="empty-state empty-state--contrast">
                <p class="card-tag">Connexion requise</p>
                <h3>Connecte-toi pour acceder a l espace membre.</h3>
                <p>Les visiteurs peuvent lire les articles publics, mais pas soumettre de contenu.</p>
                <button type="button" class="button button-primary" data-auth-open data-auth-tab="connexion">Connexion</button>
            </div>
        <?php elseif (!($authData['peut_publier_articles'] ?? false)): ?>
            <div class="empty-state empty-state--contrast">
                <p class="card-tag"><?= e((string) ($authData['role_label'] ?? 'Compte')) ?></p>
                <h3>Ton compte peut consulter, mais pas publier.</h3>
                <p>Le depot d articles est reserve aux adherents du club et a l administrateur.</p>
            </div>
        <?php else: ?>
            <form method="post" action="<?= e(url_route('articles')) ?>" class="article-form">
                <input type="hidden" name="action" value="create_article">
                <input type="hidden" name="jeton_csrf" value="<?= e($siteData['jeton_csrf']) ?>">

                <label class="form-group">
                    <span>Titre</span>
                    <input type="text" name="title" maxlength="150" required>
                </label>

                <label class="form-group">
                    <span>Resume</span>
                    <textarea name="excerpt" rows="3" maxlength="280" required></textarea>
                </label>

                <label class="form-group">
                    <span>Contenu</span>
                    <textarea name="content" rows="8" required></textarea>
                </label>

                <button type="submit" class="button button-primary">Envoyer a la moderation</button>
            </form>
        <?php endif; ?>
    </article>
</section>

<?php if ($authData['is_authenticated']): ?>
    <section class="section-block reveal reveal-4">
        <div class="section-head">
            <p class="eyebrow">Mes soumissions</p>
            <h2>Suivi des articles de mon compte.</h2>
            <p>Tu retrouves ici tes articles, leur statut de moderation et leur historique local.</p>
        </div>

        <?php if ($myArticles === []): ?>
            <div class="empty-state">
                <p class="card-tag">Aucune soumission</p>
                <h3>Tu n as pas encore propose d article.</h3>
                <p>Quand tu enverras un article, il apparaitra ici avec son statut.</p>
            </div>
        <?php else: ?>
            <div class="card-grid card-grid--three">
                <?php foreach ($myArticles as $article): ?>
                    <article class="info-card">
                        <p class="card-tag"><?= e((string) ($article['libelle_statut'] ?? 'En attente')) ?></p>
                        <h3><?= e((string) ($article['titre'] ?? 'Article')) ?></h3>
                        <p><?= e((string) ($article['resume'] ?? '')) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>
