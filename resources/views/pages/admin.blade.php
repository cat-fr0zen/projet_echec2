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
$newsletterSummary = is_array($siteData['resume_newsletter'] ?? null) ? $siteData['resume_newsletter'] : [];
$newsletterSubscribers = is_array($siteData['newsletter_abonnements_admin'] ?? null) ? $siteData['newsletter_abonnements_admin'] : [];
$newsletterSends = is_array($siteData['newsletter_envois_admin'] ?? null) ? $siteData['newsletter_envois_admin'] : [];
$horairesClub = is_array($siteData['horaires_club'] ?? null) ? $siteData['horaires_club'] : [];
$itemsHorairesClub = is_array($horairesClub['items'] ?? null) ? $horairesClub['items'] : [];
$blocsConstructeurAccueil = is_array($siteData['constructeur_accueil_blocs'] ?? null) ? $siteData['constructeur_accueil_blocs'] : [];
$courseDocumentsParRubrique = is_array($siteData['documents_cours_par_rubrique'] ?? null) ? $siteData['documents_cours_par_rubrique'] : [];
$coursePeutGererDocuments = (bool) ($siteData['peut_gerer_documents_cours'] ?? false);
$shopProducts = is_array($siteData['all_shop_products'] ?? null) ? $siteData['all_shop_products'] : [];
$shopHelloAssoLink = (string) ($siteData['lien_helloasso_boutique'] ?? \App\Repositories\ParametreSiteRepository::LIEN_HELLOASSO_PAR_DEFAUT);
$bureauSection = is_array($siteData['bureau_section'] ?? null) ? $siteData['bureau_section'] : [];
$bureauMembers = is_array($siteData['all_bureau_members'] ?? null) ? $siteData['all_bureau_members'] : [];
$shopCategories = \App\Repositories\BoutiqueProduitRepository::CATEGORIES;
$shopPublics = \App\Repositories\BoutiqueProduitRepository::PUBLICS;
$shopModesVente = \App\Repositories\BoutiqueProduitRepository::MODES_VENTE;
$courseRubriqueLabels = \App\Repositories\CoursDocumentRepository::RUBRIQUES;
$courseAncresRubriques = [
    'livrets' => 'cours-bibliotheque-livrets',
    'livret_a' => 'cours-livret-a',
    'livret_b' => 'cours-livret-b',
    'livret_c' => 'cours-livret-c',
    'livret_d' => 'cours-livret-d',
    'livret_e' => 'cours-livret-e',
    'cours' => 'cours-cours',
    'methodologie' => 'cours-methodologie',
    'strategie' => 'cours-strategie',
];
$courseRubriqueConfigs = [
    ['rubrique' => 'livrets', 'ancre' => 'cours-bibliotheque-livrets', 'badge' => 'Livrets', 'titre' => 'Bibliotheque des livrets', 'texte' => 'Documents communs relies aux livrets et ressources partagees.'],
    ['rubrique' => 'livret_a', 'ancre' => 'cours-livret-a', 'badge' => 'Livret A', 'titre' => 'Livret A', 'texte' => 'Bases du jeu et premiers reperes pour debuter.'],
    ['rubrique' => 'livret_b', 'ancre' => 'cours-livret-b', 'badge' => 'Livret B', 'titre' => 'Livret B', 'texte' => 'Premiers automatismes et securite des pieces.'],
    ['rubrique' => 'livret_c', 'ancre' => 'cours-livret-c', 'badge' => 'Livret C', 'titre' => 'Livret C', 'texte' => 'Milieu de jeu, plans simples et coordination.'],
    ['rubrique' => 'livret_d', 'ancre' => 'cours-livret-d', 'badge' => 'Livret D', 'titre' => 'Livret D', 'texte' => 'Approfondissement et evaluation de positions.'],
    ['rubrique' => 'livret_e', 'ancre' => 'cours-livret-e', 'badge' => 'Livret E', 'titre' => 'Livret E', 'texte' => 'Consolidation, finales et preparation competition.'],
    ['rubrique' => 'cours', 'ancre' => 'cours-cours', 'badge' => 'Cours', 'titre' => 'Cours et seances', 'texte' => 'Supports de seance, rappels et documents remis pendant les cours.'],
    ['rubrique' => 'methodologie', 'ancre' => 'cours-methodologie', 'badge' => 'Methodologie', 'titre' => 'Methodologie', 'texte' => 'Documents de methode, structure de travail et routines.'],
    ['rubrique' => 'strategie', 'ancre' => 'cours-strategie', 'badge' => 'Strategie', 'titre' => 'Strategie', 'texte' => 'Plans de jeu, themes strategiques et progression avancee.'],
];
$lignesHorairesAdmin = $itemsHorairesClub;
$adminTabs = [
    'newsletter' => 'Newsletter',
    'pilotage' => 'Pilotage',
    'trafic' => 'Trafic',
    'horaires' => 'Horaires',
    'comptes' => 'Comptes',
    'cours' => 'Cours',
    'boutique' => 'Boutique',
    'contenus' => 'Contenus',
];

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

