<?php

declare(strict_types=1);

namespace App\Support;

use App\Repositories\ArticleRepository;
use App\Repositories\CoursDocumentRepository;
use App\Repositories\ConstructeurPagesRepository;
use App\Repositories\DammierRepository;
use App\Repositories\MediaRepository;
use App\Repositories\NewsletterRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ScheduleRepository;
use App\Repositories\UserRepository;
use App\Services\NewsletterMailerService;
use DateTimeImmutable;
use Illuminate\Support\Facades\Hash;
use Throwable;

/**
 * Controleur d'actions (POST).
 *
 * Role:
 * - traiter les formulaires du site (inscription, login, profil, depot article/media, etc.)
 * - appliquer les regles de securite (CSRF) et d'autorisation (roles/statuts)
 * - ecrire dans les depots de donnees MySQL du port Laravel
 * - rediriger vers la page cible avec message flash
 *
 * Dependances:
 * - DepotUtilisateurs: authentification + roles/statuts
 * - DepotArticles: soumission + moderation
 * - DepotMedias: upload + moderation (metadonnees)
 * - DepotCommandes: commandes "merch" (prototype)
 * - DepotHoraires: horaires publics editables par l'admin
 */
final class LegacyActionHandler
{
    private const MODE_DOSSIER_UPLOAD = 0755;

    private const MODE_FICHIER_UPLOAD = 0644;

    private const PAGES_AUTORISEES = [
        'accueil',
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
        'mediatheque',
        'articles',
        'boutique',
        'club',
        'activites',
        'contact',
        'profil',
        'parametres',
        'admin',
    ];

    public function __construct(
        private UserRepository $depotUtilisateurs,
        private ArticleRepository $depotArticles,
        private CoursDocumentRepository $depotDocumentsCours,
        private MediaRepository $depotMedias,
        private OrderRepository $depotCommandes,
        private DammierRepository $depotDammier,
        private ScheduleRepository $depotHoraires,
        private ConstructeurPagesRepository $depotConstructeurPages,
        private string $dossierUploadMedias,
        private ?NewsletterRepository $depotNewsletter = null,
        private ?NewsletterMailerService $newsletterMailer = null,
        private ?SensitiveActionRateLimiter $rateLimiter = null
    ) {
        $this->rateLimiter ??= new SensitiveActionRateLimiter;
    }

    /**
     * Point d'entree unique: route toutes les actions POST.
     */
    public function traiter(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return;
        }

        $action = isset($_POST['action']) ? trim((string) $_POST['action']) : '';

        if ($action === '') {
            return;
        }

        if (! verifier_jeton_csrf($_POST['jeton_csrf'] ?? null)) {
            $this->traiterEchecCsrf($action);
        }

        $contexteTentative = [
            ...$_POST,
            '__ip' => (string) (request()->ip() ?? ''),
        ];

        $this->rateLimiter?->bloquerSiNecessaire($action, $contexteTentative, $this->resoudrePageRedirection('accueil'));
        $this->rateLimiter?->enregistrerTentative($action, $contexteTentative);

