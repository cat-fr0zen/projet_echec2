<?php
/**
 * Vue: Boutique.
 *
 * Catalogue du club avec filtres et redirection unique vers HelloAsso.
 */
$catalogueBoutique = $donneesSite['cartes_boutique'] ?? [];
$authData = $siteData['authentification'];
$membre = is_array($authData['user'] ?? null) ? $authData['user'] : [];
$recherche = trim((string) ($_GET['q'] ?? ''));
$categorieActive = trim((string) ($_GET['categorie'] ?? ''));
$publicActif = trim((string) ($_GET['public'] ?? ''));
$triActif = trim((string) ($_GET['tri'] ?? 'recommandes'));
$enStockSeulement = (string) ($_GET['en_stock'] ?? '') === '1';
$adhesionSeulement = (string) ($_GET['adhesion_only'] ?? '') === '1';
$prixMaxCatalogue = 0;
$lienHelloAssoBoutique = (string) ($siteData['lien_helloasso_boutique'] ?? \App\Repositories\ParametreSiteRepository::LIEN_HELLOASSO_PAR_DEFAUT);
$formaterPrixBoutique = static function (int $prixCentimes): string {
    return number_format($prixCentimes / 100, 2, ',', ' ').' €';
};

foreach ($catalogueBoutique as $produitBoutique) {
    $prixMaxCatalogue = max($prixMaxCatalogue, (int) ($produitBoutique['prix_euros'] ?? 0));
}

$prixMaxCatalogue = max($prixMaxCatalogue, 10000);
$prixMaxActif = (int) round((float) str_replace(',', '.', (string) ($_GET['prix_max'] ?? ($prixMaxCatalogue / 100))) * 100);
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
    <p>Retrouve les adhesions, les textiles du club et le materiel avec filtres, tri et lien unique vers HelloAsso.</p>
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
        <p>Produits ou demandes actuellement ouverts depuis le catalogue du club.</p>
    </article>

    <article class="panel shop-summary-card">
        <p class="card-tag">HelloAsso</p>
        <strong class="shop-summary-value">1 lien</strong>
        <p>Tous les articles du site utilisent la meme redirection externe pour l'adhesion et les achats.</p>
    </article>
</section>

<button
    type="button"
    class="shop-sidebar-toggle"
    data-shop-sidebar-toggle
    aria-expanded="false"
    aria-controls="shop-sidebar"
    aria-label="Ouvrir les filtres de la boutique"
    title="Ouvrir les filtres"
>
    <span class="shop-sidebar-toggle__icon" data-shop-sidebar-toggle-icon aria-hidden="true">&gt;</span>
</button>

<section class="shop-layout reveal reveal-4">
    <aside class="panel shop-sidebar" id="shop-sidebar" data-shop-sidebar>
        <div class="section-head section-head--compact">
            <p class="eyebrow">Filtrer les produits</p>
            <h2>Affiner la boutique</h2>
            <p>Trie le catalogue du club puis ouvre HelloAsso pour finaliser l'adhesion, la reservation ou l'achat.</p>
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
                    max="<?= e(number_format($prixMaxCatalogue / 100, 2, '.', '')) ?>"
                    step="0.01"
                    value="<?= e(number_format($prixMaxActif / 100, 2, '.', '')) ?>"
                >
                <small class="shop-help">Budget actuel : jusqu'a <?= e($formaterPrixBoutique($prixMaxActif)) ?>.</small>
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
                    ? "Ton compte est deja rattache a une adhesion active. Tu peux maintenant utiliser la boutique pour les textiles, accessoires et autres besoins du club."
                    : "Tu n'as pas encore d'adhesion active. Les formules d'adhesion visibles ici t'enverront sur HelloAsso pour finaliser la demande." ?>
            </p>
            <p class="shop-membership-note">
                Aucun numero de carte n'est saisi sur ce site.
                Le paiement et l'adhesion passent directement par HelloAsso.
            </p>
            <a
                class="button button-secondary shop-card-button"
                href="<?= e($lienHelloAssoBoutique) ?>"
                target="_blank"
                rel="noopener noreferrer external"
                referrerpolicy="no-referrer"
            >
                Ouvrir HelloAsso
            </a>
        </article>
    </aside>

    <div class="shop-main">
        <article class="panel" id="boutique-catalogue" aria-labelledby="boutique-results-title">
            <div class="shop-results-bar">
                <div role="status" aria-live="polite">
                    <p class="eyebrow">Catalogue filtre</p>
                    <h2 id="boutique-results-title"><?= e((string) count($resultatsBoutique)) ?> resultat<?= count($resultatsBoutique) > 1 ? 's' : '' ?></h2>
                    <p>Selection de produits, packs et adhesion avec redirection externe unique vers HelloAsso.</p>
                </div>
                <div class="shop-results-meta">
                    <span class="status-pill"><?= e((string) $nbProduitsDisponibles) ?> disponibles</span>
                    <span class="status-pill"><?= e((string) $nbDemandesAdhesion) ?> formules adhesion</span>
                    <span class="status-pill">Paiement externe</span>
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
                        $estReservable = (bool) ($produitBoutique['en_stock'] ?? false) || in_array((string) ($produitBoutique['mode_vente'] ?? ''), ['precommande', 'adhesion'], true);
                        $estAdhesion = (string) ($produitBoutique['categorie'] ?? '') === 'adhesion';
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
                            <p class="shop-card-price"><?= e($formaterPrixBoutique((int) ($produitBoutique['prix_euros'] ?? 0))) ?></p>
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

                            <?php if ($estAdhesion && $estAdherent): ?>
                                <p class="shop-inline-note">Ton compte est deja adherent. Tu peux tout de meme verifier ou renouveler ta formule depuis HelloAsso si besoin.</p>
                            <?php endif; ?>

                            <a
                                class="button button-primary shop-card-button"
                                href="<?= e($lienHelloAssoBoutique) ?>"
                                target="_blank"
                                rel="noopener noreferrer external"
                                referrerpolicy="no-referrer"
                            >
                                Ouvrir sur HelloAsso
                            </a>

                            <p class="shop-inline-note">
                                Tous les articles du club utilisent le meme lien securise HelloAsso.
                            </p>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </article>

        <section class="panel panel-contrast shop-orders-panel">
            <div class="section-head section-head--compact">
                <p class="eyebrow">Paiement externe</p>
                <h2>Le site sert maintenant de catalogue</h2>
                <p>Tu consultes les produits ici, puis tu bascules sur HelloAsso pour finaliser l'adhesion, la reservation ou le paiement.</p>
            </div>

            <div class="empty-state empty-state--contrast">
                <p class="card-tag">Redirection unique</p>
                <h3>Un seul lien pour tout le catalogue.</h3>
                <p>Le club peut modifier ce lien depuis l'administration sans changer chaque fiche produit une par une.</p>
                <a
                    class="button button-primary shop-card-button"
                    href="<?= e($lienHelloAssoBoutique) ?>"
                    target="_blank"
                    rel="noopener noreferrer external"
                    referrerpolicy="no-referrer"
                >
                    Ouvrir sur HelloAsso
                </a>
            </div>
        </section>
    </div>
</section>
