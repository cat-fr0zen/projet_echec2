<?php
/**
 * Vue: Administration (dashboard).
 *
 * Affiche les outils de pilotage reserves au role `admin`:
 * - gestion des comptes (roles / statut compte / statut adhesion)
 * - moderation des medias (validation / rejet)
 * - suivi des articles et des commandes
 *
 * Donnees attendues (injectees par le controleur):
 * - $pageData: titre/intro de la page
 * - $siteData: listes globales (utilisateurs, articles, medias, commandes)
 */
$allUsers = $siteData['all_users'] ?? [];
$allArticles = $siteData['all_articles'] ?? [];
$allMedia = $siteData['all_media'] ?? [];
$allOrders = $siteData['all_orders'] ?? [];
$authData = is_array($siteData['authentification'] ?? null) ? $siteData['authentification'] : [];
$currentAdmin = is_array($authData['user'] ?? null) ? $authData['user'] : [];
$currentAdminId = (string) ($currentAdmin['identifiant'] ?? '');
$roleSummary = is_array($siteData['resume_roles_compte'] ?? null) ? $siteData['resume_roles_compte'] : [];
$profCount = (int) ($roleSummary['prof'] ?? 0);
$profLimit = (int) ($siteData['limite_professeurs'] ?? 10);
$trafficSummary = is_array($siteData['resume_trafic_visiteurs'] ?? null) ? $siteData['resume_trafic_visiteurs'] : [];
$popularPages = is_array($trafficSummary['pages_populaires'] ?? null) ? $trafficSummary['pages_populaires'] : [];
$latestVisits = is_array($trafficSummary['dernieres_visites'] ?? null) ? $trafficSummary['dernieres_visites'] : [];
$horairesClub = is_array($siteData['horaires_club'] ?? null) ? $siteData['horaires_club'] : [];
$itemsHorairesClub = is_array($horairesClub['items'] ?? null) ? $horairesClub['items'] : [];
$blocsConstructeurAccueil = is_array($siteData['constructeur_accueil_blocs'] ?? null) ? $siteData['constructeur_accueil_blocs'] : [];
$lignesHorairesAdmin = $itemsHorairesClub;

while (count($lignesHorairesAdmin) < 10) {
    $lignesHorairesAdmin[] = [
        'day' => '',
        'time' => '',
        'title' => '',
        'details' => '',
        'is_holiday' => false,
    ];
}
?>

<section class="page-banner reveal reveal-2">
    <p class="eyebrow">Administration</p>
    <h1><?= e($pageData['title']) ?></h1>
    <p><?= e($pageData['intro']) ?></p>
</section>

<section id="admin-newsletter-boutique" class="section-block reveal reveal-6">
    <div class="section-head">
        <p class="eyebrow">Newsletter</p>
        <h2>Informer les abonnes d'une nouveaute boutique.</h2>
        <p>Ce bouton envoie une actualite email aux personnes inscrites a la newsletter lorsque le club ajoute un nouvel objet ou une information boutique.</p>
    </div>

    <form method="post" action="<?= e(url_route('admin')) ?>#admin-newsletter-boutique" class="article-form">
        <input type="hidden" name="action" value="notify_shop_item">
        <input type="hidden" name="_token" value="<?= e($siteData['jeton_csrf']) ?>">
        <input type="hidden" name="jeton_csrf" value="<?= e($siteData['jeton_csrf']) ?>">

        <label for="titre-objet-boutique">Nom de l'objet ou de l'information boutique</label>
        <input
            id="titre-objet-boutique"
            type="text"
            name="titre_objet_boutique"
            maxlength="150"
            required
        >

        <button type="submit" class="button button-primary">Envoyer l'actualite boutique</button>
    </form>
</section>

