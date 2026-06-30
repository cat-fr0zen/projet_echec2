<?php
/**
 * Vue: Articles.
 *
 * Affiche:
 * - les articles publies avec recherche et lecture detaillee
 * - un editeur d'article par blocs pour les adherents et admins
 * - le suivi des soumissions du membre connecte
 */
use App\Models\Article;

$authData = $siteData['authentification'];
$publishedArticles = $siteData['published_articles'] ?? [];
$myArticles = $siteData['my_articles'] ?? [];
$canCreateArticle = (bool) ($authData['peut_publier_articles'] ?? false);
$defaultAuthor = (string) ($authData['display_name'] ?? '');
$todayLabel = (new DateTimeImmutable())->format('d/m/Y');

$renderArticleBlocks = static function (array $article): void {
    $blocks = $article['blocs'] ?? [];

    foreach ($blocks as $block) {
        $type = (string) ($block['type'] ?? '');

        if ($type === Article::TYPE_BLOC_SOUS_TITRE) {
            ?>
            <h3 class="published-article-subtitle"><?= e((string) ($block['texte'] ?? '')) ?></h3>
            <?php
            continue;
        }

        if ($type === Article::TYPE_BLOC_PARAGRAPHE) {
            ?>
            <p><?= nl2br(e((string) ($block['texte'] ?? ''))) ?></p>
            <?php
            continue;
        }

        if ($type === Article::TYPE_BLOC_IMAGE && ($block['chemin_public'] ?? '') !== '') {
            ?>
            <figure class="published-article-media">
                <img
                    src="<?= e(url_ressource((string) $block['chemin_public'])) ?>"
                    alt="<?= e((string) ($block['texte_alternatif'] ?? '')) ?>"
                    loading="lazy"
                >
                <?php if (($block['legende'] ?? '') !== ''): ?>
                    <figcaption><?= e((string) $block['legende']) ?></figcaption>
                <?php endif; ?>
            </figure>
            <?php
            continue;
        }

        if ($type === Article::TYPE_BLOC_VIDEO && ($block['chemin_public'] ?? '') !== '') {
            ?>
            <figure class="published-article-media">
                <video controls preload="metadata" aria-label="<?= e((string) ($block['texte_alternatif'] ?? 'Video de l article')) ?>">
                    <source src="<?= e(url_ressource((string) $block['chemin_public'])) ?>" type="<?= e((string) ($block['type_mime'] ?? 'video/mp4')) ?>">
                </video>
                <?php if (($block['legende'] ?? '') !== ''): ?>
                    <figcaption><?= e((string) $block['legende']) ?></figcaption>
                <?php endif; ?>
            </figure>
            <?php
        }
    }
};

$articleSearchText = static function (array $article): string {
    $parts = [
        (string) ($article['titre'] ?? ''),
        (string) ($article['auteur_affiche'] ?? $article['nom_auteur'] ?? ''),
        (string) ($article['resume'] ?? ''),
    ];

    foreach (($article['blocs'] ?? []) as $block) {
        $parts[] = (string) ($block['texte'] ?? '');
        $parts[] = (string) ($block['legende'] ?? '');
        $parts[] = (string) ($block['texte_alternatif'] ?? '');
    }

    return trim(implode(' ', array_filter($parts, static fn ($value): bool => trim((string) $value) !== '')));
};
?>

<section class="page-banner page-banner--with-actions reveal reveal-2">
    <div>
        <p class="eyebrow">Articles</p>
        <h1><?= e($pageData['title']) ?></h1>
        <p><?= e($pageData['intro']) ?></p>
    </div>

    <?php if ($canCreateArticle): ?>
        <button
            type="button"
            class="button button-primary article-create-button"
            data-article-editor-open
            aria-controls="article-editor-panel"
            aria-expanded="false"
        >
            Créer un article
        </button>
    <?php endif; ?>
</section>

