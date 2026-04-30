<?php
$cartesBoutique = $donneesSite['cartes_boutique'] ?? [];
$authData = $siteData['authentification'];
$memberOrders = $siteData['member_orders'] ?? [];
?>

<section class="page-banner reveal reveal-2">
    <p class="eyebrow">Merch</p>
    <h1><?= e($donneesPage['titre']) ?></h1>
    <p><?= e($donneesPage['intro']) ?></p>
</section>

<section class="split-grid reveal reveal-3">
    <article class="panel">
        <div class="section-head section-head--compact">
            <p class="eyebrow">Catalogue</p>
            <h2>Commander depuis l espace membre.</h2>
            <p>Les visiteurs ne voient pas cette page. Les comptes connectes et les adherents peuvent enregistrer une commande locale.</p>
        </div>

        <div class="card-grid card-grid--three">
            <?php foreach ($cartesBoutique as $carteBoutique): ?>
                <article class="info-card merch-card">
                    <p class="card-tag"><?= e((string) ($carteBoutique['type'] ?? 'Produit')) ?></p>
                    <h3><?= e((string) ($carteBoutique['titre'] ?? 'Produit')) ?></h3>
                    <p><?= e((string) ($carteBoutique['texte'] ?? '')) ?></p>
                    <p class="status-pill"><?= e((string) ($carteBoutique['statut'] ?? 'Bientot')) ?></p>

                    <form method="post" action="<?= e(url_route('boutique')) ?>" class="article-form">
                        <input type="hidden" name="action" value="order_product">
                        <input type="hidden" name="jeton_csrf" value="<?= e($siteData['jeton_csrf']) ?>">
                        <input type="hidden" name="produit" value="<?= e((string) ($carteBoutique['titre'] ?? 'Produit')) ?>">
                        <input type="hidden" name="categorie" value="<?= e((string) ($carteBoutique['type'] ?? 'Produit')) ?>">
                        <button type="submit" class="button button-primary">Commander</button>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
    </article>

    <article class="panel panel-contrast">
        <div class="section-head section-head--compact">
            <p class="eyebrow">Mes commandes</p>
            <h2>Suivre le statut du merchandising.</h2>
            <p>Chaque commande enregistree apparait ici avec son statut actuel.</p>
        </div>

        <?php if ($memberOrders === []): ?>
            <div class="empty-state empty-state--contrast">
                <p class="card-tag">Aucune commande</p>
                <h3>Ton compte n a encore rien commande.</h3>
                <p>Quand tu reserveras un produit, son suivi apparaitra ici.</p>
            </div>
        <?php else: ?>
            <div class="stack-list">
                <?php foreach ($memberOrders as $commande): ?>
                    <article class="schedule-item">
                        <p class="card-tag"><?= e((string) ($commande['libelle_statut'] ?? 'En attente')) ?></p>
                        <h3><?= e((string) ($commande['produit'] ?? 'Commande')) ?></h3>
                        <p><?= e((string) ($commande['categorie'] ?? 'Produit')) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>
</section>