<nav class="admin-tab-nav reveal reveal-2" aria-label="Rubriques du tableau de bord admin" data-admin-tabs>
    <?php foreach ($adminTabs as $adminTabKey => $adminTabLabel): ?>
        <button
            type="button"
            class="admin-tab-button<?= $adminTabKey === 'newsletter' ? ' is-active' : '' ?>"
            data-admin-tab-trigger="<?= e($adminTabKey) ?>"
            aria-pressed="<?= $adminTabKey === 'newsletter' ? 'true' : 'false' ?>"
            tabindex="<?= $adminTabKey === 'newsletter' ? '0' : '-1' ?>"
        >
            <?= e($adminTabLabel) ?>
        </button>
    <?php endforeach; ?>
</nav>

<section id="admin-newsletter" class="section-block reveal reveal-2 admin-tab-panel" data-admin-tab-panel="newsletter">
    <div class="section-head">
        <p class="eyebrow">Newsletter</p>
        <h2>Suivre les abonnés et les envois.</h2>
        <p>Cette zone centralise la liste des emails inscrits, les désabonnements et les derniers messages envoyés.</p>
    </div>

    <div class="admin-summary-grid">
        <article class="info-card">
            <p class="card-tag">Abonnés</p>
            <span class="metric-value"><?= e((string) ($newsletterSummary['abonnes_total'] ?? 0)) ?></span>
            <h3>Total en base</h3>
        </article>
        <article class="info-card">
            <p class="card-tag">Actifs</p>
            <span class="metric-value"><?= e((string) ($newsletterSummary['abonnes_actifs'] ?? 0)) ?></span>
            <h3>Reçoivent les emails</h3>
        </article>
        <article class="info-card">
            <p class="card-tag">Désabonnés</p>
            <span class="metric-value"><?= e((string) ($newsletterSummary['abonnes_desabonnes'] ?? 0)) ?></span>
            <h3>Ont retiré leur consentement</h3>
        </article>
        <article class="info-card">
            <p class="card-tag">Envois</p>
            <span class="metric-value"><?= e((string) ($newsletterSummary['envois_total'] ?? 0)) ?></span>
            <h3>Historique conserve</h3>
        </article>
    </div>

    <div class="split-grid">
        <article class="panel">
            <div class="section-head section-head--compact">
                <p class="eyebrow">Abonnés</p>
                <h2>Qui reçoit la newsletter ?</h2>
            </div>

            <div class="admin-list">
                <?php if ($newsletterSubscribers === []): ?>
                    <div class="empty-state">
                        <p class="card-tag">Aucun email</p>
                        <h3>La newsletter n'a encore aucun inscrit.</h3>
                    </div>
                <?php else: ?>
                    <?php foreach ($newsletterSubscribers as $abonneNewsletter): ?>
                        <article class="info-card admin-card">
                            <p class="card-tag"><?= e((string) ($abonneNewsletter['statut_libelle'] ?? $abonneNewsletter['statut'] ?? 'Actif')) ?></p>
                            <h3><?= e((string) ($abonneNewsletter['courriel'] ?? '')) ?></h3>
                            <p>Source : <?= e((string) ($abonneNewsletter['source_inscription'] ?? 'footer')) ?></p>
                            <p class="card-subtitle">Inscrit le : <?= e((string) ($abonneNewsletter['cree_le'] ?? '')) ?></p>
                            <?php if (($abonneNewsletter['desabonne_le'] ?? '') !== ''): ?>
                                <p class="card-subtitle">Désabonné le : <?= e((string) $abonneNewsletter['desabonne_le']) ?></p>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </article>

        <article class="panel">
            <div class="section-head section-head--compact">
                <p class="eyebrow">Derniers envois</p>
                <h2>Ce qui est parti récemment</h2>
            </div>

            <div class="admin-list">
                <?php if ($newsletterSends === []): ?>
                    <div class="empty-state">
                        <p class="card-tag">Aucun envoi</p>
                        <h3>Aucun email newsletter n'a encore été journalisé.</h3>
                    </div>
                <?php else: ?>
                    <?php foreach ($newsletterSends as $envoiNewsletter): ?>
                        <article class="info-card admin-card">
                            <p class="card-tag"><?= e((string) ($envoiNewsletter['type_evenement_libelle'] ?? $envoiNewsletter['code_type_evenement'] ?? '')) ?></p>
                            <h3><?= e((string) ($envoiNewsletter['titre_evenement'] ?? '')) ?></h3>
                            <p><?= e((string) ($envoiNewsletter['courriel'] ?? '')) ?></p>
                            <p class="card-subtitle"><?= e((string) ($envoiNewsletter['statut_envoi_libelle'] ?? $envoiNewsletter['code_statut_envoi'] ?? '')) ?> - <?= e((string) ($envoiNewsletter['envoye_le'] ?? '')) ?></p>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </article>
    </div>