<section class="section-block reveal reveal-3">
    <div class="section-head">
        <p class="eyebrow">Tableau de bord</p>
        <h2>Piloter les comptes, les articles et les médias.</h2>
        <p>Cette page est réservée au président administrateur. Toutes les décisions se prennent ici sans quitter le site.</p>
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
            <p class="card-tag">Médias</p>
            <span class="metric-value"><?= e((string) count($allMedia)) ?></span>
            <h3>Médias déposés</h3>
        </article>
        <article class="info-card">
            <p class="card-tag">Commandes</p>
            <span class="metric-value"><?= e((string) count($allOrders)) ?></span>
            <h3>Merch commande</h3>
        </article>
        <article class="info-card">
            <p class="card-tag">Professeurs</p>
            <span class="metric-value"><?= e((string) $profCount) ?> / <?= e((string) $profLimit) ?></span>
            <h3>Rôles prof attribués</h3>
        </article>
        <article class="info-card">
            <p class="card-tag">Visiteurs</p>
            <span class="metric-value"><?= e((string) ($trafficSummary['visiteurs_uniques_7_jours'] ?? 0)) ?></span>
            <h3>Visiteurs uniques sur 7 jours</h3>
        </article>
    </div>
</section>

<section id="admin-constructeur" class="section-block reveal reveal-3">
    <div class="section-head">
        <p class="eyebrow">Constructeur</p>
        <h2>Organiser l'accueil avec des blocs interchangeables.</h2>
        <p>Les blocs verrouilles restent a leur place. Les autres peuvent etre deplaces ou masques sans toucher au code.</p>
    </div>

    <form method="post" action="<?= e(url_route('admin')) ?>#admin-constructeur" class="admin-form">
        <input type="hidden" name="action" value="update_home_builder">
        <input type="hidden" name="_token" value="<?= e($siteData['jeton_csrf']) ?>">
        <input type="hidden" name="jeton_csrf" value="<?= e($siteData['jeton_csrf']) ?>">

        <div class="admin-list">
            <?php foreach ($blocsConstructeurAccueil as $blocConstructeur): ?>
                <?php
                $codeBlocConstructeur = (string) ($blocConstructeur['code_bloc'] ?? '');
                $estVerrouilleConstructeur = (bool) ($blocConstructeur['est_verrouille'] ?? false);
                $estActifConstructeur = (bool) ($blocConstructeur['est_actif'] ?? false);
                ?>
                <article class="info-card admin-card">
                    <p class="card-tag"><?= e((string) ($blocConstructeur['libelle_bloc'] ?? 'Bloc')) ?></p>
                    <h3><?= e($estVerrouilleConstructeur ? 'Bloc fixe' : 'Bloc interchangeable') ?></h3>
                    <p><?= e((string) ($blocConstructeur['description_bloc'] ?? '')) ?></p>

                    <div class="admin-schedule-settings">
                        <label class="form-group">
                            <span>Position sur la page d'accueil</span>
                            <input
                                type="number"
                                min="1"
                                name="ordre_bloc[<?= e($codeBlocConstructeur) ?>]"
                                value="<?= e((string) ($blocConstructeur['ordre_affichage'] ?? 1)) ?>"
                                <?= $estVerrouilleConstructeur ? 'readonly' : '' ?>
                            >
                        </label>

                        <label class="form-group">
                            <span>Affichage public</span>
                            <span class="checkbox-inline">
                                <input
                                    type="checkbox"
                                    name="bloc_actif[<?= e($codeBlocConstructeur) ?>]"
                                    value="1"
                                    <?= $estActifConstructeur ? 'checked' : '' ?>
                                    <?= $estVerrouilleConstructeur ? 'disabled' : '' ?>
                                >
                                <span><?= e($estVerrouilleConstructeur ? 'Toujours visible' : 'Visible sur le site') ?></span>
                            </span>
                            <?php if ($estVerrouilleConstructeur): ?>
                                <small class="form-helper">Ce bloc reste visible et ne peut pas etre deplace.</small>
                            <?php else: ?>
                                <small class="form-helper">Decoche cette case pour masquer temporairement ce bloc.</small>
                            <?php endif; ?>
                        </label>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <button type="submit" class="button button-primary">Enregistrer le constructeur</button>
    </form>
</section>

