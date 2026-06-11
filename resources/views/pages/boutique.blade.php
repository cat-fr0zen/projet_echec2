<?php
/**
 * Vue: Boutique.
 *
 * Mini-shop du club avec filtres, panier, recapitulatif et validation locale.
 */
$catalogueBoutique = $donneesSite['cartes_boutique'] ?? [];
$authData = $siteData['authentification'];
$memberOrders = $siteData['member_orders'] ?? [];
$membre = is_array($authData['user'] ?? null) ? $authData['user'] : [];
$panierBoutique = is_array($siteData['panier_boutique'] ?? null) ? $siteData['panier_boutique'] : [];
$configurationPaiement = is_array($siteData['paiement_boutique'] ?? null) ? $siteData['paiement_boutique'] : [];
$lignesPanier = is_array($panierBoutique['lignes'] ?? null) ? $panierBoutique['lignes'] : [];
$recherche = trim((string) ($_GET['q'] ?? ''));
$categorieActive = trim((string) ($_GET['categorie'] ?? ''));
$publicActif = trim((string) ($_GET['public'] ?? ''));
$triActif = trim((string) ($_GET['tri'] ?? 'recommandes'));
$enStockSeulement = (string) ($_GET['en_stock'] ?? '') === '1';
$adhesionSeulement = (string) ($_GET['adhesion_only'] ?? '') === '1';
$prixMaxCatalogue = 0;
$quantitesPanier = [];
$paiementCarteActif = (bool) ($configurationPaiement['active'] ?? false);
$prestatairePaiement = trim((string) ($configurationPaiement['prestataire'] ?? 'Prestataire externe'));

