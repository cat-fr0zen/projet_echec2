<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use App\Repositories\ArticleRepository;
use App\Repositories\CoursDocumentRepository;
use App\Repositories\ConstructeurPagesRepository;
use App\Repositories\DammierRepository;
use App\Repositories\MediaRepository;
use App\Repositories\NewsletterRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ScheduleRepository;
use App\Repositories\TraficVisiteursRepository;
use App\Repositories\UserRepository;
use App\Services\ChessComService;
use App\Services\GoogleReviewsService;
use Throwable;

final class LegacyPageRenderer
{
    public function __construct(
        private SiteContent $siteContent,
        private UserRepository $userRepository,
        private ArticleRepository $articleRepository,
        private CoursDocumentRepository $coursDocumentRepository,
        private MediaRepository $mediaRepository,
        private OrderRepository $orderRepository,
        private DammierRepository $dammierRepository,
        private ScheduleRepository $scheduleRepository,
        private ConstructeurPagesRepository $constructeurPagesRepository,
        private ?NewsletterRepository $newsletterRepository,
        private ?TraficVisiteursRepository $trafficRepository,
        private ChessComService $chessComService,
        private GoogleReviewsService $googleReviewsService,
        private array $messagesFlash,
        private array $formState
    ) {}

    public function afficher(string $segment): string
    {
        $currentUser = $this->recupererUtilisateurCourant();

        if ($currentUser !== null && ($currentUser['statut_compte'] ?? '') !== User::STATUT_COMPTE_ACTIF) {
            deconnecter_utilisateur_courant();
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
        $siteData['livrets_cours'] = $this->siteContent->obtenirLivretsCours();
        $siteData['cartes_cours_strategie'] = $this->siteContent->obtenirCartesCoursStrategie();
        $siteData['cartes_mediatheque'] = $this->siteContent->obtenirCartesMediatheque();
        $siteData['cartes_boutique'] = $this->siteContent->obtenirCartesBoutique();
        $siteData['constructeur_accueil_blocs'] = $this->constructeurPagesRepository->listerPourPage('accueil');
        $siteData['constructeur_accueil_blocs_actifs'] = $this->constructeurPagesRepository->listerActifsPourPage('accueil');

        if (! ($authData['est_connecte'] ?? false) && $this->trafficRepository !== null) {
            $this->trafficRepository->enregistrerVisitePublique($segment);
        }
        $siteData['google_reviews'] = $this->googleReviewsService->recupererAvisLieu(
            (string) ($siteData['club_google_reviews_cache_key'] ?? 'club'),
            (string) ($siteData['club_google_search_query'] ?? '')
        );
        $siteData = $this->chargerDonneesDynamiques($siteData, $authData, $currentUser);

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
            $pageData['titre_bandeau_accueil'] = $pageData['titre_hero'];
            $pageData['texte_bandeau_accueil'] = $pageData['texte_hero'];
            $pageData['note_bandeau_accueil'] = $pageData['note_hero'];
            $pageCourante = $segment;
            $pageTitle = $pageData['titre'] !== '' ? $pageData['titre'] : 'Page';
            $viewFile = resource_path('views/pages/'.str_replace('.php', '.blade.php', $pageData['vue']));
        }

        $metaTitle = $pageTitle.' | '.$siteData['brand'];
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

    private function recupererUtilisateurCourant(): ?array
    {
        try {
            return $this->userRepository->trouverParIdentifiant(identifiant_utilisateur_courant());
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $siteData
     * @param  array<string, mixed>  $authData
     * @param  array<string, mixed>|null  $currentUser
     * @return array<string, mixed>
     */
    private function chargerDonneesDynamiques(array $siteData, array $authData, ?array $currentUser): array
    {
        try {
            $clubSchedule = $this->scheduleRepository->obtenir();
            $siteData['horaires_club'] = $clubSchedule;
            $siteData['club_schedule'] = $clubSchedule;
            $siteData['resume_horaires_club'] = $this->scheduleRepository->resumerParJour();
            $siteData['club_schedule_summary'] = $siteData['resume_horaires_club'];
            $siteData['planning'] = $this->scheduleRepository->adapterPlanning();
            $siteData['schedule'] = $siteData['planning'];

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
            $siteData['peut_gerer_documents_cours'] = $authData['est_admin'] || $authData['est_prof'];
            $siteData['documents_cours_par_rubrique'] = $siteData['peut_gerer_documents_cours']
                ? $this->coursDocumentRepository->listerParRubrique()
                : [];
            $siteData['tous_utilisateurs'] = $authData['est_admin'] ? $this->userRepository->listerTous() : [];
            $siteData['all_users'] = $siteData['tous_utilisateurs'];
            $siteData['resume_roles_compte'] = $authData['est_admin'] ? $this->userRepository->resumerRoles() : [];
            $siteData['limite_professeurs'] = User::MAX_PROFESSEURS;
            $siteData['resume_newsletter'] = $authData['est_admin'] && $this->newsletterRepository !== null
                ? $this->newsletterRepository->obtenirResumeAdmin()
                : [];
            $siteData['newsletter_abonnements_admin'] = $authData['est_admin'] && $this->newsletterRepository !== null
                ? $this->newsletterRepository->listerAbonnementsAdmin()
                : [];
            $siteData['newsletter_envois_admin'] = $authData['est_admin'] && $this->newsletterRepository !== null
                ? $this->newsletterRepository->listerDerniersEnvois()
                : [];
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
            $siteData['resume_trafic_visiteurs'] = $authData['est_admin'] && $this->trafficRepository !== null
                ? $this->trafficRepository->obtenirResumeAdmin()
                : [];

            $dammierPuzzle = $this->dammierRepository->obtenirPuzzleHebdomadaire();
            $dammierClassement = $this->dammierRepository->listerClassementHebdomadaire(
                (string) ($dammierPuzzle['dammier_week_key'] ?? ''),
                (string) ($dammierPuzzle['dammier_id'] ?? '')
            );
            $siteData['dammier_puzzle'] = $dammierPuzzle;
            $siteData['dammier_classement'] = $dammierClassement;
            $siteData['dammier_peut_voir_classement'] = (bool) ($authData['est_connecte'] ?? false);

            return $siteData;
        } catch (Throwable $exception) {
            report($exception);

            return $this->appliquerModeDegrade($siteData, $authData);
        }
    }

    /**
     * @param  array<string, mixed>  $siteData
     * @param  array<string, mixed>  $authData
     * @return array<string, mixed>
     */
    private function appliquerModeDegrade(array $siteData, array $authData): array
    {
        $horaires = [
            'schedule_id' => 'club_schedule_fallback',
            'season_label' => 'Horaires du club',
            'holiday_notice' => '',
            'updated_at' => '',
            'items' => [],
        ];

        $siteData['horaires_club'] = $horaires;
        $siteData['club_schedule'] = $horaires;
        $siteData['resume_horaires_club'] = [];
        $siteData['club_schedule_summary'] = [];
        $siteData['planning'] = [];
        $siteData['schedule'] = [];
        $siteData['articles_publies'] = [];
        $siteData['published_articles'] = [];
        $siteData['mes_articles'] = [];
        $siteData['my_articles'] = [];
        $siteData['medias_publies'] = [];
        $siteData['published_media'] = [];
        $siteData['mes_medias'] = [];
        $siteData['my_media'] = [];
        $siteData['peut_gerer_documents_cours'] = false;
        $siteData['documents_cours_par_rubrique'] = [];
        $siteData['tous_utilisateurs'] = [];
        $siteData['all_users'] = [];
        $siteData['resume_roles_compte'] = [];
        $siteData['limite_professeurs'] = User::MAX_PROFESSEURS;
        $siteData['resume_newsletter'] = [];
        $siteData['newsletter_abonnements_admin'] = [];
        $siteData['newsletter_envois_admin'] = [];
        $siteData['tous_articles'] = [];
        $siteData['all_articles'] = [];
        $siteData['tous_medias'] = [];
        $siteData['all_media'] = [];
        $siteData['commandes_membre'] = [];
        $siteData['member_orders'] = [];
        $siteData['toutes_commandes'] = [];
        $siteData['all_orders'] = [];
        $siteData['resume_trafic_visiteurs'] = [];
        $siteData['dammier_puzzle'] = $this->puzzleDeSecoursHorsBase();
        $siteData['dammier_classement'] = [];
        $siteData['dammier_peut_voir_classement'] = (bool) ($authData['est_connecte'] ?? false);
        $siteData['constructeur_accueil_blocs'] = $this->constructeurPagesRepository->listerPourPage('accueil');
        $siteData['constructeur_accueil_blocs_actifs'] = $this->constructeurPagesRepository->listerActifsPourPage('accueil');

        return $siteData;
    }

    /**
     * @return array<string, mixed>
     */
    private function puzzleDeSecoursHorsBase(): array
    {
        return [
            'dammier_id' => 'dammier_hors_base',
            'dammier_title' => 'Puzzle hors ligne',
            'dammier_description' => 'Le puzzle interactif reste disponible même si la base locale ne répond pas.',
            'dammier_instruction' => 'Trouve la suite théorique en 2 coups blancs.',
            'dammier_fen' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w - - 0 1',
            'dammier_side_to_move' => 'w',
            'dammier_difficulty_code' => 'facile',
            'dammier_difficulty_label' => 'Facile',
            'dammier_solution' => ['e2e4', 'g1f3'],
            'dammier_replies' => ['e7e5', 'b8c6'],
            'dammier_hints' => [
                'Commence par prendre le centre.',
                'Ensuite, développe une pièce mineure.',
            ],
            'dammier_source' => 'fallback_hors_base',
            'dammier_white_moves_count' => 2,
            'dammier_week_key' => '',
        ];
    }

    private function autoriserAccesPage(string $segment, ?array $utilisateur): void
    {
        $estConnecte = $this->estUtilisateurActif($utilisateur);
        $estAdmin = $this->estAdmin($utilisateur);

        if (in_array($segment, [
            'guide',
            'cours-livrets',
            'cours-livret-a',
            'cours-livret-b',
            'cours-livret-c',
            'cours-livret-d',
            'cours-livret-e',
            'cours-seances',
            'cours-progression',
            'cours-methodologie',
            'cours-strategie',
            'boutique',
            'profil',
            'parametres',
        ], true) && ! $estConnecte) {
            ajouter_message_flash('error', 'Connecte-toi pour acceder a cette page.');
            rediriger_vers(url_route('accueil'));
        }

        if ($segment === 'admin' && ! $estAdmin) {
            ajouter_message_flash('error', "Acces reserve a l'administrateur du site.");
            rediriger_vers(url_route('accueil'));
        }
    }

    private function construireDonneesAuthentification(?array $utilisateur): array
    {
        if (! $this->estUtilisateurActif($utilisateur)) {
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
                'est_prof' => false,
                'peut_voir_guides' => false,
                'peut_voir_boutique' => false,
                'peut_publier_articles' => false,
                'peut_soumettre_medias' => false,
            ];
        }

        $displayName = trim((string) ($utilisateur['prenom'] ?? '').' '.(string) ($utilisateur['nom'] ?? ''));
        $estAdmin = $this->estAdmin($utilisateur);
        $estAdherent = $this->estAdherent($utilisateur);
        $estProf = $this->estProf($utilisateur);
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
            'est_prof' => $estProf,
            'peut_voir_guides' => true,
            'peut_voir_boutique' => true,
            'peut_publier_articles' => $estAdmin || $estAdherent || $estProf,
            'peut_soumettre_medias' => $estAdmin || $estAdherent || $estProf,
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

                if (in_array($slug, ['guide', 'boutique'], true) && ! ($auth['est_connecte'] ?? false)) {
                    return false;
                }

                return true;
            }
        ));
    }

    private function filtrerNavigationSecondaire(array $navigationSecondaire, array $auth): array
    {
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

    private function estProf(?array $utilisateur): bool
    {
        return $this->estUtilisateurActif($utilisateur)
            && ($utilisateur['role'] ?? '') === User::ROLE_PROF;
    }

    private function libelleRole(string $role): string
    {
        return match ($role) {
            User::ROLE_ADMIN => 'Administrateur',
            User::ROLE_PROF => 'Prof',
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
