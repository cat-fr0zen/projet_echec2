<?php
/**
 * Vue: Boutique.
 *
 * Mini-shop du club avec filtres, tri, adhesion et suivi local des demandes.
 */
$catalogueBoutique = $donneesSite['cartes_boutique'] ?? [];
$authData = $siteData['authentification'];
$memberOrders = $siteData['member_orders'] ?? [];
$membre = is_array($authData['user'] ?? null) ? $authData['user'] : [];
$recherche = trim((string) ($_GET['q'] ?? ''));
$categorieActive = trim((string) ($_GET['categorie'] ?? ''));
$publicActif = trim((string) ($_GET['public'] ?? ''));
$triActif = trim((string) ($_GET['tri'] ?? 'recommandes'));
$enStockSeulement = (string) ($_GET['en_stock'] ?? '') === '1';
$adhesionSeulement = (string) ($_GET['adhesion_only'] ?? '') === '1';
$prixMaxCatalogue = 0;

foreach ($catalogueBoutique as $produitBoutique) {
    $prixMaxCatalogue = max($prixMaxCatalogue, (int) ($produitBoutique['prix_euros'] ?? 0));
}

$prixMaxCatalogue = max($prixMaxCatalogue, 100);
$prixMaxActif = (int) ($_GET['prix_max'] ?? $prixMaxCatalogue);
$prixMaxActif = max(0, min($prixMaxActif, $prixMaxCatalogue));

$categoriesDisponibles = [];
$publicsDisponibles = [];

foreach ($catalogueBoutique as $produitBoutique) {
    $slugCategorie = (string) ($produitBoutique['categorie'] ?? '');
    $labelCategorie = (string) ($produitBoutique['categorie_label'] ?? $produitBoutique['type'] ?? 'Categorie');
    $slugPublic = (string) ($produitBoutique['public_cible'] ?? '');
    $labelPublic = (string) ($produitBoutique['public_label'] ?? 'Tous');

    if ($slugCategorie !== '' && !isset($categoriesDisponibles[$slugCategorie])) {
        $categoriesDisponibles[$slugCategorie] = $labelCategorie;
    }

    if ($slugPublic !== '' && !isset($publicsDisponibles[$slugPublic])) {
        $publicsDisponibles[$slugPublic] = $labelPublic;
    }
}

$resultatsBoutique = array_values(array_filter($catalogueBoutique, static function (array $produitBoutique) use (
    $recherche,
    $categorieActive,
    $publicActif,
    $enStockSeulement,
    $adhesionSeulement,
    $prixMaxActif
): bool {
    $texteRecherche = mb_strtolower(trim($recherche));
    $categorie = (string) ($produitBoutique['categorie'] ?? '');
    $publicCible = (string) ($produitBoutique['public_cible'] ?? '');
    $prix = (int) ($produitBoutique['prix_euros'] ?? 0);
    $estEnStock = (bool) ($produitBoutique['en_stock'] ?? false);

    if ($texteRecherche !== '') {
        $corpus = mb_strtolower(implode(' ', [
            (string) ($produitBoutique['reference'] ?? ''),
            (string) ($produitBoutique['titre'] ?? ''),
            (string) ($produitBoutique['texte'] ?? ''),
            (string) ($produitBoutique['resume'] ?? ''),
            (string) ($produitBoutique['badge'] ?? ''),
        ]));

        if (!str_contains($corpus, $texteRecherche)) {
            return false;
        }
    }

    if ($categorieActive !== '' && $categorie !== $categorieActive) {
        return false;
    }

    if ($publicActif !== '' && $publicCible !== $publicActif) {
        return false;
    }

    if ($enStockSeulement && !$estEnStock) {
        return false;
    }

    if ($adhesionSeulement && $categorie !== 'adhesion') {
        return false;
    }

    return $prix <= $prixMaxActif;
}));