foreach ($lignesPanier as $lignePanier) {
    $identifiantPanier = trim((string) ($lignePanier['identifiant'] ?? ''));

    if ($identifiantPanier !== '') {
        $quantitesPanier[$identifiantPanier] = (int) ($lignePanier['quantite'] ?? 0);
    }
}

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

    if ($slugCategorie !== '' && ! isset($categoriesDisponibles[$slugCategorie])) {
        $categoriesDisponibles[$slugCategorie] = $labelCategorie;
    }

    if ($slugPublic !== '' && ! isset($publicsDisponibles[$slugPublic])) {
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

        if (! str_contains($corpus, $texteRecherche)) {
            return false;
        }
    }

    if ($categorieActive !== '' && $categorie !== $categorieActive) {
        return false;
    }

    if ($publicActif !== '' && $publicCible !== $publicActif) {
        return false;
    }

    if ($enStockSeulement && ! $estEnStock) {
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
            ! $stockGauche,
            (string) ($gauche['categorie'] ?? ''),
            $prixGauche,
            $titreGauche,
        ] <=> [
            ! $stockDroite,
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
        <p class="card-tag">Panier</p>
        <strong class="shop-summary-value"><?= e((string) ($panierBoutique['quantite_totale'] ?? 0)) ?></strong>
        <p><?= e((string) ($panierBoutique['nombre_lignes'] ?? 0)) ?> ligne<?= ((int) ($panierBoutique['nombre_lignes'] ?? 0)) > 1 ? 's' : '' ?> pour <?= e((string) ($panierBoutique['sous_total_euros'] ?? 0)) ?> EUR.</p>
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
                    : "Tu n'as pas encore d'adhesion active. Les cartes Adhesion servent a preparer une demande propre avant validation du club." ?>
            </p>
            <p class="shop-membership-note">
                Carte bancaire : aucun numero de carte n'est collecte sur le site.
                Le vrai debit devra passer par <?= e($prestatairePaiement) ?> ou un autre prestataire externe.
            </p>
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
                    <span class="status-pill"><?= e((string) ($panierBoutique['quantite_totale'] ?? 0)) ?> dans le panier</span>
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
                        $identifiantProduit = (string) ($produitBoutique['identifiant'] ?? '');
                        $estAdhesion = (string) ($produitBoutique['categorie'] ?? '') === 'adhesion';
                        $estReservable = (bool) ($produitBoutique['en_stock'] ?? false) || in_array((string) ($produitBoutique['mode_vente'] ?? ''), ['precommande', 'adhesion'], true);
                        $estDejaAdherent = $estAdherent && $estAdhesion;
                        $quantiteDansPanier = (int) ($quantitesPanier[$identifiantProduit] ?? 0);
                        $libelleBouton = $estAdhesion
                            ? ($quantiteDansPanier > 0 ? "Remplacer l'adhesion du panier" : "Ajouter l'adhesion au panier")
                            : ($quantiteDansPanier > 0 ? 'Ajouter encore au panier' : 'Ajouter au panier');
                        ?>
                        <article class="info-card shop-card<?= ! $estReservable ? ' shop-card--muted' : '' ?>">
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

                            <?php if (! empty($produitBoutique['avantages']) && is_array($produitBoutique['avantages'])): ?>
                                <ul class="shop-feature-list">
                                    <?php foreach ($produitBoutique['avantages'] as $avantage): ?>
                                        <li><?= e((string) $avantage) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>

                            <?php if ($quantiteDansPanier > 0): ?>
                                <p class="shop-inline-note">
                                    Dans le panier : <?= e((string) $quantiteDansPanier) ?>.
                                </p>
                            <?php endif; ?>

                            <?php if ($estDejaAdherent): ?>
                                <p class="shop-inline-note">Ton compte est deja adherent. Cette formule n'a pas besoin d'etre redemandee.</p>
                            <?php endif; ?>

                            <form method="post" action="<?= e(url_route('boutique')) ?>" class="article-form shop-card-form">
                                <input type="hidden" name="action" value="add_to_cart">
                                <input type="hidden" name="_token" value="<?= e($siteData['jeton_csrf']) ?>">
                                <input type="hidden" name="jeton_csrf" value="<?= e($siteData['jeton_csrf']) ?>">
                                <input type="hidden" name="identifiant_produit" value="<?= e($identifiantProduit) ?>">
                                <button
                                    type="submit"
                                    class="button button-primary shop-card-button"
                                    <?= ! $estReservable || $estDejaAdherent ? 'disabled' : '' ?>
                                >
                                    <?= e($estDejaAdherent ? 'Adhesion deja active' : $libelleBouton) ?>
                                </button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </article>

        <section id="boutique-panier" class="panel panel-contrast shop-cart-panel">
            <div class="section-head section-head--compact">
                <p class="eyebrow">Panier</p>
                <h2>Panier et paiement</h2>
                <p>Le site prepare le recapitulatif et la commande, sans jamais demander de numero de carte en direct.</p>
            </div>

            <?php if (($panierBoutique['est_vide'] ?? true) === true): ?>
                <div class="empty-state empty-state--contrast">
                    <p class="card-tag">Panier vide</p>
                    <h3>Ajoute d'abord un produit ou une adhesion depuis le catalogue.</h3>
                    <p>Le recapitulatif et la preparation du paiement apparaitront ici.</p>
                </div>
            <?php else: ?>
                <div class="shop-cart-layout">
                    <div class="stack-list shop-cart-list">
                        <?php foreach ($lignesPanier as $lignePanier): ?>
                            <?php
                            $estAdhesionPanier = (bool) ($lignePanier['est_adhesion'] ?? false);
                            $quantiteLigne = (int) ($lignePanier['quantite'] ?? 1);
                            $prixUnitaireLigne = (int) ($lignePanier['prix_unitaire_euros'] ?? 0);
                            $totalLigne = (int) ($lignePanier['prix_total_euros'] ?? 0);
                            ?>
                            <article class="schedule-item shop-cart-item">
                                <div class="shop-order-head">
                                    <div>
                                        <p class="card-tag"><?= e((string) ($lignePanier['categorie_label'] ?? 'Produit')) ?></p>
                                        <p class="shop-card-reference"><?= e((string) ($lignePanier['reference'] ?? 'REF')) ?></p>
                                    </div>
                                    <span class="shop-card-badge"><?= e((string) ($lignePanier['badge'] ?? 'Club')) ?></span>
                                </div>

                                <h3><?= e((string) ($lignePanier['titre'] ?? 'Produit')) ?></h3>
                                <p class="shop-cart-pricing"><?= e((string) $prixUnitaireLigne) ?> EUR x <?= e((string) $quantiteLigne) ?> = <?= e((string) $totalLigne) ?> EUR</p>

                                <?php if (trim((string) ($lignePanier['resume'] ?? '')) !== ''): ?>
                                    <p class="shop-card-summary"><?= e((string) ($lignePanier['resume'] ?? '')) ?></p>
                                <?php endif; ?>

                                <div class="shop-cart-actions">
                                    <?php if ($estAdhesionPanier): ?>
                                        <p class="shop-help">Quantite fixe : 1 formule d'adhesion par ligne.</p>
                                    <?php else: ?>
                                        <form method="post" action="<?= e(url_route('boutique')) ?>" class="shop-cart-update-form">
                                            <input type="hidden" name="action" value="update_cart">
                                            <input type="hidden" name="_token" value="<?= e($siteData['jeton_csrf']) ?>">
                                            <input type="hidden" name="jeton_csrf" value="<?= e($siteData['jeton_csrf']) ?>">
                                            <input type="hidden" name="identifiant_produit" value="<?= e((string) ($lignePanier['identifiant'] ?? '')) ?>">
                                            <input
                                                class="shop-input shop-input--compact"
                                                type="number"
                                                name="quantite_panier"
                                                min="0"
                                                max="99"
                                                value="<?= e((string) $quantiteLigne) ?>"
                                            >
                                            <button type="submit" class="button button-secondary">Mettre a jour</button>
                                        </form>
                                    <?php endif; ?>

                                    <form method="post" action="<?= e(url_route('boutique')) ?>" class="shop-cart-remove-form">
                                        <input type="hidden" name="action" value="remove_from_cart">
                                        <input type="hidden" name="_token" value="<?= e($siteData['jeton_csrf']) ?>">
                                        <input type="hidden" name="jeton_csrf" value="<?= e($siteData['jeton_csrf']) ?>">
                                        <input type="hidden" name="identifiant_produit" value="<?= e((string) ($lignePanier['identifiant'] ?? '')) ?>">
                                        <button type="submit" class="button button-secondary">Retirer</button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <aside class="shop-cart-summary">
                        <p class="card-tag">Recapitulatif</p>
                        <h3><?= e((string) ($panierBoutique['sous_total_euros'] ?? 0)) ?> EUR</h3>
                        <ul class="shop-total-list">
                            <li><?= e((string) ($panierBoutique['nombre_lignes'] ?? 0)) ?> ligne<?= ((int) ($panierBoutique['nombre_lignes'] ?? 0)) > 1 ? 's' : '' ?></li>
                            <li><?= e((string) ($panierBoutique['quantite_totale'] ?? 0)) ?> article<?= ((int) ($panierBoutique['quantite_totale'] ?? 0)) > 1 ? 's' : '' ?> au total</li>
                            <li>Paiement souhaite : carte bancaire</li>
                        </ul>

                        <article class="shop-payment-card<?= ! $paiementCarteActif ? ' shop-payment-card--pending' : '' ?>">
                            <p class="card-tag">Carte bancaire</p>
                            <p><?= e((string) ($configurationPaiement['resume'] ?? '')) ?></p>
                            <p class="shop-help">Aucun champ numero de carte ou cryptogramme n'est expose sur cette page.</p>
                        </article>

                        <form method="post" action="<?= e(url_route('boutique')) ?>" class="shop-checkout-form">
                            <input type="hidden" name="action" value="checkout_cart">
                            <input type="hidden" name="_token" value="<?= e($siteData['jeton_csrf']) ?>">
                            <input type="hidden" name="jeton_csrf" value="<?= e($siteData['jeton_csrf']) ?>">
                            <input type="hidden" name="mode_paiement" value="carte_bancaire">
                            <button type="submit" class="button button-primary shop-card-button">
                                <?= e($paiementCarteActif ? 'Continuer vers le paiement CB' : 'Finaliser la commande') ?>
                            </button>
                        </form>

                        <form method="post" action="<?= e(url_route('boutique')) ?>" class="shop-clear-form">
                            <input type="hidden" name="action" value="clear_cart">
                            <input type="hidden" name="_token" value="<?= e($siteData['jeton_csrf']) ?>">
                            <input type="hidden" name="jeton_csrf" value="<?= e($siteData['jeton_csrf']) ?>">
                            <button type="submit" class="button button-secondary shop-card-button">Vider le panier</button>
                        </form>

                        <?php if (! $paiementCarteActif): ?>
                            <p class="shop-help">
                                Pour un vrai debit CB, il faudra ensuite relier cette etape a <?= e($prestatairePaiement) ?>,
                                Stripe, SumUp ou un autre prestataire conforme.
                            </p>
                        <?php endif; ?>
                    </aside>
                </div>
            <?php endif; ?>
        </section>

        <section id="boutique-commandes" class="panel panel-contrast shop-orders-panel">
            <div class="section-head section-head--compact">
                <p class="eyebrow">Mes demandes</p>
                <h2>Suivre reservations et adhesion</h2>
                <p>Chaque validation de panier cree une ou plusieurs commandes locales que le club pourra ensuite traiter.</p>
            </div>

            <?php if ($memberOrders === []): ?>
                <div class="empty-state empty-state--contrast">
                    <p class="card-tag">Aucune demande</p>
                    <h3>Rien n'a encore ete valide depuis la boutique.</h3>
                    <p>Quand tu finaliseras un panier, le suivi apparaitra ici.</p>
                </div>
            <?php else: ?>
                <div class="stack-list shop-order-list">
                    <?php foreach ($memberOrders as $commande): ?>
                        <?php
                        $quantiteCommande = max(1, (int) ($commande['quantite'] ?? 1));
                        $totalCommande = $commande['prix_total_euros'] ?? null;
                        $lotCommande = trim((string) ($commande['lot_commande'] ?? ''));
                        ?>
                        <article class="schedule-item shop-order-card">
                            <div class="shop-order-head">
                                <p class="card-tag"><?= e((string) ($commande['libelle_statut'] ?? 'En attente')) ?></p>
                                <span class="shop-order-category"><?= e((string) ($commande['categorie'] ?? 'Produit')) ?></span>
                            </div>
                            <h3><?= e((string) ($commande['produit'] ?? 'Commande')) ?></h3>
                            <p>Quantite : <?= e((string) $quantiteCommande) ?>.</p>
                            <?php if ($totalCommande !== null): ?>
                                <p>Total estime : <?= e((string) $totalCommande) ?> EUR.</p>
                            <?php endif; ?>
                            <p>Paiement : <?= e((string) ($commande['libelle_mode_paiement'] ?? 'Reglement au club')) ?> - <?= e((string) ($commande['libelle_statut_paiement'] ?? 'A finaliser')) ?>.</p>
                            <?php if ($lotCommande !== ''): ?>
                                <p class="shop-order-batch">Lot : <?= e($lotCommande) ?></p>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</section>