<?php if ($canCreateArticle): ?>
    <section
        class="article-editor-shell reveal reveal-3"
        id="article-editor-panel"
        data-article-editor
        hidden
    >
        <div class="section-head section-head--compact">
            <p class="eyebrow">Éditeur</p>
            <h2>Composer un article.</h2>
        </div>

        <form
            method="post"
            action="<?= e(url_route('articles')) ?>"
            class="article-form article-editor-form"
            enctype="multipart/form-data"
            data-article-editor-form
        >
            <input type="hidden" name="action" value="create_article">
            <input type="hidden" name="_token" value="<?= e($siteData['jeton_csrf']) ?>">
            <input type="hidden" name="jeton_csrf" value="<?= e($siteData['jeton_csrf']) ?>">
            <input type="hidden" name="article_blocks_payload" data-article-blocks-payload value="">

            <div class="article-editor-grid">
                <label class="form-group">
                    <span>Titre</span>
                    <input
                        type="text"
                        name="title"
                        maxlength="150"
                        required
                        data-article-title
                        autocomplete="off"
                    >
                </label>

                <label class="form-group">
                    <span>Auteur affiché</span>
                    <input
                        type="text"
                        name="display_author"
                        maxlength="120"
                        required
                        value="<?= e($defaultAuthor) ?>"
                        data-article-author
                        autocomplete="name"
                    >
                </label>
            </div>

            <div class="article-editor-date" aria-live="polite">
                <span>Date de création</span>
                <strong data-article-date><?= e($todayLabel) ?></strong>
            </div>

            <div class="article-editor-toolbar" role="toolbar" aria-label="Ajouter un bloc a l'article">
                <button type="button" class="button button-secondary" data-add-article-block="sous_titre">Sous-titre</button>
                <button type="button" class="button button-secondary" data-add-article-block="paragraphe">Paragraphe</button>
                <button type="button" class="button button-secondary" data-add-article-block="image">Image / GIF</button>
                <button type="button" class="button button-secondary" data-add-article-block="video">Vidéo</button>
            </div>

            <div class="article-editor-workspace">
                <div class="article-block-list" data-article-block-list aria-label="Blocs de l'article"></div>

                <aside class="article-preview">
                    <p class="eyebrow">Aperçu</p>
                    <article class="published-article published-article--preview">
                        <h2 data-article-preview-title>Nouvel article</h2>
                        <div class="published-article-body" data-article-preview-body></div>
                        <footer class="published-article-footer">
                            <span data-article-preview-author><?= e($defaultAuthor !== '' ? $defaultAuthor : 'Auteur') ?></span>
                            <time datetime="<?= e((new DateTimeImmutable())->format('Y-m-d')) ?>" data-article-preview-date><?= e($todayLabel) ?></time>
                        </footer>
                    </article>
                </aside>
            </div>

            <p class="sr-only" data-article-editor-status aria-live="polite"></p>

            <div class="article-editor-actions">
                <button type="submit" class="button button-primary">Envoyer à la modération</button>
            </div>
        </form>
    </section>
<?php elseif (!$authData['is_authenticated']): ?>
    <section class="section-block reveal reveal-3">
        <div class="empty-state">
            <p class="card-tag">Connexion requise</p>
            <h3>Connecte-toi pour proposer un article.</h3>
            <p>Les visiteurs peuvent lire les articles publics, mais pas soumettre de contenu.</p>
            <button type="button" class="button button-primary" data-auth-open data-auth-tab="connexion">Connexion</button>
        </div>
    </section>
<?php else: ?>
    <section class="section-block reveal reveal-3">
        <div class="empty-state">
            <p class="card-tag"><?= e((string) ($authData['role_label'] ?? 'Compte')) ?></p>
            <h3>Ton compte peut consulter, mais pas publier.</h3>
            <p>Le dépôt d'articles est réservé aux adhérents du club et à l'administrateur.</p>
        </div>
    </section>
<?php endif; ?>