<section class="section-block reveal reveal-3">
    <div class="section-head">
        <p class="eyebrow">Trafic visiteurs</p>
        <h2>Surveiller le trafic des visiteurs non connectés.</h2>
        <p>Ce suivi reste centré sur la fréquentation publique du site, sans conserver d'adresse IP brute.</p>
    </div>

    <div class="admin-summary-grid">
        <article class="info-card">
            <p class="card-tag">Aujourd'hui</p>
            <span class="metric-value"><?= e((string) ($trafficSummary['visites_aujourdhui'] ?? 0)) ?></span>
            <h3>Visites invitées</h3>
        </article>
        <article class="info-card">
            <p class="card-tag">7 jours</p>
            <span class="metric-value"><?= e((string) ($trafficSummary['visites_7_jours'] ?? 0)) ?></span>
            <h3>Pages vues invitées</h3>
        </article>
        <article class="info-card">
            <p class="card-tag">7 jours</p>
            <span class="metric-value"><?= e((string) ($trafficSummary['visiteurs_uniques_7_jours'] ?? 0)) ?></span>
            <h3>Visiteurs uniques</h3>
        </article>
    </div>

    <div class="split-grid">
        <article class="panel">
            <div class="section-head section-head--compact">
                <p class="eyebrow">Pages populaires</p>
                <h2>Qu'est-ce qui attire le plus ?</h2>
            </div>

            <div class="admin-list">
                <?php if ($popularPages === []): ?>
                    <div class="empty-state">
                        <p class="card-tag">Aucune donnée</p>
                        <h3>Le journal visiteurs est encore vide.</h3>
                    </div>
                <?php else: ?>
                    <?php foreach ($popularPages as $pagePopulaire): ?>
                        <article class="info-card admin-card">
                            <p class="card-tag"><?= e((string) ($pagePopulaire['page'] ?? '')) ?></p>
                            <span class="metric-value"><?= e((string) ($pagePopulaire['total'] ?? 0)) ?></span>
                            <h3>Visites publiques</h3>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </article>

        <article class="panel">
            <div class="section-head section-head--compact">
                <p class="eyebrow">Dernières visites</p>
                <h2>D'où viennent les visiteurs ?</h2>
            </div>

            <div class="admin-list">
                <?php if ($latestVisits === []): ?>
                    <div class="empty-state">
                        <p class="card-tag">Aucune donnée</p>
                        <h3>Aucune visite invitée récente.</h3>
                    </div>
                <?php else: ?>
                    <?php foreach ($latestVisits as $visite): ?>
                        <article class="info-card admin-card">
                            <p class="card-tag"><?= e((string) ($visite['page'] ?? '')) ?></p>
                            <h3><?= e((string) (($visite['hote_referent'] ?? '') !== '' ? $visite['hote_referent'] : 'Accès direct')) ?></h3>
                            <p><?= e((string) ($visite['visite_le'] ?? '')) ?></p>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </article>
    </div>
</section>

