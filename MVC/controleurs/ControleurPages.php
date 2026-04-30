<?php

declare(strict_types=1);

final class ControleurPages
{
    public function __construct(
        private ModeleSite $modeleSite,
        private DepotUtilisateurs $depotUtilisateurs,
        private DepotArticles $depotArticles,
        private DepotMedias $depotMedias,
        private DepotCommandes $depotCommandes,
        private ServiceChessCom $serviceChessCom,
        private array $messagesFlash,
        private array $etatFormulaire
    ) {
    }

    public function afficher(string $segment): string
    {
        $utilisateurCourant = $this->depotUtilisateurs->trouverParIdentifiant(
            isset($_SESSION['identifiant_utilisateur']) ? (string) $_SESSION['identifiant_utilisateur'] : null
        );

        if ($utilisateurCourant !== null && ($utilisateurCourant['statut_compte'] ?? '') !== DepotUtilisateurs::STATUT_COMPTE_ACTIF) {
            unset($_SESSION['identifiant_utilisateur']);
            ajouter_message_flash('error', 'Votre compte n est plus actif. Merci de recontacter le club.');
            rediriger_vers(url_route('accueil'));
        }

        $this->autoriserAccesPage($segment, $utilisateurCourant);

        $donneesSite = $this->modeleSite->obtenirDonneesSite();
        $pages = $this->modeleSite->obtenirPages();
        $donneesAuthentification = $this->construireDonneesAuthentification($utilisateurCourant);

        $donneesSite['theme'] = theme_courant();
        $donneesSite['jeton_csrf'] = jeton_csrf();
        $donneesSite['messages_flash'] = $this->messagesFlash;
        $donneesSite['etat_formulaire'] = $this->etatFormulaire;
        $donneesSite['page_courante'] = $segment;
        $donneesSite['authentification'] = $donneesAuthentification;
        $donneesSite['navigation_principale'] = $this->filtrerNavigationPrincipale(
            $donneesSite['navigation_principale'] ?? [],
            $donneesAuthentification
        );
        $donneesSite['primary_nav'] = $donneesSite['navigation_principale'];
        $donneesSite['navigation_secondaire'] = $this->filtrerNavigationSecondaire(
            $donneesSite['navigation_secondaire'] ?? [],
            $donneesAuthentification
        );
        $donneesSite['secondary_nav'] = $donneesSite['navigation_secondaire'];
        $donneesSite['cartes_guide'] = $this->modeleSite->obtenirCartesGuide();
        $donneesSite['cartes_mediatheque'] = $this->modeleSite->obtenirCartesMediatheque();
        $donneesSite['cartes_boutique'] = $this->modeleSite->obtenirCartesBoutique();

        $articlesPublies = $this->depotArticles->trouverPublies();
        $mesArticles = $utilisateurCourant !== null
            ? $this->depotArticles->trouverParIdentifiantAuteur((string) $utilisateurCourant['identifiant'])
            : [];
        $mediasPublies = $this->depotMedias->trouverPublies();
        $mesMedias = $utilisateurCourant !== null
            ? $this->depotMedias->trouverParIdentifiantAuteur((string) $utilisateurCourant['identifiant'])
            : [];

        $donneesSite['articles_publies'] = $articlesPublies;
        $donneesSite['published_articles'] = $articlesPublies;
        $donneesSite['mes_articles'] = $mesArticles;
        $donneesSite['my_articles'] = $mesArticles;
        $donneesSite['medias_publies'] = $mediasPublies;
        $donneesSite['published_media'] = $mediasPublies;
        $donneesSite['mes_medias'] = $mesMedias;
        $donneesSite['my_media'] = $mesMedias;
        $donneesSite['tous_utilisateurs'] = $donneesAuthentification['est_admin'] ? $this->depotUtilisateurs->listerTous() : [];
        $donneesSite['all_users'] = $donneesSite['tous_utilisateurs'];
        $donneesSite['tous_articles'] = $donneesAuthentification['est_admin'] ? $this->depotArticles->listerTous() : [];
        $donneesSite['all_articles'] = $donneesSite['tous_articles'];
        $donneesSite['tous_medias'] = $donneesAuthentification['est_admin'] ? $this->depotMedias->listerTous() : [];
        $donneesSite['all_media'] = $donneesSite['tous_medias'];
        $donneesSite['commandes_membre'] = $utilisateurCourant !== null
            ? $this->depotCommandes->listerParIdentifiantUtilisateur((string) $utilisateurCourant['identifiant'])
            : [];
        $donneesSite['member_orders'] = $donneesSite['commandes_membre'];
        $donneesSite['toutes_commandes'] = $donneesAuthentification['est_admin'] ? $this->depotCommandes->listerToutes() : [];
        $donneesSite['all_orders'] = $donneesSite['toutes_commandes'];

        $donneesSite['chess_com'] = [
            'statut' => 'absent',
            'pseudo' => '',
            'message' => '',
            'classements' => [],
            'joueur' => null,
            'note_statistiques' => '',
            'date_recuperation_libelle' => '',
            'source_cache' => '',
            'status' => 'missing',
            'profile_url' => '',
            'player' => null,
            'ratings' => [],
            'stats_note' => '',
            'fetched_at_label' => '',
        ];

        if ($segment === 'profil' && $utilisateurCourant !== null) {
            $donneesSite['chess_com'] = $this->serviceChessCom->recupererInstantaneJoueur(
                (string) ($utilisateurCourant['pseudo_chess'] ?? '')
            );
        }

        $donneesPage = $pages[$segment] ?? null;

        if ($donneesPage === null && $segment === 'boutique' && isset($pages['merch'])) {
            $donneesPage = $pages['merch'];
        }

        if ($donneesPage === null) {
            http_response_code(404);
            $pageCourante = 'introuvable';
            $donneesPage = $this->modeleSite->obtenirDonneesIntrouvable();
            $titrePage = 'Page introuvable';
            $fichierVue = __DIR__ . '/../vues/pages/introuvable.php';
        } else {
            $donneesPage['titre'] = (string) ($donneesPage['titre'] ?? $donneesPage['title'] ?? '');
            $donneesPage['vue'] = (string) ($donneesPage['vue'] ?? $donneesPage['view'] ?? '');
            $donneesPage['description_meta'] = (string) ($donneesPage['description_meta'] ?? $donneesPage['meta_description'] ?? '');
            $donneesPage['titre_hero'] = (string) ($donneesPage['titre_hero'] ?? $donneesPage['hero_title'] ?? '');
            $donneesPage['texte_hero'] = (string) ($donneesPage['texte_hero'] ?? $donneesPage['hero_text'] ?? '');
            $donneesPage['note_hero'] = (string) ($donneesPage['note_hero'] ?? $donneesPage['hero_note'] ?? '');
            $pageCourante = $segment;
            $titrePage = $donneesPage['titre'] !== '' ? $donneesPage['titre'] : 'Page';
            $fichierVue = __DIR__ . '/../vues/pages/' . $donneesPage['vue'];
        }

        $metaTitre = $titrePage . ' | ' . $donneesSite['brand'];
        $descriptionMeta = (string) ($donneesPage['description_meta'] ?? $donneesPage['meta_description'] ?? $donneesSite['accroche'] ?? $donneesSite['tagline']);

        ob_start();
        require __DIR__ . '/../vues/mise-en-page.php';

        return (string) ob_get_clean();
    }

