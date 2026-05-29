<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use App\Repositories\ArticleRepository;
use App\Repositories\DammierRepository;
use App\Repositories\MediaRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ScheduleRepository;
use App\Repositories\UserRepository;
use App\Services\ChessComService;
use App\Services\GoogleReviewsService;

final class LegacyPageRenderer
{
    public function __construct(
        private SiteContent $siteContent,
        private UserRepository $userRepository,
        private ArticleRepository $articleRepository,
        private MediaRepository $mediaRepository,
        private OrderRepository $orderRepository,
        private DammierRepository $dammierRepository,
        private ScheduleRepository $scheduleRepository,
        private ChessComService $chessComService,
        private GoogleReviewsService $googleReviewsService,
        private array $messagesFlash,
        private array $formState
    ) {
    }

    public function afficher(string $segment): string
    {
        $currentUser = $this->userRepository->trouverParIdentifiant(
            isset($_SESSION['identifiant_utilisateur']) ? (string) $_SESSION['identifiant_utilisateur'] : null
        );

        if ($currentUser !== null && ($currentUser['statut_compte'] ?? '') !== User::STATUT_COMPTE_ACTIF) {
            unset($_SESSION['identifiant_utilisateur']);
            ajouter_message_flash('error', "Votre compte n'est plus actif. Merci de recontacter le club.");
            rediriger_vers(url_route('accueil'));
        }

        $this->autoriserAccesPage($segment, $currentUser);

        $siteData = $this->siteContent->obtenirDonneesSite();
        $pages = $this->siteContent->obtenirPages();
        $authData = $this->construireDonneesAuthentification($currentUser);

        $siteData['theme'] = theme_courant();
        $siteData['jeton_csrf'] = jeton_csrf();
        $siteData['messages_flash'] = $this->messagesFlash;
        $siteData['etat_formulaire'] = $this->formState;
        $siteData['page_courante'] = $segment;
        $siteData['authentification'] = $authData;
        $siteData['navigation_principale'] = $this->filtrerNavigationPrincipale(
            $siteData['navigation_principale'] ?? [],
            $authData
        );
        $siteData['primary_nav'] = $siteData['navigation_principale'];
        $siteData['navigation_secondaire'] = $this->filtrerNavigationSecondaire(
            $siteData['navigation_secondaire'] ?? [],
            $authData
        );
        $siteData['secondary_nav'] = $siteData['navigation_secondaire'];
        $siteData['cartes_guide'] = $this->siteContent->obtenirCartesGuide();
        $siteData['cartes_mediatheque'] = $this->siteContent->obtenirCartesMediatheque();
        $siteData['cartes_boutique'] = $this->siteContent->obtenirCartesBoutique();

        $clubSchedule = $this->scheduleRepository->obtenir();
        $siteData['horaires_club'] = $clubSchedule;
        $siteData['club_schedule'] = $clubSchedule;
        $siteData['resume_horaires_club'] = $this->scheduleRepository->resumerParJour();
        $siteData['club_schedule_summary'] = $siteData['resume_horaires_club'];
        $siteData['planning'] = $this->scheduleRepository->adapterPlanning();
        $siteData['schedule'] = $siteData['planning'];
        $siteData['google_reviews'] = $this->googleReviewsService->recupererAvisLieu(
            (string) ($siteData['club_google_reviews_cache_key'] ?? 'club'),
            (string) ($siteData['club_google_search_query'] ?? '')
        );

        $publishedArticles = $this->articleRepository->trouverPublies();
        $myArticles = $currentUser !== null
            ? $this->articleRepository->trouverParIdentifiantAuteur((string) $currentUser['identifiant'])
            : [];
        $publishedMedia = $this->mediaRepository->trouverPublies();
        $myMedia = $currentUser !== null
            ? $this->mediaRepository->trouverParIdentifiantAuteur((string) $currentUser['identifiant'])
            : [];

        $siteData['articles_publies'] = $publishedArticles;
        $siteData['published_articles'] = $publishedArticles;
        $siteData['mes_articles'] = $myArticles;
        $siteData['my_articles'] = $myArticles;
        $siteData['medias_publies'] = $publishedMedia;
        $siteData['published_media'] = $publishedMedia;
        $siteData['mes_medias'] = $myMedia;
        $siteData['my_media'] = $myMedia;
        $siteData['tous_utilisateurs'] = $authData['est_admin'] ? $this->userRepository->listerTous() : [];
        $siteData['all_users'] = $siteData['tous_utilisateurs'];
        $siteData['tous_articles'] = $authData['est_admin'] ? $this->articleRepository->listerTous() : [];
        $siteData['all_articles'] = $siteData['tous_articles'];
        $siteData['tous_medias'] = $authData['est_admin'] ? $this->mediaRepository->listerTous() : [];
        $siteData['all_media'] = $siteData['tous_medias'];
        $siteData['commandes_membre'] = $currentUser !== null
            ? $this->orderRepository->listerParIdentifiantUtilisateur((string) $currentUser['identifiant'])
            : [];
        $siteData['member_orders'] = $siteData['commandes_membre'];
        $siteData['toutes_commandes'] = $authData['est_admin'] ? $this->orderRepository->listerToutes() : [];
        $siteData['all_orders'] = $siteData['toutes_commandes'];

        $dammierPuzzle = $this->dammierRepository->obtenirPuzzleHebdomadaire();
        $dammierClassement = $this->dammierRepository->listerClassementHebdomadaire(
            (string) ($dammierPuzzle['dammier_week_key'] ?? ''),
            (string) ($dammierPuzzle['dammier_id'] ?? '')
        );
        $siteData['dammier_puzzle'] = $dammierPuzzle;
        $siteData['dammier_classement'] = $dammierClassement;
        $siteData['dammier_peut_voir_classement'] = (bool) ($authData['est_connecte'] ?? false);

        $siteData['chess_com'] = [
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

        if ($segment === 'profil' && $currentUser !== null) {
            $siteData['chess_com'] = $this->chessComService->recupererInstantaneJoueur(
                (string) ($currentUser['pseudo_chess'] ?? '')
            );
        }

        $pageData = $pages[$segment] ?? null;

        if ($pageData === null && $segment === 'boutique' && isset($pages['merch'])) {
            $pageData = $pages['merch'];
        }

        if ($pageData === null) {
            http_response_code(404);
            $pageCourante = 'introuvable';
            $pageData = $this->siteContent->obtenirDonneesIntrouvable();
            $pageTitle = 'Page introuvable';
            $viewFile = resource_path('views/pages/introuvable.blade.php');
        } else {
            $pageData['titre'] = (string) ($pageData['titre'] ?? $pageData['title'] ?? '');
            $pageData['vue'] = (string) ($pageData['vue'] ?? $pageData['view'] ?? '');
            $pageData['description_meta'] = (string) ($pageData['description_meta'] ?? $pageData['meta_description'] ?? '');
            $pageData['titre_hero'] = (string) ($pageData['titre_hero'] ?? $pageData['hero_title'] ?? '');
            $pageData['texte_hero'] = (string) ($pageData['texte_hero'] ?? $pageData['hero_text'] ?? '');
            $pageData['note_hero'] = (string) ($pageData['note_hero'] ?? $pageData['hero_note'] ?? '');
            $pageCourante = $segment;
            $pageTitle = $pageData['titre'] !== '' ? $pageData['titre'] : 'Page';
            $viewFile = resource_path('views/pages/' . str_replace('.php', '.blade.php', $pageData['vue']));
        }

        $metaTitle = $pageTitle . ' | ' . $siteData['brand'];
        $metaDescription = (string) (
            $pageData['description_meta']
            ?? $pageData['meta_description']
            ?? $siteData['accroche']
            ?? $siteData['tagline']
        );

        $donneesSite = normaliser_structure_utf8($siteData);
        $donneesPage = normaliser_structure_utf8($pageData);
        $metaTitre = normaliser_texte_utf8($metaTitle);
        $descriptionMeta = normaliser_texte_utf8($metaDescription);
        $fichierVue = $viewFile;

        ob_start();
        require resource_path('views/layouts/app.blade.php');

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
            ajouter_message_flash('error', "Acces reserve a l'administrateur du site.");
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

        $displayName = trim((string) ($utilisateur['prenom'] ?? '') . ' ' . (string) ($utilisateur['nom'] ?? ''));
        $estAdmin = $this->estAdmin($utilisateur);
        $estAdherent = $this->estAdherent($utilisateur);
        $role = (string) ($utilisateur['role'] ?? User::ROLE_CONNECTE);

        return [
            'est_connecte' => true,
            'nom_affichage' => $displayName !== '' ? $displayName : (string) ($utilisateur['courriel'] ?? ''),
            'utilisateur' => $utilisateur,
            'is_authenticated' => true,
            'display_name' => $displayName !== '' ? $displayName : (string) ($utilisateur['courriel'] ?? ''),
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
                'federal_license_number' => $utilisateur['numero_licence'] ?? '',
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
        return array_values(array_filter(
            $navigationPrincipale,
            static function (array $item) use ($auth): bool {
                $slug = (string) ($item['slug'] ?? '');

                if (in_array($slug, ['guide', 'boutique'], true) && !($auth['est_connecte'] ?? false)) {
                    return false;
                }

                return true;
            }
        ));
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
            && ($utilisateur['statut_compte'] ?? '') === User::STATUT_COMPTE_ACTIF;
    }

    private function estAdmin(?array $utilisateur): bool
    {
        return $this->estUtilisateurActif($utilisateur)
            && ($utilisateur['role'] ?? '') === User::ROLE_ADMIN;
    }

    private function estAdherent(?array $utilisateur): bool
    {
        return $this->estUtilisateurActif($utilisateur)
            && (
                ($utilisateur['role'] ?? '') === User::ROLE_ADHERENT
                || ($utilisateur['statut_adhesion'] ?? '') === User::STATUT_ADHESION_ACTIVE
            );
    }

    private function libelleRole(string $role): string
    {
        return match ($role) {
            User::ROLE_ADMIN => 'Administrateur',
            User::ROLE_ADHERENT => 'Adherent',
            User::ROLE_CONNECTE => 'Compte connecte',
            default => 'Visiteur',
        };
    }

    private function libelleAdhesion(string $statutAdhesion): string
    {
        return match ($statutAdhesion) {
            User::STATUT_ADHESION_ACTIVE => 'Adhesion active',
            default => 'Non adherent',
        };
    }
}