<section id="admin-horaires-club" class="section-block reveal reveal-4">
    <div class="section-head">
        <p class="eyebrow">Horaires</p>
        <h2>Modifier l'emploi du temps public.</h2>
        <p>L'administrateur peut adapter les créneaux à tout moment, y compris pour un jour férié ou une fermeture exceptionnelle.</p>
    </div>

    <form method="post" action="<?= e(url_route('admin')) ?>#admin-horaires-club" class="admin-form schedule-admin-form">
        <input type="hidden" name="action" value="update_club_schedule">
        <input type="hidden" name="_token" value="<?= e($siteData['jeton_csrf']) ?>">
        <input type="hidden" name="jeton_csrf" value="<?= e($siteData['jeton_csrf']) ?>">

        <div class="admin-schedule-settings">
            <label class="form-group">
                <span>Titre public</span>
                <input
                    type="text"
                    name="libelle_saison_horaires"
                    value="<?= e((string) ($horairesClub['season_label'] ?? 'Horaires du club')) ?>"
                    maxlength="120"
                    required
                >
            </label>

            <label class="form-group">
                <span>Message jour férié / exception</span>
                <textarea name="message_jour_ferie" rows="3" maxlength="320"><?= e((string) ($horairesClub['holiday_notice'] ?? '')) ?></textarea>
                <small class="form-helper">Ce message apparaît sur l'accueil avec le libellé “Jour férié”.</small>
            </label>
        </div>

        <div class="admin-schedule-grid">
            <?php foreach ($lignesHorairesAdmin as $indexHoraire => $horaire): ?>
                <fieldset class="info-card admin-card admin-schedule-card">
                    <legend>Créneau <?= e((string) ($indexHoraire + 1)) ?></legend>

                    <label class="form-group">
                        <span>Jour</span>
                        <input
                            type="text"
                            name="horaire_jour[]"
                            value="<?= e((string) ($horaire['day'] ?? '')) ?>"
                            maxlength="60"
                            placeholder="Exemple : Samedi"
                        >
                    </label>

                    <label class="form-group">
                        <span>Horaire</span>
                        <input
                            type="text"
                            name="horaire_heure[]"
                            value="<?= e((string) ($horaire['time'] ?? '')) ?>"
                            maxlength="80"
                            placeholder="Exemple : 10h30 à 12h00"
                        >
                    </label>

                    <label class="form-group">
                        <span>Activité affichée dans le détail</span>
                        <input
                            type="text"
                            name="horaire_titre[]"
                            value="<?= e((string) ($horaire['title'] ?? '')) ?>"
                            maxlength="180"
                            placeholder="Exemple : Parties libres"
                        >
                    </label>

                    <label class="form-group">
                        <span>Détails de l'emploi du temps</span>
                        <textarea
                            name="horaire_details[]"
                            rows="5"
                            maxlength="1400"
                            placeholder="Lieu, intervenants, groupes, précisions..."
                        ><?= e((string) ($horaire['details'] ?? '')) ?></textarea>
                    </label>

                    <label class="checkbox-row">
                        <input
                            type="checkbox"
                            name="horaire_jour_ferie[]"
                            value="<?= e((string) $indexHoraire) ?>"
                            <?= !empty($horaire['is_holiday']) ? 'checked' : '' ?>
                        >
                        <span>Marquer ce créneau comme jour férié / exception</span>
                    </label>
                </fieldset>
            <?php endforeach; ?>
        </div>

        <button type="submit" class="button button-primary">Enregistrer les horaires</button>
    </form>
</section>

