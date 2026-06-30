<?php
/**
 * Vue: Detail d'article.
 */
use App\Models\Article;

$article = is_array($pageData['article_detail'] ?? null) ? $pageData['article_detail'] : [];

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
?>

<section class="page-banner reveal reveal-2">
    <p class="eyebrow">Article complet</p>
    <h1><?= e((string) ($article['titre'] ?? 'Article')) ?></h1>
    <p><?= e((string) ($article['resume'] ?? '')) ?></p>
    <p><a class="button button-secondary" href="<?= e(url_route('articles')) ?>">Retour aux articles</a></p>
</section>

<section class="section-block reveal reveal-3">
    <article class="published-article">
        <div class="published-article-body">
            <?php $renderArticleBlocks($article); ?>
        </div>
        <footer class="published-article-footer">
            <span><?= e((string) ($article['auteur_affiche'] ?? $article['nom_auteur'] ?? 'Auteur')) ?></span>
            <time datetime="<?= e(mb_substr((string) ($article['cree_le'] ?? ''), 0, 10)) ?>">
                <?= e((string) ($article['date_creation_libelle'] ?? '')) ?>
            </time>
        </footer>
    </article>
</section>