usort($resultatsBoutique, static function (array $gauche, array $droite) use ($triActif): int {
    $prixGauche = (int) ($gauche['prix_euros'] ?? 0);
    $prixDroite = (int) ($droite['prix_euros'] ?? 0);
    $titreGauche = (string) ($gauche['titre'] ?? '');
    $titreDroite = (string) ($droite['titre'] ?? '');
    $stockGauche = (bool) ($gauche['en_stock'] ?? false);
    $stockDroite = (bool) ($droite['en_stock'] ?? false);

    return match ($triActif) {
        'prix_asc' => $prixGauche <=> $prixDroite,
        'prix_desc' => $prixDroite <=> $prixGauche,
        'alphabetique' => strcasecmp($titreGauche, $titreDroite),
        'stock' => [$stockDroite, $prixGauche] <=> [$stockGauche, $prixDroite],
        default => [
            !$stockGauche,
            (string) ($gauche['categorie'] ?? ''),
            $prixGauche,
            $titreGauche,
        ] <=> [
            !$stockDroite,
            (string) ($droite['categorie'] ?? ''),
            $prixDroite,
            $titreDroite,
        ],
    };
});

$nbProduitsDisponibles = count(array_filter($catalogueBoutique, static fn (array $produitBoutique): bool => (bool) ($produitBoutique['en_stock'] ?? false)));
$nbDemandesAdhesion = count(array_filter($catalogueBoutique, static fn (array $produitBoutique): bool => (string) ($produitBoutique['categorie'] ?? '') === 'adhesion'));
$estAdherent = (bool) ($authData['est_adherent'] ?? false);
$libelleAdhesion = (string) ($membre['membership_label'] ?? ($estAdherent ? 'Adherent actif' : 'Aucune adhesion active'));
?>

<section class="page-banner reveal reveal-2">
    <p class="eyebrow">Mini shop du club</p>
    <h1><?= e($donneesPage['titre']) ?></h1>
    <p><?= e($donneesPage['intro']) ?></p>
</section>

<section class="shop-summary-grid reveal reveal-3">
    <article class="panel shop-summary-card">
        <p class="card-tag">Catalogue</p>
        <strong class="shop-summary-value"><?= e((string) count($catalogueBoutique)) ?></strong>
        <p>References visibles entre adhesion, textile, accessoires et materiel.</p>
    </article>

    <article class="panel shop-summary-card">
        <p class="card-tag">Disponibles</p>
        <strong class="shop-summary-value"><?= e((string) $nbProduitsDisponibles) ?></strong>
        <p>Produits ou demandes actuellement ouverts a la reservation.</p>
    </article>

    <article class="panel shop-summary-card">
        <p class="card-tag">Adhesion</p>
        <strong class="shop-summary-value"><?= e((string) $nbDemandesAdhesion) ?></strong>
        <p>Formules prevues pour rejoindre le club avant l'integration du paiement.</p>
    </article>
</section>