<section class="section-block reveal reveal-4">
    <div class="section-head">
        <p class="eyebrow">Comptes</p>
        <h2>Gérer les rôles et les statuts.</h2>
        <p>Le rôle détermine les droits, et le statut permet de suspendre un accès si besoin.</p>
    </div>

    <div class="admin-list">
        <?php foreach ($allUsers as $user): ?>
            <article class="info-card admin-card">
                <p class="card-tag"><?= e((string) ($user['role'] ?? 'connecte')) ?></p>
                <h3><?= e(trim((string) ($user['prenom'] ?? '') . ' ' . (string) ($user['nom'] ?? ''))) ?></h3>
                <p><?= e((string) ($user['courriel'] ?? '')) ?></p>
                <?php if (($user['numero_licence'] ?? '') !== ''): ?>
                    <p class="card-subtitle">Licence FFE: <?= e((string) $user['numero_licence']) ?></p>
                <?php endif; ?>

                <form method="post" action="<?= e(url_route('admin')) ?>" class="admin-form">
                    <input type="hidden" name="action" value="update_user_access">
                    <input type="hidden" name="_token" value="<?= e($siteData['jeton_csrf']) ?>">
                    <input type="hidden" name="jeton_csrf" value="<?= e($siteData['jeton_csrf']) ?>">
                    <input type="hidden" name="identifiant_utilisateur_cible" value="<?= e((string) ($user['identifiant'] ?? '')) ?>">

                    <label class="form-group">
                        <span>Rôle</span>
                        <select name="role_utilisateur">
                            <option value="connecte"<?= ($user['role'] ?? '') === 'connecte' ? ' selected' : '' ?>>Connect?</option>
                            <option value="adherent"<?= ($user['role'] ?? '') === 'adherent' ? ' selected' : '' ?>>Adhérent</option>
                            <option value="prof"<?= ($user['role'] ?? '') === 'prof' ? ' selected' : '' ?>>Prof</option>
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
                        <span>Adhésion</span>
                        <select name="statut_adhesion_utilisateur">
                            <option value="aucune"<?= ($user['statut_adhesion'] ?? '') === 'aucune' ? ' selected' : '' ?>>Non adhérent</option>
                            <option value="active"<?= ($user['statut_adhesion'] ?? '') === 'active' ? ' selected' : '' ?>>Adhérent actif</option>
                        </select>
                    </label>

                    <button type="submit" class="button button-primary">Mettre ? jour</button>
                </form>

                <?php if ($currentAdminId !== '' && (string) ($user['identifiant'] ?? '') !== $currentAdminId): ?>
                    <form method="post" action="<?= e(url_route('admin')) ?>" class="admin-form">
                        <input type="hidden" name="action" value="transfer_admin_role">
                        <input type="hidden" name="_token" value="<?= e($siteData['jeton_csrf']) ?>">
                        <input type="hidden" name="jeton_csrf" value="<?= e($siteData['jeton_csrf']) ?>">
                        <input type="hidden" name="identifiant_utilisateur_cible" value="<?= e((string) ($user['identifiant'] ?? '')) ?>">

                        <label class="form-group">
                            <span>Mon rôle après transfert</span>
                            <select name="role_apres_transfert">
                                <option value="prof">Prof</option>
                                <option value="adherent">Adherent</option>
                                <option value="connecte">Compte connecte</option>
                            </select>
                        </label>

                        <button type="submit" class="button button-secondary">Transferer le role admin</button>
                    </form>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="split-grid reveal reveal-5">
    <article class="panel">
        <div class="section-head section-head--compact">
            <p class="eyebrow">Modération articles</p>
            <h2>Valider ou refuser les articles.</h2>
            <p>Chaque article soumis peut rester en attente, être publié ou être refusé.</p>
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
                        <p class="card-subtitle">Auteur: <?= e((string) ($article['auteur_affiche'] ?? $article['nom_auteur'] ?? '')) ?></p>
                        <p class="card-subtitle">Créé le: <?= e((string) ($article['date_creation_libelle'] ?? '')) ?></p>

                        <form method="post" action="<?= e(url_route('admin')) ?>" class="admin-form admin-inline-form">
                            <input type="hidden" name="action" value="review_article">
                            <input type="hidden" name="_token" value="<?= e($siteData['jeton_csrf']) ?>">
                            <input type="hidden" name="jeton_csrf" value="<?= e($siteData['jeton_csrf']) ?>">
                            <input type="hidden" name="identifiant_article" value="<?= e((string) ($article['identifiant'] ?? '')) ?>">

                            <button type="submit" name="statut_article" value="publie" class="button button-primary">Publier</button>
                            <button type="submit" name="statut_article" value="refuse" class="button button-secondary">Refuser</button>
                            <button type="submit" name="statut_article" value="en_attente_validation" class="button button-secondary">Remettre en attente</button>
                        </form>

                        <form method="post" action="<?= e(url_route('admin')) ?>" class="admin-form admin-inline-form" data-confirm-delete>
                            <input type="hidden" name="action" value="delete_article">
                            <input type="hidden" name="_token" value="<?= e($siteData['jeton_csrf']) ?>">
                            <input type="hidden" name="jeton_csrf" value="<?= e($siteData['jeton_csrf']) ?>">
                            <input type="hidden" name="identifiant_article" value="<?= e((string) ($article['identifiant'] ?? '')) ?>">
                            <button type="submit" class="button button-secondary button-danger">Supprimer</button>
                        </form>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </article>

    <article class="panel panel-contrast">
        <div class="section-head section-head--compact">
            <p class="eyebrow">Modération médias</p>
            <h2>Valider ou refuser les photos et vidéos.</h2>
            <p>Le président choisit ici ce qui devient visible publiquement sur la médiathèque.</p>
        </div>

        <div class="admin-list">
            <?php if ($allMedia === []): ?>
                <div class="empty-state empty-state--contrast">
                    <p class="card-tag">Aucun média</p>
                    <h3>Aucun dépôt de média pour le moment.</h3>
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
                            <input type="hidden" name="_token" value="<?= e($siteData['jeton_csrf']) ?>">
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
        <p>Le président peut mettre à jour le statut des commandes créées depuis la boutique.</p>
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
                        <input type="hidden" name="_token" value="<?= e($siteData['jeton_csrf']) ?>">
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