</section>

<section id="admin-newsletter-boutique" class="section-block reveal reveal-6 admin-tab-panel" data-admin-tab-panel="newsletter">
    <div class="section-head">
        <p class="eyebrow">Newsletter</p>
        <h2>Informer les abonnés d'une nouveauté boutique.</h2>
        <p>Ce bouton envoie une actualité email aux personnes inscrites à la newsletter lorsque le club ajoute un nouvel objet ou une information boutique.</p>
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

        <button type="submit" class="button button-primary">Envoyer l'actualité boutique</button>
    </form>
</section>

<section class="section-block reveal reveal-3 admin-tab-panel" data-admin-tab-panel="pilotage">
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

<section id="admin-constructeur" class="section-block reveal reveal-3 admin-tab-panel" data-admin-tab-panel="pilotage">
    <div class="section-head">
        <p class="eyebrow">Constructeur</p>
        <h2>Organiser l'accueil avec des blocs interchangeables.</h2>
        <p>Les blocs verrouillés restent à leur place. Les autres peuvent être déplacés ou masqués sans toucher au code.</p>
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
                                <small class="form-helper">Ce bloc reste visible et ne peut pas être déplacé.</small>
                            <?php else: ?>
                                <small class="form-helper">Décoche cette case pour masquer temporairement ce bloc.</small>
                            <?php endif; ?>
                        </label>

                        <?php if ($codeBlocConstructeur === 'mot_du_club'): ?>
                            <label class="form-group">
                                <span>Titre du bloc</span>
                                <input
                                    type="text"
                                    name="titre_bloc[<?= e($codeBlocConstructeur) ?>]"
                                    value="<?= e((string) ($blocConstructeur['titre_personnalise'] ?? 'Présentation')) ?>"
                                    maxlength="160"
                                >
                                <small class="form-helper">Ce titre s'affiche sur la carte de présentation de l'accueil.</small>
                            </label>

                            <label class="form-group">
                                <span>Texte du bloc</span>
                                <textarea
                                    name="contenu_bloc[<?= e($codeBlocConstructeur) ?>]"
                                    rows="8"
                                ><?= e((string) ($blocConstructeur['contenu_personnalise'] ?? '')) ?></textarea>
                                <small class="form-helper">Tu peux modifier ici le texte de présentation visible publiquement sur l'accueil.</small>
                            </label>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <button type="submit" class="button button-primary">Enregistrer le constructeur</button>
    </form>
</section>

<section class="section-block reveal reveal-3 admin-tab-panel" data-admin-tab-panel="trafic">
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

<section id="admin-horaires-club" class="section-block reveal reveal-4 admin-tab-panel" data-admin-tab-panel="horaires">
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

<section class="section-block reveal reveal-4 admin-tab-panel" data-admin-tab-panel="comptes">
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
                            <option value="connecte"<?= ($user['role'] ?? '') === 'connecte' ? ' selected' : '' ?>>Connecté</option>
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

                    <button type="submit" class="button button-primary">Mettre à jour</button>
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
                                <option value="adherent">Adhérent</option>
                                <option value="connecte">Compte connecté</option>
                            </select>
                        </label>

                        <button type="submit" class="button button-secondary">Transférer le rôle admin</button>
                    </form>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section id="admin-cours" class="section-block reveal reveal-5 admin-tab-panel" data-admin-tab-panel="cours">
    <div class="section-head">
        <p class="eyebrow">Cours</p>
        <h2>Gerer les PDF de cours.</h2>
        <p>Tout le classement pedagogique peut etre pilote depuis ici : ajout, modification, suppression et reorganisation par rubrique.</p>
    </div>

    <div class="admin-course-sections">
        <?php
        $courseJetonCsrf = (string) ($siteData['jeton_csrf'] ?? '');
        $pageCourante = 'admin';
        ?>
        <?php foreach ($courseRubriqueConfigs as $courseRubriqueConfig): ?>
            <?php require resource_path('views/pages/partials/cours-rubrique.blade.php'); ?>
        <?php endforeach; ?>
    </div>