<section class="shop-layout reveal reveal-4">
    <aside class="panel shop-sidebar">
        <div class="section-head section-head--compact">
            <p class="eyebrow">Filtrer les produits</p>
            <h2>Affiner la boutique</h2>
            <p>Trie les objets du club, les reservations et les formules d'adhesion comme dans un mini shop.</p>
        </div>

        <form method="get" action="<?= e(url_route('boutique')) ?>" class="shop-filter-form">
            <label class="shop-field">
                <span>Rechercher une reference</span>
                <input
                    class="shop-input"
                    type="search"
                    name="q"
                    value="<?= e($recherche) ?>"
                    placeholder="Designation, reference, modele..."
                >
            </label>

            <label class="shop-field">
                <span>Categorie</span>
                <select class="shop-select" name="categorie">
                    <option value="">Toutes les categories</option>
                    <?php foreach ($categoriesDisponibles as $slugCategorie => $labelCategorie): ?>
                        <option value="<?= e($slugCategorie) ?>"<?= $slugCategorie === $categorieActive ? ' selected' : '' ?>>
                            <?= e($labelCategorie) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="shop-field">
                <span>Public concerne</span>
                <select class="shop-select" name="public">
                    <option value="">Tous les publics</option>
                    <?php foreach ($publicsDisponibles as $slugPublic => $labelPublic): ?>
                        <option value="<?= e($slugPublic) ?>"<?= $slugPublic === $publicActif ? ' selected' : '' ?>>
                            <?= e($labelPublic) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="shop-field">
                <span>Prix maximum</span>
                <input
                    class="shop-input"
                    type="number"
                    name="prix_max"
                    min="0"
                    max="<?= e((string) $prixMaxCatalogue) ?>"
                    step="1"
                    value="<?= e((string) $prixMaxActif) ?>"
                >
                <small class="shop-help">Budget actuel : jusqu'a <?= e((string) $prixMaxActif) ?> EUR.</small>
            </label>

            <label class="shop-field">
                <span>Tri</span>
                <select class="shop-select" name="tri">
                    <option value="recommandes"<?= $triActif === 'recommandes' ? ' selected' : '' ?>>Recommandes du club</option>
                    <option value="prix_asc"<?= $triActif === 'prix_asc' ? ' selected' : '' ?>>Prix croissant</option>
                    <option value="prix_desc"<?= $triActif === 'prix_desc' ? ' selected' : '' ?>>Prix decroissant</option>
                    <option value="alphabetique"<?= $triActif === 'alphabetique' ? ' selected' : '' ?>>Ordre alphabetique</option>
                    <option value="stock"<?= $triActif === 'stock' ? ' selected' : '' ?>>Disponibles d'abord</option>
                </select>
            </label>

            <label class="shop-toggle">
                <input type="checkbox" name="en_stock" value="1"<?= $enStockSeulement ? ' checked' : '' ?>>
                <span>Voir uniquement les produits disponibles</span>
            </label>

            <label class="shop-toggle">
                <input type="checkbox" name="adhesion_only" value="1"<?= $adhesionSeulement ? ' checked' : '' ?>>
                <span>Voir uniquement les formules d'adhesion</span>
            </label>

            <div class="shop-filter-actions">
                <button type="submit" class="button button-primary">Appliquer les filtres</button>
                <a class="button button-secondary" href="<?= e(url_route('boutique')) ?>">Reinitialiser</a>
            </div>
        </form>

        <article class="shop-membership-card">
            <p class="card-tag">Espace adhesion</p>
            <h3><?= e($libelleAdhesion) ?></h3>
            <p>
                <?= $estAdherent
                    ? "Ton compte est deja rattache a une adhesion active. Tu peux surtout utiliser cette page pour le textile et le materiel."
                    : "Tu n'as pas encore d'adhesion active. Les cartes Adhesion ci-contre servent a lancer une demande avant la future integration du paiement." ?>
            </p>
            <p class="shop-membership-note">Paiement en ligne et terminal : a brancher plus tard. Pour l'instant, le club enregistre la demande puis la valide manuellement.</p>
        </article>
    </aside>

    <div class="shop-main">
        <article class="panel">
            <div class="shop-results-bar">
                <div>
                    <p class="eyebrow">Catalogue filtre</p>
                    <h2><?= e((string) count($resultatsBoutique)) ?> resultat<?= count($resultatsBoutique) > 1 ? 's' : '' ?></h2>
                    <p>Selection de produits, packs et adhesion avec reservation locale depuis l'espace membre.</p>
                </div>
                <div class="shop-results-meta">
                    <span class="status-pill"><?= e((string) $nbProduitsDisponibles) ?> disponibles</span>
                    <span class="status-pill"><?= e((string) $nbDemandesAdhesion) ?> formules adhesion</span>
                </div>
            </div>

            <?php if ($resultatsBoutique === []): ?>
                <div class="empty-state shop-empty-state">
                    <p class="card-tag">Aucun resultat</p>
                    <h3>Aucun produit ne correspond a tes filtres.</h3>
                    <p>Essaie d'ouvrir le budget, de retirer le filtre adhesion ou de revenir a toutes les categories.</p>
                </div>
            <?php else: ?>
                <div class="shop-grid">
                    <?php foreach ($resultatsBoutique as $produitBoutique): ?>
                        <?php
                        $estAdhesion = (string) ($produitBoutique['categorie'] ?? '') === 'adhesion';
                        $estReservable = (bool) ($produitBoutique['en_stock'] ?? false) || in_array((string) ($produitBoutique['mode_vente'] ?? ''), ['precommande', 'adhesion'], true);
                        $estDejaAdherent = $estAdherent && $estAdhesion;
                        $libelleBouton = $estAdhesion ? "Demander l'adhesion" : 'Reserver ce produit';
                        ?>
                        <article class="info-card shop-card<?= !$estReservable ? ' shop-card--muted' : '' ?>">
                            <div class="shop-card-head">
                                <div>
                                    <p class="card-tag"><?= e((string) ($produitBoutique['categorie_label'] ?? 'Produit')) ?></p>
                                    <p class="shop-card-reference"><?= e((string) ($produitBoutique['reference'] ?? 'REF')) ?></p>
                                </div>
                                <span class="shop-card-badge"><?= e((string) ($produitBoutique['badge'] ?? 'Club')) ?></span>
                            </div>

                            <h3><?= e((string) ($produitBoutique['titre'] ?? 'Produit')) ?></h3>
                            <p class="shop-card-price"><?= e((string) ($produitBoutique['prix_euros'] ?? 0)) ?> EUR</p>
                            <p><?= e((string) ($produitBoutique['texte'] ?? '')) ?></p>

                            <div class="shop-card-meta">
                                <span class="status-pill"><?= e((string) ($produitBoutique['stock_label'] ?? 'Disponible')) ?></span>
                                <span class="status-pill"><?= e((string) ($produitBoutique['public_label'] ?? 'Tous')) ?></span>
                            </div>

                            <p class="shop-card-summary"><?= e((string) ($produitBoutique['resume'] ?? '')) ?></p>

                            <?php if (!empty($produitBoutique['avantages']) && is_array($produitBoutique['avantages'])): ?>
                                <ul class="shop-feature-list">
                                    <?php foreach ($produitBoutique['avantages'] as $avantage): ?>
                                        <li><?= e((string) $avantage) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>

                            <?php if ($estDejaAdherent): ?>
                                <p class="shop-inline-note">Ton compte est deja adherent. Cette formule n'a pas besoin d'etre redemandee.</p>
                            <?php endif; ?>

                            <form method="post" action="<?= e(url_route('boutique')) ?>" class="article-form shop-card-form">
                                <input type="hidden" name="action" value="order_product">
                                <input type="hidden" name="_token" value="<?= e($siteData['jeton_csrf']) ?>">
                                <input type="hidden" name="jeton_csrf" value="<?= e($siteData['jeton_csrf']) ?>">
                                <input type="hidden" name="produit" value="<?= e((string) ($produitBoutique['titre'] ?? 'Produit')) ?>">
                                <input type="hidden" name="categorie" value="<?= e((string) ($produitBoutique['categorie_label'] ?? $produitBoutique['type'] ?? 'Produit')) ?>">
                                <button
                                    type="submit"
                                    class="button button-primary shop-card-button"
                                    <?= !$estReservable || $estDejaAdherent ? 'disabled' : '' ?>
                                >
                                    <?= e($estDejaAdherent ? 'Adhesion deja active' : $libelleBouton) ?>
                                </button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </article>

        <section id="boutique-commandes" class="panel panel-contrast shop-orders-panel">
            <div class="section-head section-head--compact">
                <p class="eyebrow">Mes demandes</p>
                <h2>Suivre reservations et adhesion</h2>
                <p>Chaque clic enregistre une demande locale. Le paiement et le terminal viendront dans une etape suivante.</p>
            </div>

            <?php if ($memberOrders === []): ?>
                <div class="empty-state empty-state--contrast">
                    <p class="card-tag">Aucune demande</p>
                    <h3>Rien n'a encore ete reserve depuis la boutique.</h3>
                    <p>Quand tu reserveras un article ou lanceras une adhesion, le suivi apparaitra ici.</p>
                </div>
            <?php else: ?>
                <div class="stack-list shop-order-list">
                    <?php foreach ($memberOrders as $commande): ?>
                        <article class="schedule-item shop-order-card">
                            <div class="shop-order-head">
                                <p class="card-tag"><?= e((string) ($commande['libelle_statut'] ?? 'En attente')) ?></p>
                                <span class="shop-order-category"><?= e((string) ($commande['categorie'] ?? 'Produit')) ?></span>
                            </div>
                            <h3><?= e((string) ($commande['produit'] ?? 'Commande')) ?></h3>
                            <p>Statut actuel : <?= e((string) ($commande['libelle_statut'] ?? 'En attente')) ?>.</p>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</section>