        switch ($action) {
            case 'inscription':
            case 'register':
                $this->traiterInscription();
                break;
            case 'connexion':
            case 'login':
                $this->traiterConnexion();
                break;
            case 'deconnexion':
            case 'logout':
                $this->traiterDeconnexion();
                break;
            case 'mettre_a_jour_profil':
            case 'update_profile':
                $this->traiterMiseAJourProfil();
                break;
            case 'creer_article':
            case 'create_article':
                $this->traiterCreationArticle();
                break;
            case 'ajouter_document_cours':
            case 'upload_course_document':
                $this->traiterAjoutDocumentCours();
                break;
            case 'modifier_document_cours':
            case 'update_course_document':
                $this->traiterMiseAJourDocumentCours();
                break;
            case 'supprimer_document_cours':
            case 'delete_course_document':
                $this->traiterSuppressionDocumentCours();
                break;
            case 'soumettre_media':
            case 'submit_media':
                $this->traiterSoumissionMedia();
                break;
            case 'commander_produit':
            case 'order_product':
                $this->traiterCommandeProduit();
                break;
            case 'moderer_article':
            case 'review_article':
                $this->traiterModerationArticle();
                break;
            case 'supprimer_article':
            case 'delete_article':
                $this->traiterSuppressionArticle();
                break;
            case 'moderer_media':
            case 'review_media':
                $this->traiterModerationMedia();
                break;
            case 'mettre_a_jour_statut_commande':
            case 'update_order_status':
                $this->traiterMiseAJourStatutCommande();
                break;
            case 'mettre_a_jour_acces_utilisateur':
            case 'update_user_access':
                $this->traiterMiseAJourAccesUtilisateur();
                break;
            case 'transferer_role_admin':
            case 'transfer_admin_role':
                $this->traiterTransfertRoleAdmin();
                break;
            case 'mettre_a_jour_horaires_club':
            case 'update_club_schedule':
                $this->traiterMiseAJourHorairesClub();
                break;
            case 'mettre_a_jour_constructeur_accueil':
            case 'update_home_builder':
                $this->traiterMiseAJourConstructeurAccueil();
                break;
            case 'notifier_objet_boutique':
            case 'notify_shop_item':
                $this->traiterNotificationObjetBoutique();
                break;
            case 'inscription_newsletter':
            case 'newsletter_subscribe':
                $this->traiterInscriptionNewsletter();
                break;
            case 'soumettre_resultat_dammier':
            case 'submit_dammier_score':
                $this->traiterSoumissionResultatDammier();
                break;
            default:
                ajouter_message_flash('error', 'Action non prise en charge.');
                rediriger_vers(url_route('accueil'));
        }
    }

    /** Traite le formulaire d'inscription. */
    private function traiterInscription(): void
    {
        $pageRedirection = $this->resoudrePageRedirection('accueil');
        $donnees = [
            'nom' => trim((string) ($_POST['nom'] ?? $_POST['last_name'] ?? '')),
            'prenom' => trim((string) ($_POST['prenom'] ?? $_POST['first_name'] ?? '')),
            'date_naissance' => trim((string) ($_POST['date_naissance'] ?? $_POST['birth_date'] ?? '')),
            'courriel' => trim((string) ($_POST['courriel'] ?? $_POST['email'] ?? '')),
            'numero_licence' => $this->depotUtilisateurs->normaliserNumeroLicenceFederale($_POST['numero_licence'] ?? $_POST['federal_license_number'] ?? ''),
            'mot_de_passe' => (string) ($_POST['mot_de_passe'] ?? $_POST['password'] ?? ''),
            'description_profil' => trim((string) ($_POST['description_profil'] ?? $_POST['profile_description'] ?? '')),
            'pseudo_chess' => trim((string) ($_POST['pseudo_chess'] ?? '')),
        ];

        $erreurs = $this->validerDonneesProfil($donnees, true);

        $erreurCourrielPartage = $this->validerCourrielPourInscription(
            $donnees['courriel'],
            (string) $donnees['numero_licence']
        );

        if ($erreurCourrielPartage !== null) {
            $erreurs[] = $erreurCourrielPartage;
        }

        if ($this->numeroLicenceDejaUtilise($donnees['numero_licence'])) {
            $erreurs[] = 'Un compte existe deja avec ce numero de licence.';
        }

        if ($erreurs !== []) {
            memoriser_etat_formulaire([
                'ouverte' => true,
                'onglet' => 'inscription',
                'erreurs' => $erreurs,
                'anciennes_valeurs' => $donnees,
            ]);
            rediriger_vers(url_route($pageRedirection));
        }

        $utilisateur = $this->depotUtilisateurs->creer($donnees);
        $this->rateLimiter?->reinitialiser('inscription', [
            ...$donnees,
            '__ip' => (string) (request()->ip() ?? ''),
        ]);
        $this->rateLimiter?->reinitialiser('connexion', [
            'identifiant_connexion' => $donnees['courriel'],
            '__ip' => (string) (request()->ip() ?? ''),
        ]);
        $this->ouvrirSessionAuthentification((string) $utilisateur['identifiant']);
        ajouter_message_flash('success', 'Votre compte a été créé avec succès.');
        rediriger_vers(url_route('profil'));
    }

    /**
     * Affiche une erreur claire quand un formulaire d'auth arrive avec une session expiree.
     */
    private function traiterEchecCsrf(string $action): never
    {
        $pageRedirection = $this->resoudrePageRedirection('accueil');

        if (in_array($action, ['inscription', 'register'], true)) {
            memoriser_etat_formulaire([
                'ouverte' => true,
                'onglet' => 'inscription',
                'erreurs' => ['Votre session a expiré. Merci de renvoyer le formulaire.'],
                'anciennes_valeurs' => [
                    'nom' => trim((string) ($_POST['nom'] ?? $_POST['last_name'] ?? '')),
                    'prenom' => trim((string) ($_POST['prenom'] ?? $_POST['first_name'] ?? '')),
                    'date_naissance' => trim((string) ($_POST['date_naissance'] ?? $_POST['birth_date'] ?? '')),
                    'courriel' => trim((string) ($_POST['courriel'] ?? $_POST['email'] ?? '')),
                    'numero_licence' => $this->depotUtilisateurs->normaliserNumeroLicenceFederale($_POST['numero_licence'] ?? $_POST['federal_license_number'] ?? ''),
                    'description_profil' => trim((string) ($_POST['description_profil'] ?? $_POST['profile_description'] ?? '')),
                    'pseudo_chess' => trim((string) ($_POST['pseudo_chess'] ?? '')),
                ],
            ]);
            rediriger_vers(url_route($pageRedirection));
        }

        if (in_array($action, ['connexion', 'login'], true)) {
            $identifiantConnexion = trim((string) ($_POST['identifiant_connexion'] ?? $_POST['login_identifier'] ?? $_POST['courriel'] ?? $_POST['email'] ?? ''));
            memoriser_etat_formulaire([
                'ouverte' => true,
                'onglet' => 'connexion',
                'erreurs' => ['Votre session a expiré. Merci de renvoyer le formulaire.'],
                'anciennes_valeurs' => [
                    'identifiant_connexion' => $identifiantConnexion,
                    'courriel' => $identifiantConnexion,
                ],
            ]);
            rediriger_vers(url_route($pageRedirection));
        }

        ajouter_message_flash('error', 'Votre session a expiré. Merci de recommencer.');
        rediriger_vers(url_route($pageRedirection));
    }

    /** Traite le formulaire de connexion. */
    private function traiterConnexion(): void
    {
        $pageRedirection = $this->resoudrePageRedirection('accueil');
        $identifiantConnexion = trim((string) ($_POST['identifiant_connexion'] ?? $_POST['login_identifier'] ?? $_POST['courriel'] ?? $_POST['email'] ?? ''));
        $motDePasse = (string) ($_POST['mot_de_passe'] ?? $_POST['password'] ?? '');

        if (
            filter_var($identifiantConnexion, FILTER_VALIDATE_EMAIL)
            && $this->depotUtilisateurs->compterParCourriel($identifiantConnexion) > 1
        ) {
            memoriser_etat_formulaire([
                'ouverte' => true,
                'onglet' => 'connexion',
                'erreurs' => ['Plusieurs comptes partagent cet email. Connectez-vous avec le numero de licence du compte concerne.'],
                'anciennes_valeurs' => [
                    'identifiant_connexion' => $identifiantConnexion,
                    'courriel' => $identifiantConnexion,
                ],
            ]);
            rediriger_vers(url_route($pageRedirection));
        }

        $utilisateurAuthentifiable = $this->depotUtilisateurs->trouverModeleParIdentifiantConnexion($identifiantConnexion);
        $utilisateur = $utilisateurAuthentifiable !== null
            ? $this->depotUtilisateurs->trouverParIdentifiant((string) $utilisateurAuthentifiable->getAuthIdentifier())
            : null;

        if ($utilisateur === null || $utilisateurAuthentifiable === null || ! Hash::check($motDePasse, $utilisateurAuthentifiable->getAuthPassword())) {
            memoriser_etat_formulaire([
                'ouverte' => true,
                'onglet' => 'connexion',
                'erreurs' => ['Identifiant ou mot de passe incorrect.'],
                'anciennes_valeurs' => [
                    'identifiant_connexion' => $identifiantConnexion,
                    'courriel' => $identifiantConnexion,
                ],
            ]);
            rediriger_vers(url_route($pageRedirection));
        }

        if (Hash::needsRehash($utilisateurAuthentifiable->getAuthPassword())) {
            $this->depotUtilisateurs->mettreAJourMotDePasse((string) $utilisateur['identifiant'], $motDePasse);
        }

        if (($utilisateur['statut_compte'] ?? '') !== DepotUtilisateurs::STATUT_COMPTE_ACTIF) {
            memoriser_etat_formulaire([
                'ouverte' => true,
                'onglet' => 'connexion',
                'erreurs' => ['Votre compte est actuellement suspendu.'],
                'anciennes_valeurs' => [
                    'identifiant_connexion' => $identifiantConnexion,
                    'courriel' => $identifiantConnexion,
                ],
            ]);
            rediriger_vers(url_route($pageRedirection));
        }

        $this->ouvrirSessionAuthentification((string) $utilisateur['identifiant']);
        ajouter_message_flash('success', 'Connexion réussie.');
        rediriger_vers(url_route('profil'));
    }

    /** Permet a l'admin d'organiser les blocs visibles de l'accueil. */
    private function traiterMiseAJourConstructeurAccueil(): void
    {
        $utilisateur = $this->depotUtilisateurs->trouverParIdentifiant(identifiant_utilisateur_courant());

        if (($utilisateur['role'] ?? '') !== 'admin') {
            ajouter_message_flash('error', "Seul l'administrateur peut modifier le constructeur.");
            rediriger_vers(url_route('admin').'#admin-constructeur');
        }

        $ordres = is_array($_POST['ordre_bloc'] ?? null) ? $_POST['ordre_bloc'] : [];
        $actifs = is_array($_POST['bloc_actif'] ?? null) ? $_POST['bloc_actif'] : [];
        $donnees = [];

        foreach ($ordres as $codeBloc => $ordreBloc) {
            $codeBlocNormalise = trim((string) $codeBloc);

            if ($codeBlocNormalise === '') {
                continue;
            }

            $donnees[$codeBlocNormalise] = [
                'ordre_affichage' => max(1, (int) $ordreBloc),
                'est_actif' => isset($actifs[$codeBlocNormalise]) && (string) $actifs[$codeBlocNormalise] === '1',
            ];
        }

        $this->depotConstructeurPages->mettreAJourBlocsAccueil($donnees);
        ajouter_message_flash('success', "Le constructeur de l'accueil a été mis à jour.");
        rediriger_vers(url_route('admin').'#admin-constructeur');
    }

    /** Inscrit une adresse a la newsletter avec consentement explicite. */
    private function traiterInscriptionNewsletter(): void
    {
        $pageRedirection = $this->resoudrePageRedirection('accueil');

        if ($this->depotNewsletter === null || $this->newsletterMailer === null) {
            ajouter_message_flash('error', "La newsletter n'est pas disponible pour le moment.");
            rediriger_vers(url_route($pageRedirection).'#footer-newsletter-title');
        }

        $courriel = trim((string) ($_POST['newsletter_email'] ?? ''));
        $courriel = function_exists('mb_strtolower') ? mb_strtolower($courriel) : strtolower($courriel);
        $consentementAccepte = (string) ($_POST['newsletter_consentement'] ?? '') === '1';

        if ($courriel === '' || strlen($courriel) > 254 || ! filter_var($courriel, FILTER_VALIDATE_EMAIL)) {
            ajouter_message_flash('error', 'Veuillez saisir une adresse email valide pour la newsletter.');
            rediriger_vers(url_route($pageRedirection).'#footer-newsletter-title');
        }

        if (! $consentementAccepte) {
            ajouter_message_flash('error', 'Le consentement est obligatoire pour recevoir la newsletter.');
            rediriger_vers(url_route($pageRedirection).'#footer-newsletter-title');
        }

        $abonnement = $this->depotNewsletter->inscrire(
            $courriel,
            $this->hacherAdresseIp(),
            $this->nettoyerAgentUtilisateur(),
            'footer'
        );

        try {
            $this->newsletterMailer->envoyerConfirmation($abonnement);
        } catch (Throwable $exception) {
            error_log('[newsletter-confirmation] '.$exception->getMessage());
        }

        ajouter_message_flash('success', 'Inscription newsletter enregistrée. Un email de confirmation va être envoyé si la messagerie du serveur est configurée.');
        rediriger_vers(url_route($pageRedirection).'#footer-newsletter-title');
    }

    /** Traite la deconnexion (session). */
    private function traiterDeconnexion(): void
    {
        $this->fermerSessionAuthentification();
        ajouter_message_flash('success', 'Vous avez été déconnecté.');
        rediriger_vers(url_route('accueil'));
    }

    /** Ouvre ou migre proprement la session d'authentification. */
    private function ouvrirSessionAuthentification(string $identifiantUtilisateur): void
    {
        connecter_utilisateur_courant($identifiantUtilisateur);
    }

    /** Ferme proprement la session d'authentification sans supposer une session PHP active. */
    private function fermerSessionAuthentification(): void
    {
        deconnecter_utilisateur_courant();
    }

    /** Garantit une session native active avant les appels PHP bas niveau. */
    private function demarrerSessionNativeSiNecessaire(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
    }

    /** Met a jour le profil (nom/prenom/date/description/pseudo chess). */
    private function traiterMiseAJourProfil(): void
    {
        $utilisateurCourant = $this->obtenirUtilisateurCourant();

        if ($utilisateurCourant === null) {
            ajouter_message_flash('error', 'Vous devez être connecté pour modifier votre profil.');
            rediriger_vers(url_route('accueil'));
        }

        $donnees = [
            'nom' => trim((string) ($_POST['nom'] ?? $_POST['last_name'] ?? '')),
            'prenom' => trim((string) ($_POST['prenom'] ?? $_POST['first_name'] ?? '')),
            'date_naissance' => trim((string) ($_POST['date_naissance'] ?? $_POST['birth_date'] ?? '')),
            'description_profil' => trim((string) ($_POST['description_profil'] ?? $_POST['profile_description'] ?? '')),
            'pseudo_chess' => trim((string) ($_POST['pseudo_chess'] ?? $_POST['chess_username'] ?? '')),
            'numero_licence' => $this->depotUtilisateurs->normaliserNumeroLicenceFederale($_POST['numero_licence'] ?? $_POST['federal_license_number'] ?? ''),
            'courriel' => (string) ($utilisateurCourant['courriel'] ?? ''),
            'mot_de_passe' => 'ignore',
        ];

        $erreurs = $this->validerDonneesProfil($donnees, false);

        if ($erreurs !== []) {
            ajouter_message_flash('error', implode(' ', $erreurs));
            rediriger_vers(url_route('profil'));
        }

        if ($this->numeroLicenceDejaUtilise($donnees['numero_licence'], (string) $utilisateurCourant['identifiant'])) {
            ajouter_message_flash('error', 'Ce numéro de licence est déjà associé à un autre compte.');
            rediriger_vers(url_route('profil'));
        }

        $this->depotUtilisateurs->mettreAJour((string) $utilisateurCourant['identifiant'], $donnees);
        ajouter_message_flash('success', 'Votre profil a été mis à jour.');
        rediriger_vers(url_route('profil'));
    }

    /** Ajoute un document PDF de cours dans une rubrique geree par les profs et l'admin. */
    private function traiterAjoutDocumentCours(): void
    {
        $utilisateurCourant = $this->exigerProfOuAdmin();
        $pageOrigine = $this->resoudrePageRedirection('guide');
        $rubrique = trim((string) ($_POST['rubrique_document_cours'] ?? ''));
        $titre = trim((string) ($_POST['titre_document_cours'] ?? ''));
        $description = trim((string) ($_POST['description_document_cours'] ?? ''));
        $fichier = $_FILES['fichier_document_cours'] ?? null;
        $pageRedirection = url_route($pageOrigine);

        if (! $this->depotDocumentsCours->rubriqueEstValide($rubrique)) {
            ajouter_message_flash('error', 'La rubrique de cours demandée est invalide.');
            rediriger_vers($pageRedirection);
        }

        $pageRedirection = $this->urlPageCoursRubrique($rubrique);

        if ($titre === '' || mb_strlen($titre) > 160) {
            ajouter_message_flash('error', 'Le titre du document est obligatoire et doit rester inférieur à 160 caractères.');
            rediriger_vers($pageRedirection);
        }

        if (mb_strlen($description) > 2000) {
            ajouter_message_flash('error', 'La description du document doit rester inférieure à 2000 caractères.');
            rediriger_vers($pageRedirection);
        }

        if (! is_array($fichier) || (($fichier['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK)) {
            ajouter_message_flash('error', 'Merci de joindre un fichier PDF valide.');
            rediriger_vers($pageRedirection);
        }

        $validation = $this->validerFichierPdfCours($fichier);

        if ($validation['erreurs'] !== []) {
            ajouter_message_flash('error', implode(' ', $validation['erreurs']));
            rediriger_vers($pageRedirection);
        }

        $dossierCours = UploadStorage::dossierCours();

        if (! $this->preparerDossierUpload($dossierCours)) {
            ajouter_message_flash('error', "Le dossier des documents de cours n'est pas disponible.");
            rediriger_vers($pageRedirection);
        }

        $nomStocke = 'cours_'.bin2hex(random_bytes(12)).'.pdf';
        $cheminDestination = $dossierCours.DIRECTORY_SEPARATOR.$nomStocke;

        if (! $this->deplacerFichierTeleverse((string) ($fichier['tmp_name'] ?? ''), $cheminDestination)) {
            ajouter_message_flash('error', "Le téléversement du PDF a échoué.");
            rediriger_vers($pageRedirection);
        }

        $this->securiserFichierTeleverse($cheminDestination);

        $this->depotDocumentsCours->creer([
            'code_rubrique' => $rubrique,
            'titre_document' => $titre,
            'description_document' => $description,
            'nom_fichier_original' => (string) ($fichier['name'] ?? 'document.pdf'),
            'nom_fichier_stocke' => $nomStocke,
            'chemin_fichier' => UploadStorage::cheminCours($nomStocke),
            'type_mime' => $validation['mime'],
            'taille_octets' => (int) ($fichier['size'] ?? 0),
            'identifiant_auteur' => (string) ($utilisateurCourant['identifiant'] ?? ''),
        ]);

        ajouter_message_flash('success', 'Le document PDF a été ajouté dans la rubrique de cours.');
        rediriger_vers($pageRedirection);
    }

    /** Met a jour un document PDF de cours, avec remplacement optionnel du fichier protege. */
    private function traiterMiseAJourDocumentCours(): void
    {
        $utilisateurCourant = $this->exigerProfOuAdmin();
        $identifiantDocument = trim((string) ($_POST['identifiant_document_cours'] ?? ''));
        $documentExistant = $this->depotDocumentsCours->trouverParIdentifiant($identifiantDocument);

        if ($documentExistant === null) {
            ajouter_message_flash('error', 'Le document demandé est introuvable.');
            rediriger_vers(url_route('guide').'#cours-livrets');
        }

        $rubrique = trim((string) ($_POST['rubrique_document_cours'] ?? ''));
        $titre = trim((string) ($_POST['titre_document_cours'] ?? ''));
        $description = trim((string) ($_POST['description_document_cours'] ?? ''));
        $fichierRemplacement = $_FILES['fichier_document_cours_remplacement'] ?? null;
        $rubriqueOrigine = (string) ($documentExistant['code_rubrique'] ?? 'cours');
        $pageOrigine = $this->resoudrePageRedirection($this->pageCoursDepuisRubrique($rubriqueOrigine));
        $pageRedirection = url_route($pageOrigine).'#'.$this->ancreDocumentCours($rubriqueOrigine);

        if (! $this->depotDocumentsCours->rubriqueEstValide($rubrique)) {
            ajouter_message_flash('error', 'La rubrique de cours demandée est invalide.');
            rediriger_vers($pageRedirection);
        }

        if ($pageOrigine === 'guide') {
            $pageRedirection = url_route('guide').'#'.$this->ancreDocumentCours($rubrique);
        }

        if ($titre === '' || mb_strlen($titre) > 160) {
            ajouter_message_flash('error', 'Le titre du document est obligatoire et doit rester inférieur à 160 caractères.');
            rediriger_vers($pageRedirection);
        }

        if (mb_strlen($description) > 2000) {
            ajouter_message_flash('error', 'La description du document doit rester inférieure à 2000 caractères.');
            rediriger_vers($pageRedirection);
        }

        $donneesMiseAJour = [
            'code_rubrique' => $rubrique,
            'titre_document' => $titre,
            'description_document' => $description,
        ];

        $nomStockeRemplacement = null;

        if (is_array($fichierRemplacement)) {
            $erreurTeleversement = (int) ($fichierRemplacement['error'] ?? UPLOAD_ERR_NO_FILE);

            if ($erreurTeleversement !== UPLOAD_ERR_NO_FILE && $erreurTeleversement !== UPLOAD_ERR_OK) {
                ajouter_message_flash('error', 'Le remplacement du PDF a échoué.');
                rediriger_vers($pageRedirection);
            }

            if ($erreurTeleversement === UPLOAD_ERR_OK) {
                $validation = $this->validerFichierPdfCours($fichierRemplacement);

                if ($validation['erreurs'] !== []) {
                    ajouter_message_flash('error', implode(' ', $validation['erreurs']));
                    rediriger_vers($pageRedirection);
                }

                $dossierCours = UploadStorage::dossierCours();

                if (! $this->preparerDossierUpload($dossierCours)) {
                    ajouter_message_flash('error', "Le dossier des documents de cours n'est pas disponible.");
                    rediriger_vers($pageRedirection);
                }

                $nomStockeRemplacement = 'cours_'.bin2hex(random_bytes(12)).'.pdf';
                $cheminDestination = $dossierCours.DIRECTORY_SEPARATOR.$nomStockeRemplacement;

                if (! $this->deplacerFichierTeleverse((string) ($fichierRemplacement['tmp_name'] ?? ''), $cheminDestination)) {
                    ajouter_message_flash('error', "Le téléversement du PDF a échoué.");
                    rediriger_vers($pageRedirection);
                }

                $this->securiserFichierTeleverse($cheminDestination);

                $donneesMiseAJour['nom_fichier_original'] = (string) ($fichierRemplacement['name'] ?? 'document.pdf');
                $donneesMiseAJour['nom_fichier_stocke'] = $nomStockeRemplacement;
                $donneesMiseAJour['chemin_fichier'] = UploadStorage::cheminCours($nomStockeRemplacement);
                $donneesMiseAJour['type_mime'] = $validation['mime'];
                $donneesMiseAJour['taille_octets'] = (int) ($fichierRemplacement['size'] ?? 0);
            }
        }

        $documentMisAJour = $this->depotDocumentsCours->mettreAJour($identifiantDocument, $donneesMiseAJour);

        if ($documentMisAJour === null) {
            if ($nomStockeRemplacement !== null) {
                UploadStorage::supprimerCheminCours($nomStockeRemplacement);
            }

            ajouter_message_flash('error', 'La mise à jour du document a échoué.');
            rediriger_vers($pageRedirection);
        }

        if (
            $nomStockeRemplacement !== null
            && (string) ($documentExistant['nom_fichier_stocke'] ?? '') !== ''
            && (string) ($documentExistant['nom_fichier_stocke'] ?? '') !== $nomStockeRemplacement
        ) {
            UploadStorage::supprimerCheminCours((string) $documentExistant['nom_fichier_stocke']);
        }

        ajouter_message_flash('success', 'Le document de cours a été modifié.');
        rediriger_vers($this->urlPageCoursRubrique((string) ($documentMisAJour['code_rubrique'] ?? $rubrique)));
    }

    /** Supprime un document PDF de cours avec nettoyage du fichier protege. */
    private function traiterSuppressionDocumentCours(): void
    {
        $this->exigerProfOuAdmin();
        $identifiantDocument = trim((string) ($_POST['identifiant_document_cours'] ?? ''));
        $document = $this->depotDocumentsCours->trouverParIdentifiant($identifiantDocument);

        if ($document === null) {
            ajouter_message_flash('error', 'Le document demande est introuvable.');
            rediriger_vers(url_route('guide').'#cours-livrets');
        }

        $this->depotDocumentsCours->supprimer($identifiantDocument);
        UploadStorage::supprimerCheminCours((string) ($document['nom_fichier_stocke'] ?? ''));

        ajouter_message_flash('success', 'Le document de cours a été supprimé.');
        rediriger_vers($this->urlPageCoursRubrique((string) ($document['code_rubrique'] ?? '')));
    }

    /** Soumission d'article (reserve adherent/admin). */
    private function traiterCreationArticle(): void
    {
        $utilisateurCourant = $this->obtenirUtilisateurCourant();

        if ($utilisateurCourant === null) {
            ajouter_message_flash('error', 'Vous devez être connecté pour proposer un article.');
            rediriger_vers(url_route('articles'));
        }

        if (! $this->utilisateurPeutPublierContenu($utilisateurCourant)) {
            ajouter_message_flash('error', 'Seuls les adhérents du club peuvent proposer des articles.');
            rediriger_vers(url_route('articles'));
        }

        $titre = trim((string) ($_POST['titre'] ?? $_POST['title'] ?? ''));
        $auteurAffiche = trim((string) ($_POST['auteur_affiche'] ?? $_POST['display_author'] ?? ''));
        $erreurs = [];

        if ($titre === '' || mb_strlen($titre) > 150) {
            $erreurs[] = 'Le titre est obligatoire et doit rester inférieur à 150 caractères.';
        }

        if ($auteurAffiche === '' || mb_strlen($auteurAffiche) > 120) {
            $erreurs[] = "Le nom d'auteur affiché est obligatoire et doit rester inférieur à 120 caractères.";
        }

        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $auteurAffiche) === 1) {
            $erreurs[] = "Le nom d'auteur contient des caracteres non autorises.";
        }

        if ($erreurs !== []) {
            ajouter_message_flash('error', implode(' ', $erreurs));
            rediriger_vers(url_route('articles'));
        }

        $blocsArticle = $this->normaliserBlocsArticleDepuisFormulaire();
        $contenuTexte = $this->extraireTexteArticle($blocsArticle['blocs']);
        $resume = $this->genererResumeArticle($blocsArticle['blocs']);
        $erreurs = $blocsArticle['erreurs'];

        if (mb_strlen($contenuTexte) < 40) {
            $erreurs[] = "L'article doit contenir au moins 40 caracteres de texte.";
        }

        if ($erreurs !== []) {
            $this->supprimerMediasArticleTeleverses($blocsArticle['blocs']);
            ajouter_message_flash('error', implode(' ', $erreurs));
            rediriger_vers(url_route('articles'));
        }

        $nomAuteur = trim((string) $utilisateurCourant['prenom'].' '.(string) $utilisateurCourant['nom']);

        $this->depotArticles->creer([
            'identifiant_auteur' => $utilisateurCourant['identifiant'],
            'nom_auteur' => $nomAuteur !== '' ? $nomAuteur : (string) $utilisateurCourant['courriel'],
            'auteur_affiche' => $auteurAffiche,
            'titre' => $titre,
            'resume' => $resume,
            'contenu' => $contenuTexte,
            'blocs' => $blocsArticle['blocs'],
        ]);

        ajouter_message_flash('success', 'Votre article a été enregistré et attend validation.');
        rediriger_vers(url_route('articles'));
    }

    /** Upload et enregistrement d'un media (reserve adherent/admin). */
    private function traiterSoumissionMedia(): void
    {
        $utilisateurCourant = $this->obtenirUtilisateurCourant();

        if ($utilisateurCourant === null) {
            ajouter_message_flash('error', 'Vous devez être connecté pour proposer un média.');
            rediriger_vers(url_route('mediatheque'));
        }

        if (! $this->utilisateurPeutPublierContenu($utilisateurCourant)) {
            ajouter_message_flash('error', 'Seuls les adhérents du club peuvent proposer des photos ou des vidéos.');
            rediriger_vers(url_route('mediatheque'));
        }

        $titre = trim((string) ($_POST['titre_media'] ?? $_POST['media_title'] ?? ''));
        $description = trim((string) ($_POST['description_media'] ?? $_POST['media_description'] ?? ''));
        $typeMedia = trim((string) ($_POST['type_media'] ?? $_POST['media_type'] ?? ''));
        $fichier = $_FILES['media_fichier'] ?? null;

        $erreurs = [];

        if ($titre === '' || mb_strlen($titre) > 150) {
            $erreurs[] = 'Le titre du média est obligatoire et doit rester inférieur à 150 caractères.';
        }

        if (mb_strlen($description) > 500) {
            $erreurs[] = 'La description du média doit rester inférieure à 500 caractères.';
        }

        if (! in_array($typeMedia, [DepotMedias::TYPE_PHOTO, DepotMedias::TYPE_VIDEO], true)) {
            $erreurs[] = 'Le type de media est invalide.';
        }

        if (! is_array($fichier) || (($fichier['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK)) {
            $erreurs[] = 'Un fichier valide est obligatoire.';
        }

        if ($erreurs !== []) {
            ajouter_message_flash('error', implode(' ', $erreurs));
            rediriger_vers(url_route('mediatheque'));
        }

        $validationFichier = $this->validerFichierMedia($fichier, $typeMedia);

        if ($validationFichier['erreurs'] !== []) {
            ajouter_message_flash('error', implode(' ', $validationFichier['erreurs']));
            rediriger_vers(url_route('mediatheque'));
        }

        if (! $this->preparerDossierUpload($this->dossierUploadMedias)) {
            ajouter_message_flash('error', "Le dossier d'envoi des medias n'est pas disponible.");
            rediriger_vers(url_route('mediatheque'));
        }

        $nomStocke = 'media_'.bin2hex(random_bytes(12)).'.'.$validationFichier['extension'];
        $cheminDestination = rtrim($this->dossierUploadMedias, '/\\').DIRECTORY_SEPARATOR.$nomStocke;

        if (! $this->deplacerFichierTeleverse((string) $fichier['tmp_name'], $cheminDestination)) {
            ajouter_message_flash('error', 'Le téléversement du média a échoué.');
            rediriger_vers(url_route('mediatheque'));
        }

        $this->securiserFichierTeleverse($cheminDestination);

        $nomAuteur = trim((string) $utilisateurCourant['prenom'].' '.(string) $utilisateurCourant['nom']);

        $this->depotMedias->creer([
            'identifiant_auteur' => $utilisateurCourant['identifiant'],
            'nom_auteur' => $nomAuteur !== '' ? $nomAuteur : (string) $utilisateurCourant['courriel'],
            'type_media' => $typeMedia,
            'titre' => $titre,
            'description' => $description,
            'nom_fichier_original' => (string) ($fichier['name'] ?? ''),
            'nom_fichier_stocke' => $nomStocke,
            'chemin_public' => UploadStorage::cheminMedia($nomStocke),
            'type_mime' => $validationFichier['mime'],
            'taille_octets' => (int) ($fichier['size'] ?? 0),
        ]);

        ajouter_message_flash('success', 'Votre média a été envoyé et attend validation.');
        rediriger_vers(url_route('mediatheque'));
    }

    /** Moderation d'un article (admin). */
    private function traiterModerationArticle(): void
    {
        $this->exigerAdmin();

        $identifiantArticle = trim((string) ($_POST['identifiant_article'] ?? ''));
        $statut = trim((string) ($_POST['statut_article'] ?? ''));

        $articleAvantModeration = $identifiantArticle !== ''
            ? $this->depotArticles->trouverParIdentifiant($identifiantArticle)
            : null;
        $articleMisAJour = $identifiantArticle !== ''
            ? $this->depotArticles->changerStatut($identifiantArticle, $statut)
            : null;

        if (
            $articleMisAJour !== null
            && $statut === DepotArticles::STATUT_PUBLIE
            && ($articleAvantModeration['statut'] ?? '') !== DepotArticles::STATUT_PUBLIE
        ) {
            $this->notifierArticlePublie($articleMisAJour);
        }

        if ($articleMisAJour === null) {
            ajouter_message_flash('error', "Impossible de mettre à jour l'article.");
            rediriger_vers(url_route('admin'));
        }

        ajouter_message_flash('success', "Le statut de l'article a été mis à jour.");
        rediriger_vers(url_route('admin'));
    }

    /** Suppression definitive d'un article (admin). */
    private function traiterSuppressionArticle(): void
    {
        $this->exigerAdmin();

        $identifiantArticle = trim((string) ($_POST['identifiant_article'] ?? ''));
        $article = $identifiantArticle !== '' ? $this->depotArticles->trouverParIdentifiant($identifiantArticle) : null;

        if ($article === null || ! $this->depotArticles->supprimer($identifiantArticle)) {
            ajouter_message_flash('error', "Impossible de supprimer l'article.");
            rediriger_vers(url_route('admin'));
        }

        $this->supprimerMediasArticleTeleverses($article['blocs'] ?? []);
        ajouter_message_flash('success', "L'article a été supprimé.");
        rediriger_vers(url_route('admin'));
    }

    /** Moderation d'un media (admin). */
    private function traiterModerationMedia(): void
    {
        $this->exigerAdmin();

        $identifiantMedia = trim((string) ($_POST['identifiant_media'] ?? ''));
        $statut = trim((string) ($_POST['statut_media'] ?? ''));

        if ($identifiantMedia === '' || $this->depotMedias->changerStatut($identifiantMedia, $statut) === null) {
            ajouter_message_flash('error', 'Impossible de mettre à jour le média.');
            rediriger_vers(url_route('admin'));
        }

        ajouter_message_flash('success', 'Le statut du média a été mis à jour.');
        rediriger_vers(url_route('admin'));
    }

    /** Cree une commande produit (compte connecte). */
    private function traiterCommandeProduit(): void
    {
        $utilisateurCourant = $this->obtenirUtilisateurCourant();

        if ($utilisateurCourant === null || ($utilisateurCourant['statut_compte'] ?? '') !== DepotUtilisateurs::STATUT_COMPTE_ACTIF) {
            ajouter_message_flash('error', 'Vous devez être connecté pour commander un article.');
            rediriger_vers(url_route('accueil'));
        }

        $produit = trim((string) ($_POST['produit'] ?? ''));
        $categorie = trim((string) ($_POST['categorie'] ?? ''));

        if ($produit === '' || $categorie === '') {
            ajouter_message_flash('error', 'Produit invalide.');
            rediriger_vers(url_route('boutique'));
        }

        $nomUtilisateur = trim((string) $utilisateurCourant['prenom'].' '.(string) $utilisateurCourant['nom']);

        $this->depotCommandes->creer([
            'identifiant_utilisateur' => $utilisateurCourant['identifiant'],
            'nom_utilisateur' => $nomUtilisateur !== '' ? $nomUtilisateur : (string) $utilisateurCourant['courriel'],
            'produit' => $produit,
            'categorie' => $categorie,
        ]);

        ajouter_message_flash('success', 'La commande a été enregistrée avec le statut En attente.');
        rediriger_vers(url_route('boutique'));
    }

    /** Met a jour le statut d'une commande (admin). */
    private function traiterMiseAJourStatutCommande(): void
    {
        $this->exigerAdmin();

        $identifiantCommande = trim((string) ($_POST['identifiant_commande'] ?? ''));
        $statut = trim((string) ($_POST['statut_commande'] ?? ''));

        if ($identifiantCommande === '' || $this->depotCommandes->changerStatut($identifiantCommande, $statut) === null) {
            ajouter_message_flash('error', 'Impossible de mettre à jour la commande.');
            rediriger_vers(url_route('admin'));
        }

        ajouter_message_flash('success', 'Le statut de la commande a été mis à jour.');
        rediriger_vers(url_route('admin'));
    }

    /** Met a jour role/statut d'un utilisateur (admin). */
    private function traiterMiseAJourAccesUtilisateur(): void
    {
        $administrateur = $this->obtenirUtilisateurCourant();
        $this->exigerAdmin();

        $identifiantUtilisateur = trim((string) ($_POST['identifiant_utilisateur_cible'] ?? ''));
        $role = trim((string) ($_POST['role_utilisateur'] ?? ''));
        $statutCompte = trim((string) ($_POST['statut_compte_utilisateur'] ?? ''));
        $statutAdhesion = trim((string) ($_POST['statut_adhesion_utilisateur'] ?? ''));

        if ($identifiantUtilisateur === '') {
            ajouter_message_flash('error', 'Utilisateur cible introuvable.');
            rediriger_vers(url_route('admin'));
        }

        if ($administrateur !== null && $administrateur['identifiant'] === $identifiantUtilisateur && $role !== DepotUtilisateurs::ROLE_ADMIN) {
            ajouter_message_flash('error', "L'administrateur principal ne peut pas retirer son propre rôle admin ici.");
            rediriger_vers(url_route('admin'));
        }

        $utilisateur = $this->depotUtilisateurs->mettreAJourAcces($identifiantUtilisateur, $role, $statutCompte, $statutAdhesion);

        if ($utilisateur === null) {
            ajouter_message_flash('error', "Impossible de mettre à jour les accès de l'utilisateur.");
            rediriger_vers(url_route('admin'));
        }

        ajouter_message_flash('success', "Les accès de l'utilisateur ont été mis à jour.");
        rediriger_vers(url_route('admin'));
    }

    /** Transfere le role admin vers un autre compte. */
    private function traiterTransfertRoleAdmin(): void
    {
        $administrateur = $this->obtenirUtilisateurCourant();
        $this->exigerAdmin();

        $identifiantUtilisateurCible = trim((string) ($_POST['identifiant_utilisateur_cible'] ?? ''));
        $roleApresTransfert = trim((string) ($_POST['role_apres_transfert'] ?? DepotUtilisateurs::ROLE_PROF));

        if ($administrateur === null || $identifiantUtilisateurCible === '') {
            ajouter_message_flash('error', 'Compte cible introuvable pour le transfert admin.');
            rediriger_vers(url_route('admin'));
        }

        $utilisateurMisAJour = $this->depotUtilisateurs->transfererRoleAdmin(
            (string) $administrateur['identifiant'],
            $identifiantUtilisateurCible,
            $roleApresTransfert
        );

        if ($utilisateurMisAJour === null) {
            ajouter_message_flash('error', 'Le transfert du rôle admin a échoué.');
            rediriger_vers(url_route('admin'));
        }

        ajouter_message_flash('success', 'Le rôle admin a été transféré vers un autre compte.');
        rediriger_vers(url_route('admin'));
    }

    /** Met a jour les horaires publics du club (admin). */
    private function traiterMiseAJourHorairesClub(): void
    {
        $this->exigerAdmin();

        $jours = is_array($_POST['horaire_jour'] ?? null) ? $_POST['horaire_jour'] : [];
        $heures = is_array($_POST['horaire_heure'] ?? null) ? $_POST['horaire_heure'] : [];
        $titres = is_array($_POST['horaire_titre'] ?? null) ? $_POST['horaire_titre'] : [];
        $details = is_array($_POST['horaire_details'] ?? null) ? $_POST['horaire_details'] : [];
        $indicesFeries = is_array($_POST['horaire_jour_ferie'] ?? null) ? $_POST['horaire_jour_ferie'] : [];
        $creneaux = [];
        $nombreLignes = max(count($jours), count($heures), count($titres), count($details));

        for ($index = 0; $index < $nombreLignes; $index++) {
            $creneaux[] = [
                'day' => (string) ($jours[$index] ?? ''),
                'time' => (string) ($heures[$index] ?? ''),
                'title' => (string) ($titres[$index] ?? ''),
                'details' => (string) ($details[$index] ?? ''),
                'is_holiday' => in_array((string) $index, array_map('strval', $indicesFeries), true),
            ];
        }

        $succes = $this->depotHoraires->mettreAJour(
            (string) ($_POST['libelle_saison_horaires'] ?? ''),
            (string) ($_POST['message_jour_ferie'] ?? ''),
            $creneaux
        );

        if ($succes) {
            $this->notifierHorairesMisAJour((string) ($_POST['libelle_saison_horaires'] ?? 'Horaires du club'));
        }

        if (! $succes) {
            ajouter_message_flash('error', 'Au moins un créneau doit contenir un jour et un horaire.');
            rediriger_vers(url_route('admin').'#admin-horaires-club');
        }

        ajouter_message_flash('success', 'Les horaires publics du club ont été mis à jour.');
        rediriger_vers(url_route('admin').'#admin-horaires-club');
    }

    private function traiterNotificationObjetBoutique(): void
    {
        $this->exigerAdmin();

        $titreProduit = trim((string) ($_POST['titre_objet_boutique'] ?? ''));

        if ($titreProduit === '' || mb_strlen($titreProduit) > 150) {
            ajouter_message_flash('error', "Le titre de l'objet boutique est obligatoire et doit rester court.");
            rediriger_vers(url_route('admin').'#admin-newsletter-boutique');
        }

        $this->notifierNouvelObjetBoutique($titreProduit);

        ajouter_message_flash('success', 'Les abonnés newsletter ont été informés de la nouveauté boutique.');
        rediriger_vers(url_route('admin').'#admin-newsletter-boutique');
    }

    /**
     * Recoit un score du mini-jeu dammier et renvoie le classement mis a jour.
     */
    private function traiterSoumissionResultatDammier(): void
    {
        $utilisateurCourant = $this->obtenirUtilisateurCourant();

        if ($utilisateurCourant === null) {
            $this->repondreJson([
                'success' => false,
                'message' => 'Connexion requise pour enregistrer un score.',
            ], 401);
        }

        $puzzleId = trim((string) ($_POST['dammier_puzzle_id'] ?? ''));
        $weekKey = trim((string) ($_POST['dammier_week_key'] ?? ''));
        $movesCount = (int) ($_POST['dammier_moves_count'] ?? 0);
        $elapsedSeconds = (int) ($_POST['dammier_elapsed_seconds'] ?? 0);

        if (
            $puzzleId === ''
            || $weekKey === ''
            || $movesCount < 1
            || $movesCount > 99
            || $elapsedSeconds < 1
            || $elapsedSeconds > 7200
        ) {
            $this->repondreJson([
                'success' => false,
                'message' => 'Les données du score dammier sont invalides.',
            ], 422);
        }

        if (! $this->depotDammier->verifierPuzzleHebdomadaire($weekKey, $puzzleId)) {
            $this->repondreJson([
                'success' => false,
                'message' => 'Le puzzle hebdomadaire a changé. Recharge la page.',
            ], 409);
        }

        $puzzle = $this->depotDammier->obtenirPuzzleHebdomadaire();
        $score = $this->depotDammier->enregistrerScoreHebdomadaire($utilisateurCourant, $puzzle, $movesCount, $elapsedSeconds);
        $classement = $this->depotDammier->listerClassementHebdomadaire($weekKey, $puzzleId);
        $statutScore = (string) ($score['dammier_record_status'] ?? '');
        $message = match ($statutScore) {
            'improved' => 'Ton score a ete ameliore dans le classement.',
            'unchanged' => 'Ton meilleur score etait deja meilleur. Le classement reste inchange.',
            default => 'Score dammier enregistr?.',
        };

        $this->repondreJson([
            'success' => true,
            'message' => $message,
            'dammier_score' => $score,
            'dammier_classement' => $classement,
        ]);
    }

    private function notifierArticlePublie(array $article): void
    {
        if ($this->newsletterMailer === null) {
            return;
        }

        try {
            $this->newsletterMailer->notifierArticlePublie($article);
        } catch (Throwable $exception) {
            error_log('[newsletter-article] '.$exception->getMessage());
        }
    }

    private function notifierHorairesMisAJour(string $libelleSaison): void
    {
        if ($this->newsletterMailer === null) {
            return;
        }

        try {
            $this->newsletterMailer->notifierHorairesMisAJour($libelleSaison);
        } catch (Throwable $exception) {
            error_log('[newsletter-horaires] '.$exception->getMessage());
        }
    }

    private function notifierNouvelObjetBoutique(string $titreProduit): void
    {
        if ($this->newsletterMailer === null) {
            return;
        }

        try {
            $this->newsletterMailer->notifierNouvelObjetBoutique($titreProduit);
        } catch (Throwable $exception) {
            error_log('[newsletter-boutique] '.$exception->getMessage());
        }
    }

    /**
     * Recupere l'utilisateur courant depuis la session.
     *
     * @return array|null Utilisateur normalise, ou null si non connecte.
     */
    private function obtenirUtilisateurCourant(): ?array
    {
        $identifiantUtilisateur = identifiant_utilisateur_courant() ?? '';

        return $this->depotUtilisateurs->trouverParIdentifiant($identifiantUtilisateur);
    }

    private function hacherAdresseIp(): string
    {
        $adresseIp = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        $selConsentement = trim((string) (getenv('NEWSLETTER_CONSENT_SALT') ?: ''));

        if ($adresseIp === '' || $selConsentement === '') {
            return '';
        }

        return hash('sha256', $adresseIp.'|'.$selConsentement);
    }

    private function nettoyerAgentUtilisateur(): string
    {
        $agentUtilisateur = trim(strip_tags((string) ($_SERVER['HTTP_USER_AGENT'] ?? '')));

        return function_exists('mb_substr') ? mb_substr($agentUtilisateur, 0, 255) : substr($agentUtilisateur, 0, 255);
    }

    /**
     * D?termin? une page de redirection sure (whitelist).
     *
     * @param  string  $pageParDefaut  Fallback.
     * @return string Page valide.
     */
    private function resoudrePageRedirection(string $pageParDefaut): string
    {
        $page = trim((string) ($_POST['page_redirection'] ?? $_POST['redirect_page'] ?? ''));

        if ($page === '' || ! in_array($page, self::PAGES_AUTORISEES, true)) {
            return $pageParDefaut;
        }

        return $page;
    }

    /**
     * Valide les donnees de profil (nom/prenom/email/naissance/description/pseudo chess).
     *
     * @param  array  $donnees  Donnees a verifier.
     * @param  bool  $verifierMotDePasse  True pour l'inscription.
     * @return array Liste d'erreurs.
     */
    private function validerDonneesProfil(array $donnees, bool $verifierMotDePasse): array
    {
        $erreurs = [];

        if ($donnees['nom'] === '' || mb_strlen($donnees['nom']) > 100) {
            $erreurs[] = 'Le nom est obligatoire et doit rester raisonnable.';
        }

        if ($donnees['prenom'] === '' || mb_strlen($donnees['prenom']) > 100) {
            $erreurs[] = 'Le prenom est obligatoire et doit rester raisonnable.';
        }

        if ($donnees['date_naissance'] !== '' && ! $this->estDateValide($donnees['date_naissance'])) {
            $erreurs[] = 'La date de naissance doit respecter le format attendu.';
        }

        if (! filter_var($donnees['courriel'], FILTER_VALIDATE_EMAIL)) {
            $erreurs[] = 'Veuillez saisir une adresse email valide.';
        }

        if (! $this->estNumeroLicenceValide((string) ($donnees['numero_licence'] ?? ''))) {
            $erreurs[] = 'Le numero de licence doit contenir 3 a 30 caracteres: lettres, chiffres ou tirets.';
        }

        if ($verifierMotDePasse && mb_strlen($donnees['mot_de_passe']) < 8) {
            $erreurs[] = 'Le mot de passe doit contenir au moins 8 caracteres.';
        }

        if (mb_strlen($donnees['description_profil']) > 1200) {
            $erreurs[] = 'La description de profil doit rester inférieure à 1200 caractères.';
        }

        if (! $this->estPseudoChessValide($donnees['pseudo_chess'])) {
            $erreurs[] = 'Le pseudo Chess.com doit contenir seulement des lettres, chiffres, tirets ou underscores.';
        }

        return $erreurs;
    }

    /**
     * Construit des blocs d'article propres depuis le formulaire editeur.
     *
     * @return array{blocs: array, erreurs: array}
     */
    private function normaliserBlocsArticleDepuisFormulaire(): array
    {
        $payload = (string) ($_POST['article_blocks_payload'] ?? '');
        $donnees = json_decode($payload, true);
        $erreurs = [];
        $blocs = [];

        if (! is_array($donnees)) {
            return [
                'blocs' => [],
                'erreurs' => ["L'editeur d'article n'a pas transmis de contenu valide."],
            ];
        }

        if (count($donnees) > 60) {
            $erreurs[] = "L'article contient trop de blocs.";
        }

        foreach (array_slice($donnees, 0, 60) as $bloc) {
            if (! is_array($bloc)) {
                continue;
            }

            $type = (string) ($bloc['type'] ?? '');
            $texte = trim((string) ($bloc['texte'] ?? ''));

            if ($type === DepotArticles::TYPE_BLOC_PARAGRAPHE) {
                if ($texte === '') {
                    continue;
                }

                if (mb_strlen($texte) > 3000) {
                    $erreurs[] = 'Un paragraphe doit rester inférieur à 3000 caractères.';

                    continue;
                }

                $blocs[] = [
                    'type' => DepotArticles::TYPE_BLOC_PARAGRAPHE,
                    'texte' => $texte,
                ];

                continue;
            }

            if ($type === DepotArticles::TYPE_BLOC_SOUS_TITRE) {
                if ($texte === '' || mb_strlen($texte) > 140) {
                    $erreurs[] = 'Chaque sous-titre doit contenir entre 1 et 140 caracteres.';

                    continue;
                }

                $blocs[] = [
                    'type' => DepotArticles::TYPE_BLOC_SOUS_TITRE,
                    'texte' => $texte,
                ];

                continue;
            }

            if (in_array($type, [DepotArticles::TYPE_BLOC_IMAGE, DepotArticles::TYPE_BLOC_VIDEO], true)) {
                $blocMedia = $this->traiterBlocMediaArticle($bloc, $type);
                $erreurs = [...$erreurs, ...$blocMedia['erreurs']];

                if ($blocMedia['bloc'] !== null) {
                    $blocs[] = $blocMedia['bloc'];
                }
            }
        }

        if ($blocs === []) {
            $erreurs[] = "Ajoute au moins un paragraphe, un sous-titre ou un media a l'article.";
        }

        return [
            'blocs' => $blocs,
            'erreurs' => array_values(array_unique($erreurs)),
        ];
    }

    /**
     * @return array{bloc: array|null, erreurs: array}
     */
    private function traiterBlocMediaArticle(array $bloc, string $type): array
    {
        $nomChampFichier = (string) ($bloc['nom_champ_fichier'] ?? '');
        $texteAlternatif = trim((string) ($bloc['texte_alternatif'] ?? ''));
        $legende = trim((string) ($bloc['legende'] ?? ''));

        if (! preg_match('/^article_media_[a-z0-9_]+$/', $nomChampFichier)) {
            return [
                'bloc' => null,
                'erreurs' => ['Un bloc media est invalide.'],
            ];
        }

        $fichier = $_FILES[$nomChampFichier] ?? null;

        if (! is_array($fichier) || (($fichier['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK)) {
            return [
                'bloc' => null,
                'erreurs' => ['Chaque bloc image ou video doit contenir un fichier valide.'],
            ];
        }

        if ($texteAlternatif === '' || mb_strlen($texteAlternatif) > 180) {
            return [
                'bloc' => null,
                'erreurs' => $type === DepotArticles::TYPE_BLOC_VIDEO
                    ? ['Chaque video doit avoir une description courte accessible.']
                    : ['Chaque image ou GIF doit avoir un texte alternatif court.'],
            ];
        }

        if (mb_strlen($legende) > 220) {
            return [
                'bloc' => null,
                'erreurs' => ['Une légende média doit rester inférieure à 220 caractères.'],
            ];
        }

        $validation = $this->validerFichierMedia(
            $fichier,
            $type === DepotArticles::TYPE_BLOC_VIDEO ? DepotMedias::TYPE_VIDEO : DepotMedias::TYPE_PHOTO
        );

        if ($validation['erreurs'] !== []) {
            return [
                'bloc' => null,
                'erreurs' => $validation['erreurs'],
            ];
        }

        $dossierArticles = UploadStorage::dossierArticles();

        if (! $this->preparerDossierUpload($dossierArticles)) {
            return [
                'bloc' => null,
                'erreurs' => ["Le dossier d'envoi des medias d'article n'est pas disponible."],
            ];
        }

        $nomStocke = 'article_'.bin2hex(random_bytes(12)).'.'.$validation['extension'];
        $cheminDestination = $dossierArticles.DIRECTORY_SEPARATOR.$nomStocke;

        if (! $this->deplacerFichierTeleverse((string) ($fichier['tmp_name'] ?? ''), $cheminDestination)) {
            return [
                'bloc' => null,
                'erreurs' => ["Le televersement d'un media d'article a echoue."],
            ];
        }

        $this->securiserFichierTeleverse($cheminDestination);

        return [
            'bloc' => [
                'type' => $type,
                'chemin_public' => UploadStorage::cheminArticle($nomStocke),
                'type_mime' => $validation['mime'],
                'texte_alternatif' => $texteAlternatif,
                'legende' => $legende,
                'nom_fichier_original' => (string) ($fichier['name'] ?? ''),
                'taille_octets' => (int) ($fichier['size'] ?? 0),
            ],
            'erreurs' => [],
        ];
    }

    private function extraireTexteArticle(array $blocs): string
    {
        $segments = [];

        foreach ($blocs as $bloc) {
            $type = (string) ($bloc['type'] ?? '');

            if (in_array($type, [DepotArticles::TYPE_BLOC_PARAGRAPHE, DepotArticles::TYPE_BLOC_SOUS_TITRE], true)) {
                $segments[] = trim((string) ($bloc['texte'] ?? ''));
            }

            if (in_array($type, [DepotArticles::TYPE_BLOC_IMAGE, DepotArticles::TYPE_BLOC_VIDEO], true)) {
                $segments[] = trim((string) ($bloc['texte_alternatif'] ?? ''));
                $segments[] = trim((string) ($bloc['legende'] ?? ''));
            }
        }

        return trim(preg_replace('/\s+/', ' ', implode(' ', array_filter($segments))) ?? '');
    }

    private function genererResumeArticle(array $blocs): string
    {
        foreach ($blocs as $bloc) {
            if (($bloc['type'] ?? '') === DepotArticles::TYPE_BLOC_PARAGRAPHE) {
                $texte = trim((string) ($bloc['texte'] ?? ''));

                if ($texte !== '') {
                    return mb_substr($texte, 0, 280);
                }
            }
        }

        return mb_substr($this->extraireTexteArticle($blocs), 0, 280);
    }

    private function supprimerMediasArticleTeleverses(array $blocs): void
    {
        $prefixePublic = 'assets/media/uploads/articles/';
        $prefixeProtege = 'fichiers/articles/';

        foreach ($blocs as $bloc) {
            if (! in_array((string) ($bloc['type'] ?? ''), [DepotArticles::TYPE_BLOC_IMAGE, DepotArticles::TYPE_BLOC_VIDEO], true)) {
                continue;
            }

            $cheminPublic = (string) ($bloc['chemin_public'] ?? '');

            if (! str_starts_with($cheminPublic, $prefixePublic) && ! str_starts_with($cheminPublic, $prefixeProtege)) {
                continue;
            }

            $nomFichier = basename($cheminPublic);

            if ($nomFichier === '' || ! str_starts_with($nomFichier, 'article_')) {
                continue;
            }

            UploadStorage::supprimerCheminArticle($nomFichier);
        }
    }

    private function preparerDossierUpload(string $dossier): bool
    {
        if (! is_dir($dossier) && ! mkdir($dossier, self::MODE_DOSSIER_UPLOAD, true)) {
            return false;
        }

        if (is_dir($dossier) && DIRECTORY_SEPARATOR !== '\\') {
            chmod($dossier, self::MODE_DOSSIER_UPLOAD);
        }

        return is_dir($dossier) && is_writable($dossier);
    }

    private function securiserFichierTeleverse(string $cheminFichier): void
    {
        if (is_file($cheminFichier) && DIRECTORY_SEPARATOR !== '\\') {
            chmod($cheminFichier, self::MODE_FICHIER_UPLOAD);
        }
    }

    private function deplacerFichierTeleverse(string $cheminSource, string $cheminDestination): bool
    {
        if ($cheminSource === '') {
            return false;
        }

        if (move_uploaded_file($cheminSource, $cheminDestination)) {
            return true;
        }

        if (! is_file($cheminSource)) {
            return false;
        }

        return @rename($cheminSource, $cheminDestination)
            || (@copy($cheminSource, $cheminDestination) && @unlink($cheminSource));
    }

    /**
     * @param  array<string, mixed>  $fichier
     * @return array{erreurs: array<int, string>, mime: string}
     */
    private function validerFichierPdfCours(array $fichier): array
    {
        $erreurs = [];
        $nomOriginal = mb_strtolower((string) ($fichier['name'] ?? ''));
        $extension = pathinfo($nomOriginal, PATHINFO_EXTENSION);
        $taille = (int) ($fichier['size'] ?? 0);
        $tailleMax = 20 * 1024 * 1024;
        $mimeClient = mb_strtolower((string) ($fichier['type'] ?? ''));
        $mimesAutorises = ['application/pdf', 'application/x-pdf'];
        $cheminTemporaire = (string) ($fichier['tmp_name'] ?? '');

        if ($taille <= 0 || $taille > $tailleMax) {
            $erreurs[] = 'Le document PDF doit faire moins de 20 Mo.';
        }

        if ($extension !== 'pdf') {
            $erreurs[] = 'Seuls les fichiers PDF sont acceptes.';
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo !== false ? (string) finfo_file($finfo, (string) ($fichier['tmp_name'] ?? '')) : '';
        if ($finfo !== false) {
            finfo_close($finfo);
        }

        $signaturePdf = is_file($cheminTemporaire)
            ? (string) file_get_contents($cheminTemporaire, false, null, 0, 5)
            : '';

        if (
            $signaturePdf !== '%PDF-'
            && ! in_array($mime, $mimesAutorises, true)
            && ! in_array($mimeClient, $mimesAutorises, true)
        ) {
            $erreurs[] = 'Le type de fichier envoyé doit être un PDF valide.';
        }

        return [
            'erreurs' => $erreurs,
            'mime' => in_array($mime, $mimesAutorises, true)
                ? $mime
                : (in_array($mimeClient, $mimesAutorises, true) ? $mimeClient : 'application/pdf'),
        ];
    }

    /**
     * Valide un fichier upload (type, extension, taille) selon photo/video.
     *
     * @param  array  $fichier  $_FILES[...] brut.
     * @param  string  $typeMedia  photo|video.
     * @return array {erreurs, extension, mime}
     */
    private function validerFichierMedia(array $fichier, string $typeMedia): array
    {
        $mimeAutorises = $typeMedia === DepotMedias::TYPE_VIDEO
            ? ['video/mp4', 'video/webm', 'video/quicktime']
            : ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $extensionsAutorisees = $typeMedia === DepotMedias::TYPE_VIDEO
            ? ['mp4', 'webm', 'mov']
            : ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $tailleMax = $typeMedia === DepotMedias::TYPE_VIDEO ? 50 * 1024 * 1024 : 8 * 1024 * 1024;

        $erreurs = [];
        $nomOriginal = mb_strtolower((string) ($fichier['name'] ?? ''));
        $extension = pathinfo($nomOriginal, PATHINFO_EXTENSION);
        $taille = (int) ($fichier['size'] ?? 0);

        if ($taille <= 0 || $taille > $tailleMax) {
            $erreurs[] = $typeMedia === DepotMedias::TYPE_VIDEO
                ? 'La video doit faire moins de 50 Mo.'
                : 'La photo doit faire moins de 8 Mo.';
        }

        if ($extension === '' || ! in_array($extension, $extensionsAutorisees, true)) {
            $erreurs[] = 'L extension du fichier n est pas autorisee.';
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo !== false ? (string) finfo_file($finfo, (string) ($fichier['tmp_name'] ?? '')) : '';
        if ($finfo !== false) {
            finfo_close($finfo);
        }

        if ($mime === '' || ! in_array($mime, $mimeAutorises, true)) {
            $erreurs[] = 'Le type de fichier envoye n est pas autorise.';
        }

        return [
            'erreurs' => $erreurs,
            'extension' => $extension,
            'mime' => $mime,
        ];
    }

    /**
     * Verifie si l'utilisateur a le droit de soumettre du contenu (article/media).
     *
     * @param  array  $utilisateur  Utilisateur normalise.
     * @return bool True si adherent/admin et compte actif.
     */
    private function utilisateurPeutPublierContenu(array $utilisateur): bool
    {
        if (($utilisateur['statut_compte'] ?? '') !== DepotUtilisateurs::STATUT_COMPTE_ACTIF) {
            return false;
        }

        return in_array(
            (string) ($utilisateur['role'] ?? ''),
            [DepotUtilisateurs::ROLE_ADHERENT, DepotUtilisateurs::ROLE_PROF, DepotUtilisateurs::ROLE_ADMIN],
            true
        );
    }

    /**
     * Force l'acces admin (sinon redirection accueil).
     */
    private function exigerAdmin(): void
    {
        $utilisateurCourant = $this->obtenirUtilisateurCourant();

        if (
            $utilisateurCourant === null
            || ($utilisateurCourant['role'] ?? '') !== DepotUtilisateurs::ROLE_ADMIN
            || ($utilisateurCourant['statut_compte'] ?? '') !== DepotUtilisateurs::STATUT_COMPTE_ACTIF
        ) {
            ajouter_message_flash('error', "Accès réservé à l'administrateur du site.");
            rediriger_vers(url_route('accueil'));
        }
    }

    /**
     * Force l'acces prof/admin pour les documents pedagogiques.
     *
     * @return array<string, mixed>
     */
    private function exigerProfOuAdmin(): array
    {
        $utilisateurCourant = $this->obtenirUtilisateurCourant();

        if (
            $utilisateurCourant === null
            || ($utilisateurCourant['statut_compte'] ?? '') !== DepotUtilisateurs::STATUT_COMPTE_ACTIF
            || ! in_array((string) ($utilisateurCourant['role'] ?? ''), [DepotUtilisateurs::ROLE_PROF, DepotUtilisateurs::ROLE_ADMIN], true)
        ) {
            ajouter_message_flash('error', "Accès réservé aux professeurs et à l'administrateur.");
            rediriger_vers(url_route('guide').'#cours-livrets');
        }

        return $utilisateurCourant;
    }

    private function ancreDocumentCours(string $rubrique): string
    {
        return match ($rubrique) {
            'livret_a' => 'cours-livret-a',
            'livret_b' => 'cours-livret-b',
            'livret_c' => 'cours-livret-c',
            'livret_d' => 'cours-livret-d',
            'livret_e' => 'cours-livret-e',
            'methodologie' => 'cours-methodologie',
            'strategie' => 'cours-strategie',
            default => 'cours-cours',
        };
    }

    private function pageCoursDepuisRubrique(string $rubrique): string
    {
        return match ($rubrique) {
            'livret_a' => 'cours-livret-a',
            'livret_b' => 'cours-livret-b',
            'livret_c' => 'cours-livret-c',
            'livret_d' => 'cours-livret-d',
            'livret_e' => 'cours-livret-e',
            'cours' => 'cours-seances',
            'methodologie' => 'cours-methodologie',
            'strategie' => 'cours-strategie',
            default => 'guide',
        };
    }

    private function urlPageCoursRubrique(string $rubrique): string
    {
        $page = $this->pageCoursDepuisRubrique($rubrique);

        return url_route($page).'#'.$this->ancreDocumentCours($rubrique);
    }

    /** Verifie le format du numero de licence federale. */
    private function estNumeroLicenceValide(string $valeur): bool
    {
        if ($valeur === '') {
            return true;
        }

        return mb_strlen($valeur) >= 3
            && mb_strlen($valeur) <= 30
            && preg_match('/^[A-Z0-9-]+$/', $valeur) === 1;
    }

    private function numeroLicenceDejaUtilise(string $numeroLicence, ?string $identifiantIgnore = null): bool
    {
        if ($numeroLicence === '') {
            return false;
        }

        $utilisateur = $this->depotUtilisateurs->trouverParNumeroLicence($numeroLicence);

        return $utilisateur !== null
            && ($identifiantIgnore === null || ($utilisateur['identifiant'] ?? '') !== $identifiantIgnore);
    }

    private function validerCourrielPourInscription(string $courriel, string $numeroLicence): ?string
    {
        $comptesMemeCourriel = $this->depotUtilisateurs->listerParCourriel($courriel);

        if ($comptesMemeCourriel === []) {
            return null;
        }

        if ($numeroLicence === '') {
            return 'Un compte existe deja avec cet email. Pour partager cet email entre plusieurs comptes, chaque compte doit avoir son propre numero de licence.';
        }

        foreach ($comptesMemeCourriel as $compte) {
            if (trim((string) ($compte['numero_licence'] ?? '')) === '') {
                return 'Cet email est deja utilise par un compte sans numero de licence. Ajoute d abord un numero de licence a ce compte ou utilise un autre email.';
            }
        }

        return null;
    }

    /** Verifie le format de date 'Y-m-d'. */
    private function estDateValide(string $valeur): bool
    {
        $date = DateTimeImmutable::createFromFormat('Y-m-d', $valeur);

        return $date instanceof DateTimeImmutable && $date->format('Y-m-d') === $valeur;
    }

    /** Verifie le pseudo Chess.com (alphanum + _ -). */
    private function estPseudoChessValide(string $valeur): bool
    {
        if ($valeur === '') {
            return true;
        }

        return mb_strlen($valeur) <= 50 && preg_match('/^[A-Za-z0-9_-]+$/', $valeur) === 1;
    }

    /**
     * Termine la requete avec une reponse JSON.
     */
    private function repondreJson(array $payload, int $statusCode = 200): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