    private function autoriserAccesPage(string $segment, ?array $utilisateur): void
    {
        $estConnecte = $this->estUtilisateurActif($utilisateur);
        $estAdmin = $this->estAdmin($utilisateur);

        if (in_array($segment, ['guide', 'boutique', 'profil', 'parametres'], true) && !$estConnecte) {
            ajouter_message_flash('error', 'Connecte-toi pour acceder a cette page.');
            rediriger_vers(url_route('accueil'));
        }

        if ($segment === 'admin' && !$estAdmin) {
            ajouter_message_flash('error', 'Acces reserve a l administrateur du site.');
            rediriger_vers(url_route('accueil'));
        }
    }

    private function construireDonneesAuthentification(?array $utilisateur): array
    {
        if (!$this->estUtilisateurActif($utilisateur)) {
            return [
                'est_connecte' => false,
                'nom_affichage' => '',
                'utilisateur' => null,
                'is_authenticated' => false,
                'display_name' => '',
                'user' => null,
                'role' => 'visiteur',
                'role_label' => 'Visiteur',
                'est_admin' => false,
                'est_adherent' => false,
                'peut_voir_guides' => false,
                'peut_voir_boutique' => false,
                'peut_publier_articles' => false,
                'peut_soumettre_medias' => false,
            ];
        }

        $nomAffichage = trim((string) ($utilisateur['prenom'] ?? '') . ' ' . (string) ($utilisateur['nom'] ?? ''));
        $estAdmin = $this->estAdmin($utilisateur);
        $estAdherent = $this->estAdherent($utilisateur);
        $role = (string) ($utilisateur['role'] ?? DepotUtilisateurs::ROLE_CONNECTE);

        return [
            'est_connecte' => true,
            'nom_affichage' => $nomAffichage !== '' ? $nomAffichage : (string) ($utilisateur['courriel'] ?? ''),
            'utilisateur' => $utilisateur,
            'is_authenticated' => true,
            'display_name' => $nomAffichage !== '' ? $nomAffichage : (string) ($utilisateur['courriel'] ?? ''),
            'role' => $role,
            'role_label' => $this->libelleRole($role),
            'est_admin' => $estAdmin,
            'est_adherent' => $estAdherent,
            'peut_voir_guides' => true,
            'peut_voir_boutique' => true,
            'peut_publier_articles' => $estAdmin || $estAdherent,
            'peut_soumettre_medias' => $estAdmin || $estAdherent,
            'user' => [
                ...$utilisateur,
                'email' => $utilisateur['courriel'] ?? '',
                'first_name' => $utilisateur['prenom'] ?? '',
                'last_name' => $utilisateur['nom'] ?? '',
                'birth_date' => $utilisateur['date_naissance'] ?? '',
                'profile_description' => $utilisateur['description_profil'] ?? '',
                'chess_username' => $utilisateur['pseudo_chess'] ?? '',
                'role_label' => $this->libelleRole($role),
                'membership_label' => $this->libelleAdhesion((string) ($utilisateur['statut_adhesion'] ?? '')),
            ],
        ];
    }