</section>

<section id="admin-boutique" class="section-block reveal reveal-5 admin-tab-panel" data-admin-tab-panel="boutique">
    <div class="section-head">
        <p class="eyebrow">Boutique</p>
        <h2>Gerer le catalogue de la boutique.</h2>
        <p>Ajoute, modifie ou supprime les produits et formules d'adhesion visibles dans la boutique publique.</p>
    </div>

    <div class="split-grid">
        <article class="panel">
            <div class="section-head section-head--compact">
                <p class="eyebrow">Paiement externe</p>
                <h2>Lien HelloAsso unique</h2>
                <p>Tous les produits de la boutique utilisent ce meme lien externe. Le site ne gere plus de paiement direct.</p>
            </div>

            <form method="post" action="<?= e(url_route('admin')) ?>#admin-boutique" class="admin-form">
                <input type="hidden" name="action" value="mettre_a_jour_lien_helloasso_boutique">
                <input type="hidden" name="_token" value="<?= e($siteData['jeton_csrf']) ?>">
                <input type="hidden" name="jeton_csrf" value="<?= e($siteData['jeton_csrf']) ?>">

                <label class="form-group">
                    <span>URL HelloAsso de la boutique</span>
                    <input
                        type="url"
                        name="lien_helloasso_boutique"
                        value="<?= e($shopHelloAssoLink) ?>"
                        maxlength="2000"
                        placeholder="https://www.helloasso.com/..."
                        required
                    >
                    <small class="form-helper">Tous les boutons de la boutique publique utiliseront ce lien.</small>
                </label>

                <button type="submit" class="button button-secondary">Enregistrer le lien HelloAsso</button>
            </form>

            <div class="section-head section-head--compact">
                <p class="eyebrow">Nouveau produit</p>
                <h2>Ajouter un produit</h2>
            </div>

            <form method="post" action="<?= e(url_route('admin')) ?>#admin-boutique" class="admin-form">
                <input type="hidden" name="action" value="ajouter_produit_boutique">
                <input type="hidden" name="_token" value="<?= e($siteData['jeton_csrf']) ?>">
                <input type="hidden" name="jeton_csrf" value="<?= e($siteData['jeton_csrf']) ?>">

                <div class="admin-shop-form-grid">
                    <label class="form-group">
                        <span>Reference</span>
                        <input type="text" name="reference_produit_boutique" maxlength="80" placeholder="TEXT-001" required>
                    </label>

                    <label class="form-group">
                        <span>Nom du produit</span>
                        <input type="text" name="titre_produit_boutique" maxlength="160" required>
                    </label>

                    <label class="form-group">
                        <span>Categorie</span>
                        <select name="categorie_produit_boutique" required>
                            <?php foreach ($shopCategories as $shopCategoryCode => $shopCategoryLabel): ?>
                                <option value="<?= e($shopCategoryCode) ?>"><?= e($shopCategoryLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label class="form-group">
                        <span>Public cible</span>
                        <select name="public_produit_boutique" required>
                            <?php foreach ($shopPublics as $shopPublicCode => $shopPublicLabel): ?>
                                <option value="<?= e($shopPublicCode) ?>"><?= e($shopPublicLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label class="form-group">
                        <span>Prix en euros</span>
                        <input type="number" name="prix_produit_boutique" min="0" max="10000" step="1" value="0" required>
                    </label>

                    <label class="form-group">
                        <span>Mode de vente</span>
                        <select name="mode_vente_produit_boutique" required>
                            <?php foreach ($shopModesVente as $shopModeCode => $shopModeLabel): ?>
                                <option value="<?= e($shopModeCode) ?>"><?= e($shopModeLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label class="form-group">
                        <span>Badge</span>
                        <input type="text" name="badge_produit_boutique" maxlength="80" placeholder="Nouveau">
                    </label>

                    <label class="form-group">
                        <span>Ordre d'affichage</span>
                        <input type="number" name="ordre_affichage_produit_boutique" min="1" max="999" value="1" required>
                    </label>
                </div>

                <label class="form-group">
                    <span>Description principale</span>
                    <textarea name="description_produit_boutique" rows="4" maxlength="2000" required></textarea>
                </label>

                <label class="form-group">
                    <span>Resume court</span>
                    <textarea name="resume_produit_boutique" rows="3" maxlength="280"></textarea>
                </label>

                <label class="form-group">
                    <span>Avantages (une ligne par avantage)</span>
                    <textarea name="avantages_produit_boutique" rows="4" maxlength="800"></textarea>
                </label>

                <div class="admin-shop-toggle-grid">
                    <label class="checkbox-inline">
                        <input type="checkbox" name="stock_produit_boutique" value="1" checked>
                        <span>Produit disponible ou reservable</span>
                    </label>

                    <label class="checkbox-inline">
                        <input type="checkbox" name="visible_produit_boutique" value="1" checked>
                        <span>Visible dans la boutique publique</span>
                    </label>
                </div>

                <button type="submit" class="button button-primary">Ajouter un produit</button>
            </form>
        </article>

        <article class="panel panel-contrast">
            <div class="section-head section-head--compact">
                <p class="eyebrow">Catalogue actuel</p>
                <h2>Produits deja en ligne</h2>
            </div>

            <div class="admin-list">
                <?php if ($shopProducts === []): ?>
                    <div class="empty-state empty-state--contrast">
                        <p class="card-tag">Aucun produit</p>
                        <h3>La boutique est vide pour le moment.</h3>
                        <p>Ajoute ici le premier article ou la premiere formule d'adhesion.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($shopProducts as $shopProduct): ?>
                        <?php $shopAdvantages = is_array($shopProduct['avantages'] ?? null) ? $shopProduct['avantages'] : []; ?>
                        <article class="info-card admin-card admin-card--contrast admin-shop-card">
                            <div class="shop-order-head">
                                <div>
                                    <p class="card-tag"><?= e((string) ($shopProduct['categorie_label'] ?? 'Produit')) ?></p>
                                    <p class="shop-card-reference"><?= e((string) ($shopProduct['reference'] ?? '')) ?></p>
                                </div>
                                <span class="shop-card-badge"><?= e((string) ($shopProduct['badge'] ?? 'Club')) ?></span>
                            </div>

                            <h3><?= e((string) ($shopProduct['titre'] ?? 'Produit')) ?></h3>
                            <p><?= e((string) ($shopProduct['texte'] ?? '')) ?></p>
                            <p class="card-subtitle">
                                <?= e((string) ($shopProduct['public_label'] ?? 'Tous publics')) ?>
                                - <?= e((string) ($shopProduct['prix_euros'] ?? 0)) ?> EUR
                                - <?= e((string) ($shopProduct['stock_label'] ?? 'Disponible')) ?>
                            </p>

                            <?php if (trim((string) ($shopProduct['resume'] ?? '')) !== ''): ?>
                                <p class="shop-card-summary"><?= e((string) ($shopProduct['resume'] ?? '')) ?></p>
                            <?php endif; ?>

                            <?php if ($shopAdvantages !== []): ?>
                                <ul class="shop-feature-list">
                                    <?php foreach ($shopAdvantages as $shopAdvantage): ?>
                                        <li><?= e((string) $shopAdvantage) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>

                            <details class="admin-shop-edit">
                                <summary class="button button-secondary">Modifier</summary>

                                <form method="post" action="<?= e(url_route('admin')) ?>#admin-boutique" class="admin-form admin-shop-edit-form">
                                    <input type="hidden" name="action" value="modifier_produit_boutique">
                                    <input type="hidden" name="_token" value="<?= e($siteData['jeton_csrf']) ?>">
                                    <input type="hidden" name="jeton_csrf" value="<?= e($siteData['jeton_csrf']) ?>">
                                    <input type="hidden" name="identifiant_produit_boutique" value="<?= e((string) ($shopProduct['identifiant_produit'] ?? '')) ?>">

                                    <div class="admin-shop-form-grid">
                                        <label class="form-group">
                                            <span>Reference</span>
                                            <input type="text" name="reference_produit_boutique" maxlength="80" value="<?= e((string) ($shopProduct['reference'] ?? '')) ?>" required>
                                        </label>

                                        <label class="form-group">
                                            <span>Nom du produit</span>
                                            <input type="text" name="titre_produit_boutique" maxlength="160" value="<?= e((string) ($shopProduct['titre'] ?? '')) ?>" required>
                                        </label>

                                        <label class="form-group">
                                            <span>Categorie</span>
                                            <select name="categorie_produit_boutique" required>
                                                <?php foreach ($shopCategories as $shopCategoryCode => $shopCategoryLabel): ?>
                                                    <option value="<?= e($shopCategoryCode) ?>"<?= $shopCategoryCode === (string) ($shopProduct['categorie'] ?? '') ? ' selected' : '' ?>>
                                                        <?= e($shopCategoryLabel) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>

                                        <label class="form-group">
                                            <span>Public cible</span>
                                            <select name="public_produit_boutique" required>
                                                <?php foreach ($shopPublics as $shopPublicCode => $shopPublicLabel): ?>
                                                    <option value="<?= e($shopPublicCode) ?>"<?= $shopPublicCode === (string) ($shopProduct['public_cible'] ?? '') ? ' selected' : '' ?>>
                                                        <?= e($shopPublicLabel) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>

                                        <label class="form-group">
                                            <span>Prix en euros</span>
                                            <input type="number" name="prix_produit_boutique" min="0" max="10000" step="1" value="<?= e((string) ($shopProduct['prix_euros'] ?? 0)) ?>" required>
                                        </label>

                                        <label class="form-group">
                                            <span>Mode de vente</span>
                                            <select name="mode_vente_produit_boutique" required>
                                                <?php foreach ($shopModesVente as $shopModeCode => $shopModeLabel): ?>
                                                    <option value="<?= e($shopModeCode) ?>"<?= $shopModeCode === (string) ($shopProduct['mode_vente'] ?? '') ? ' selected' : '' ?>>
                                                        <?= e($shopModeLabel) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>

                                        <label class="form-group">
                                            <span>Badge</span>
                                            <input type="text" name="badge_produit_boutique" maxlength="80" value="<?= e((string) ($shopProduct['badge'] ?? '')) ?>">
                                        </label>

                                        <label class="form-group">
                                            <span>Ordre d'affichage</span>
                                            <input type="number" name="ordre_affichage_produit_boutique" min="1" max="999" value="<?= e((string) ($shopProduct['ordre_affichage'] ?? 1)) ?>" required>
                                        </label>
                                    </div>

                                    <label class="form-group">
                                        <span>Description principale</span>
                                        <textarea name="description_produit_boutique" rows="4" maxlength="2000" required><?= e((string) ($shopProduct['texte'] ?? '')) ?></textarea>
                                    </label>

                                    <label class="form-group">
                                        <span>Resume court</span>
                                        <textarea name="resume_produit_boutique" rows="3" maxlength="280"><?= e((string) ($shopProduct['resume'] ?? '')) ?></textarea>
                                    </label>

                                    <label class="form-group">
                                        <span>Avantages (une ligne par avantage)</span>
                                        <textarea name="avantages_produit_boutique" rows="4" maxlength="800"><?= e(implode("\n", $shopAdvantages)) ?></textarea>
                                    </label>

                                    <div class="admin-shop-toggle-grid">
                                        <label class="checkbox-inline">
                                            <input type="checkbox" name="stock_produit_boutique" value="1"<?= (bool) ($shopProduct['est_en_stock'] ?? false) ? ' checked' : '' ?>>
                                            <span>Produit disponible ou reservable</span>
                                        </label>

                                        <label class="checkbox-inline">
                                            <input type="checkbox" name="visible_produit_boutique" value="1"<?= (bool) ($shopProduct['est_actif'] ?? false) ? ' checked' : '' ?>>
                                            <span>Visible dans la boutique publique</span>
                                        </label>
                                    </div>

                                    <button type="submit" class="button button-primary">Enregistrer les changements</button>
                                </form>
                            </details>

                            <form
                                method="post"
                                action="<?= e(url_route('admin')) ?>#admin-boutique"
                                class="admin-form admin-inline-form"
                                data-confirm-delete
                                data-confirm-message="Supprimer definitivement ce produit de la boutique ?"
                            >
                                <input type="hidden" name="action" value="supprimer_produit_boutique">
                                <input type="hidden" name="_token" value="<?= e($siteData['jeton_csrf']) ?>">
                                <input type="hidden" name="jeton_csrf" value="<?= e($siteData['jeton_csrf']) ?>">
                                <input type="hidden" name="identifiant_produit_boutique" value="<?= e((string) ($shopProduct['identifiant_produit'] ?? '')) ?>">
                                <button type="submit" class="button button-secondary button-danger">Supprimer</button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </article>
    </div>
</section>

<section id="admin-bureau-club" class="section-block reveal reveal-5 admin-tab-panel" data-admin-tab-panel="contenus">
    <div class="section-head">
        <p class="eyebrow">Bureau du club</p>
        <h2>Gerer les cartes du bureau visibles sur l'accueil.</h2>
        <p>Tu peux modifier tous les textes, ajouter une nouvelle carte ou supprimer une personne sans toucher au code.</p>
    </div>

    <div class="split-grid">
        <article class="panel">
            <div class="section-head section-head--compact">
                <p class="eyebrow">Textes generaux</p>
                <h2>Modifier le bloc bureau.</h2>
                <p>Ces textes apparaissent juste au-dessus des cartes du bureau sur la page d'accueil.</p>
            </div>

            <form method="post" action="<?= e(url_route('admin')) ?>#admin-bureau-club" class="admin-form">
                <input type="hidden" name="action" value="mettre_a_jour_textes_bureau">
                <input type="hidden" name="_token" value="<?= e($siteData['jeton_csrf']) ?>">
                <input type="hidden" name="jeton_csrf" value="<?= e($siteData['jeton_csrf']) ?>">

                <label class="form-group">
                    <span>Surtitre</span>
                    <input
                        type="text"
                        name="bureau_surtitre"
                        maxlength="80"
                        value="<?= e((string) ($bureauSection['surtitre'] ?? $bureauSection['eyebrow'] ?? '')) ?>"
                        placeholder="Bureau du club"
                    >
                </label>

                <label class="form-group">
                    <span>Titre principal</span>
                    <input
                        type="text"
                        name="bureau_titre"
                        maxlength="180"
                        value="<?= e((string) ($bureauSection['titre'] ?? $bureauSection['title'] ?? '')) ?>"
                        placeholder="Les membres du bureau des échecs."
                    >
                </label>

                <label class="form-group">
                    <span>Texte d'introduction</span>
                    <textarea
                        name="bureau_description"
                        rows="4"
                        maxlength="600"
                        placeholder="Presentation du bloc bureau"
                    ><?= e((string) ($bureauSection['description'] ?? '')) ?></textarea>
                </label>

                <button type="submit" class="button button-primary">Enregistrer les textes</button>
            </form>
        </article>

        <article class="panel">
            <div class="section-head section-head--compact">
                <p class="eyebrow">Nouvelle carte</p>
                <h2>Ajouter un membre du bureau.</h2>
                <p>Chaque carte peut contenir un prenom, un nom, un role, une description et un lien photo optionnel.</p>
            </div>

            <form method="post" action="<?= e(url_route('admin')) ?>#admin-bureau-club" class="admin-form">
                <input type="hidden" name="action" value="ajouter_membre_bureau">
                <input type="hidden" name="_token" value="<?= e($siteData['jeton_csrf']) ?>">
                <input type="hidden" name="jeton_csrf" value="<?= e($siteData['jeton_csrf']) ?>">

                <div class="admin-schedule-settings">
                    <label class="form-group">
                        <span>Prenom</span>
                        <input type="text" name="prenom_membre_bureau" maxlength="100" placeholder="Claire">
                    </label>

                    <label class="form-group">
                        <span>Nom</span>
                        <input type="text" name="nom_membre_bureau" maxlength="100" placeholder="DUPONT">
                    </label>

                    <label class="form-group">
                        <span>Role affiche</span>
                        <input type="text" name="role_membre_bureau" maxlength="160" placeholder="Secretaire">
                    </label>

                    <label class="form-group">
                        <span>Position</span>
                        <input type="number" name="ordre_affichage_membre_bureau" min="1" max="999" value="4" required>
                    </label>
                </div>

                <label class="form-group">
                    <span>Description</span>
                    <textarea name="description_membre_bureau" rows="4" maxlength="1200" placeholder="Presentation de la personne"></textarea>
                </label>

                <label class="form-group">
                    <span>Lien photo optionnel</span>
                    <input type="text" name="photo_membre_bureau" maxlength="2048" placeholder="assets/media/... ou https://...">
                </label>

                <label class="form-group">
                    <span class="checkbox-inline">
                        <input type="checkbox" name="visible_membre_bureau" value="1" checked>
                        <span>Afficher cette carte publiquement</span>
                    </span>
                </label>

                <button type="submit" class="button button-secondary">Ajouter la carte</button>
            </form>
        </article>
    </div>

    <div class="admin-list">
        <?php if ($bureauMembers === []): ?>
            <div class="empty-state">
                <p class="card-tag">Aucun membre</p>
                <h3>Aucune carte bureau n'est enregistree pour le moment.</h3>
            </div>
        <?php else: ?>
            <?php foreach ($bureauMembers as $bureauMember): ?>
                <article class="info-card admin-card">
                    <p class="card-tag"><?= e((string) ($bureauMember['role'] ?? 'Bureau')) ?></p>
                    <h3><?= e((string) ($bureauMember['nom_complet'] ?? 'Membre du bureau')) ?></h3>
                    <p><?= e((string) ($bureauMember['description'] ?? '')) ?></p>

                    <form method="post" action="<?= e(url_route('admin')) ?>#admin-bureau-club" class="admin-form">
                        <input type="hidden" name="action" value="modifier_membre_bureau">
                        <input type="hidden" name="_token" value="<?= e($siteData['jeton_csrf']) ?>">
                        <input type="hidden" name="jeton_csrf" value="<?= e($siteData['jeton_csrf']) ?>">
                        <input type="hidden" name="identifiant_membre_bureau" value="<?= e((string) ($bureauMember['identifiant_membre_bureau'] ?? '')) ?>">

                        <div class="admin-schedule-settings">
                            <label class="form-group">
                                <span>Prenom</span>
                                <input type="text" name="prenom_membre_bureau" maxlength="100" value="<?= e((string) ($bureauMember['prenom'] ?? '')) ?>">
                            </label>

                            <label class="form-group">
                                <span>Nom</span>
                                <input type="text" name="nom_membre_bureau" maxlength="100" value="<?= e((string) ($bureauMember['nom'] ?? '')) ?>">
                            </label>

                            <label class="form-group">
                                <span>Role affiche</span>
                                <input type="text" name="role_membre_bureau" maxlength="160" value="<?= e((string) ($bureauMember['role'] ?? '')) ?>">
                            </label>

                            <label class="form-group">
                                <span>Position</span>
                                <input type="number" name="ordre_affichage_membre_bureau" min="1" max="999" value="<?= e((string) ($bureauMember['ordre_affichage'] ?? 1)) ?>" required>
                            </label>
                        </div>

                        <label class="form-group">
                            <span>Description</span>
                            <textarea name="description_membre_bureau" rows="4" maxlength="1200"><?= e((string) ($bureauMember['description'] ?? '')) ?></textarea>
                        </label>

                        <label class="form-group">
                            <span>Lien photo optionnel</span>
                            <input type="text" name="photo_membre_bureau" maxlength="2048" value="<?= e((string) ($bureauMember['photo'] ?? '')) ?>">
                        </label>

                        <label class="form-group">
                            <span class="checkbox-inline">
                                <input type="checkbox" name="visible_membre_bureau" value="1"<?= (bool) ($bureauMember['est_actif'] ?? false) ? ' checked' : '' ?>>
                                <span>Carte visible publiquement</span>
                            </span>
                        </label>

                        <button type="submit" class="button button-primary">Enregistrer les changements</button>
                    </form>

                    <form
                        method="post"
                        action="<?= e(url_route('admin')) ?>#admin-bureau-club"
                        class="admin-form admin-inline-form"
                        data-confirm-delete
                        data-confirm-message="Supprimer definitivement cette carte du bureau ?"
                    >
                        <input type="hidden" name="action" value="supprimer_membre_bureau">
                        <input type="hidden" name="_token" value="<?= e($siteData['jeton_csrf']) ?>">
                        <input type="hidden" name="jeton_csrf" value="<?= e($siteData['jeton_csrf']) ?>">
                        <input type="hidden" name="identifiant_membre_bureau" value="<?= e((string) ($bureauMember['identifiant_membre_bureau'] ?? '')) ?>">
                        <button type="submit" class="button button-secondary button-danger">Supprimer</button>
                    </form>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<section class="split-grid reveal reveal-5 admin-tab-panel" data-admin-tab-panel="contenus">
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

<section id="admin-boutique-commandes" class="section-block reveal reveal-6 admin-tab-panel" data-admin-tab-panel="boutique">
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
