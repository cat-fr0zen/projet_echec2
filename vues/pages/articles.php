<?php
$authData = $siteData['auth'];
$publishedArticles = $siteData['published_articles'];
$myArticles = $siteData['my_articles'];
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
            <p>Seuls les articles valides par un futur role administrateur sont destines a la publication publique.</p>
        </div>

        <?php if ($publishedArticles === []): ?>
            <div class="empty-state">
                <p class="card-tag">Aucune publication</p>
                <h3>Aucun article public pour le moment.</h3>
                <p>Le cadre editorial est pret. Les publications apparaîtront ici une fois moderees et validees.</p>
            </div>
        <?php else: ?>
            <div class="stack-list">
                <?php foreach ($publishedArticles as $article): ?>
                    <article class="schedule-item">
                        <p class="card-tag">Publie</p>
                        <h3><?= e($article['title']) ?></h3>
                        <p><?= e($article['excerpt']) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>

    <article class="panel panel-contrast">
        <div class="section-head section-head--compact">
            <p class="eyebrow">Soumission membre</p>
            <h2>Rediger et proposer un article.</h2>
            <p>Les membres connectes peuvent proposer un article. Il reste en attente de validation avant sa publication.</p>
        </div>

        <?php if ($authData['is_authenticated']): ?>
            <form method="post" action="<?= e(route_url('articles')) ?>" class="article-form">
                <input type="hidden" name="action" value="create_article">
                <input type="hidden" name="csrf_token" value="<?= e($siteData['csrf_token']) ?>">

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

                <button type="submit" class="button button-primary">Proposer l article</button>
            </form>
        <?php else: ?>
            <div class="empty-state empty-state--contrast">
                <p class="card-tag">Connexion requise</p>
                <h3>Connecte-toi pour proposer un article.</h3>
                <p>La redaction est reservee aux membres inscrits avec un compte email.</p>
                <button type="button" class="button button-primary" data-auth-open data-auth-tab="login">Connexion / inscription</button>
            </div>
        <?php endif; ?>
    </article>
</section>

<?php if ($authData['is_authenticated']): ?>
    <section class="section-block reveal reveal-4">
        <div class="section-head">
            <p class="eyebrow">Mes soumissions</p>
            <h2>Articles en attente ou deja enregistres.</h2>
            <p>Cette liste est visible cote membre pour suivre les articles proposes depuis le compte connecte.</p>
        </div>

        <?php if ($myArticles === []): ?>
            <div class="empty-state">
                <p class="card-tag">Aucune soumission</p>
                <h3>Tu n as pas encore propose d article.</h3>
                <p>Utilise le formulaire ci-dessus pour enregistrer ton premier contenu.</p>
            </div>
        <?php else: ?>
            <div class="card-grid card-grid--three">
                <?php foreach ($myArticles as $article): ?>
                    <article class="info-card">
                        <p class="card-tag"><?= e($article['status'] === 'approved' ? 'Publie' : 'En attente') ?></p>
                        <h3><?= e($article['title']) ?></h3>
                        <p><?= e($article['excerpt']) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>