    private function filtrerNavigationPrincipale(array $navigationPrincipale, array $auth): array
    {
        return array_values(
            array_filter(
                $navigationPrincipale,
                static function (array $element) use ($auth): bool {
                    $slug = (string) ($element['slug'] ?? '');

                    if (in_array($slug, ['guide', 'boutique'], true) && !($auth['est_connecte'] ?? false)) {
                        return false;
                    }

                    return true;
                }
            )
        );
    }

    private function filtrerNavigationSecondaire(array $navigationSecondaire, array $auth): array
    {
        if ($auth['est_admin'] ?? false) {
            $navigationSecondaire[] = ['slug' => 'admin', 'label' => 'Admin'];
        }

        return $navigationSecondaire;
    }

    private function estUtilisateurActif(?array $utilisateur): bool
    {
        return $utilisateur !== null
            && ($utilisateur['statut_compte'] ?? '') === DepotUtilisateurs::STATUT_COMPTE_ACTIF;
    }

    private function estAdmin(?array $utilisateur): bool
    {
        return $this->estUtilisateurActif($utilisateur)
            && ($utilisateur['role'] ?? '') === DepotUtilisateurs::ROLE_ADMIN;
    }

    private function estAdherent(?array $utilisateur): bool
    {
        return $this->estUtilisateurActif($utilisateur)
            && (
                ($utilisateur['role'] ?? '') === DepotUtilisateurs::ROLE_ADHERENT
                || ($utilisateur['statut_adhesion'] ?? '') === DepotUtilisateurs::STATUT_ADHESION_ACTIVE
            );
    }

    private function libelleRole(string $role): string
    {
        return match ($role) {
            DepotUtilisateurs::ROLE_ADMIN => 'Administrateur',
            DepotUtilisateurs::ROLE_ADHERENT => 'Adherent',
            DepotUtilisateurs::ROLE_CONNECTE => 'Compte connecte',
            default => 'Visiteur',
        };
    }

    private function libelleAdhesion(string $statutAdhesion): string
    {
        return match ($statutAdhesion) {
            DepotUtilisateurs::STATUT_ADHESION_ACTIVE => 'Adhesion active',
            default => 'Non adherent',
        };
    }
}