<section class="section-block reveal reveal-4">
    <div class="section-head">
        <p class="eyebrow">Publication publique</p>
        <h2>Articles visibles par tous.</h2>
        <p>Retrouve rapidement un sujet, puis ouvre la version complète seulement quand tu veux la lire.</p>
    </div>

    <?php if ($publishedArticles === []): ?>
        <div class="empty-state">
            <p class="card-tag">Aucune publication</p>
            <h3>Aucun article public pour le moment.</h3>
            <p>Le cadre éditorial est prêt. Les publications apparaîtront ici une fois modérées.</p>
        </div>
    <?php else: ?>
        <section class="article-search" data-article-search>
            <label class="article-search-field" for="article-search-input">
                <span class="eyebrow">Recherche rapide</span>
                <input
                    id="article-search-input"
                    type="search"
                    placeholder="Titre, auteur, mot-clé..."
                    autocomplete="off"
                    data-article-search-input
                >
            </label>
            <button type="button" class="button button-secondary article-search-reset" data-article-search-reset>Effacer</button>
            <p class="article-search-status" data-article-search-status aria-live="polite"></p>
        </section>

        <div class="published-article-list published-article-list--compact" data-article-search-list>
            <?php foreach ($publishedArticles as $index => $article): ?>
                <?php
                $articleTitle = (string) ($article['titre'] ?? 'Article');
                $articleAuthor = (string) ($article['auteur_affiche'] ?? $article['nom_auteur'] ?? 'Auteur');
                $articleDateLabel = (string) ($article['date_creation_libelle'] ?? '');
                $articleSearchPayload = $articleSearchText($article);
                ?>
                <article
                    class="published-article-card"
                    data-article-search-item
                    data-article-search-text="<?= e(mb_strtolower($articleSearchPayload)) ?>"
                >
                    <a
                        class="published-article-card-button"
                        href="<?= e('/articles/' . rawurlencode((string) ($article['identifiant'] ?? ''))) ?>"
                    >
                        <span class="published-article-card-meta">
                            <span class="card-tag"><?= e($articleAuthor) ?></span>
                            <?php if ($articleDateLabel !== ''): ?>
                                <span class="published-article-card-date"><?= e($articleDateLabel) ?></span>
                            <?php endif; ?>
                        </span>
                        <strong class="published-article-card-title"><?= e($articleTitle) ?></strong>
                        <span class="published-article-card-action">Lire l'article</span>
                    </a>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="empty-state" data-article-search-empty hidden>
            <p class="card-tag">Aucun résultat</p>
            <h3>Aucun article ne correspond à ta recherche.</h3>
            <p>Essaie un autre titre, un nom d'auteur ou un mot-clé plus simple.</p>
        </div>
    <?php endif; ?>
</section>

<?php if ($authData['is_authenticated']): ?>
    <section class="section-block reveal reveal-5">
        <div class="section-head">
            <p class="eyebrow">Mes soumissions</p>
            <h2>Suivi des articles de mon compte.</h2>
            <p>Tu retrouves ici tes articles, leur statut de modération et leur historique local.</p>
        </div>

        <?php if ($myArticles === []): ?>
            <div class="empty-state">
                <p class="card-tag">Aucune soumission</p>
                <h3>Tu n'as pas encore proposé d'article.</h3>
                <p>Quand tu enverras un article, il apparaîtra ici avec son statut.</p>
            </div>
        <?php else: ?>
            <div class="card-grid card-grid--three">
                <?php foreach ($myArticles as $article): ?>
                    <article class="info-card">
                        <p class="card-tag"><?= e((string) ($article['libelle_statut'] ?? 'En attente')) ?></p>
                        <h3><?= e((string) ($article['titre'] ?? 'Article')) ?></h3>
                        <p><?= e((string) ($article['resume'] ?? '')) ?></p>
                        <p class="card-subtitle">
                            <?= e((string) ($article['date_creation_libelle'] ?? '')) ?>
                        </p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>
