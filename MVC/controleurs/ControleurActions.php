<?php

declare(strict_types=1);

final class ControleurActions
{
    private const PAGES_AUTORISEES = [
        'accueil',
        'guide',
        'mediatheque',
        'articles',
        'boutique',
        'club',
        'activites',
        'contact',
        'profil',
        'parametres',
    ];

    public function __construct(
        private DepotUtilisateurs $depotUtilisateurs,
        private DepotArticles $depotArticles
    ) {
    }

    public function traiter(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return;
        }

        $action = isset($_POST['action']) ? trim((string) $_POST['action']) : '';

        if ($action === '') {
            return;
        }

        if (!verifier_jeton_csrf($_POST['jeton_csrf'] ?? null)) {
            ajouter_message_flash('error', 'Votre session a expiré. Merci de recommencer.');
            rediriger_vers(url_route('accueil'));
        }

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
            default:
                ajouter_message_flash('error', 'Action non prise en charge.');
                rediriger_vers(url_route('accueil'));
        }
    }

    private function traiterInscription(): void
    {
        $pageRedirection = $this->resoudrePageRedirection('accueil');
        $donnees = [
            'nom' => trim((string) ($_POST['nom'] ?? $_POST['last_name'] ?? '')),
            'prenom' => trim((string) ($_POST['prenom'] ?? $_POST['first_name'] ?? '')),
            'date_naissance' => trim((string) ($_POST['date_naissance'] ?? $_POST['birth_date'] ?? '')),
            'courriel' => trim((string) ($_POST['courriel'] ?? $_POST['email'] ?? '')),
            'mot_de_passe' => (string) ($_POST['mot_de_passe'] ?? $_POST['password'] ?? ''),
            'description_profil' => trim((string) ($_POST['description_profil'] ?? $_POST['profile_description'] ?? '')),
            'pseudo_chess' => trim((string) ($_POST['pseudo_chess'] ?? '')),
        ];

        $erreurs = [];

        if ($donnees['nom'] === '' || mb_strlen($donnees['nom']) > 100) {
            $erreurs[] = 'Le nom est obligatoire et doit rester raisonnable.';
        }

        if ($donnees['prenom'] === '' || mb_strlen($donnees['prenom']) > 100) {
            $erreurs[] = 'Le prénom est obligatoire et doit rester raisonnable.';
        }

        if ($donnees['date_naissance'] !== '' && !$this->estDateValide($donnees['date_naissance'])) {
            $erreurs[] = 'La date de naissance doit respecter le format attendu.';
        }

        if (!filter_var($donnees['courriel'], FILTER_VALIDATE_EMAIL)) {
            $erreurs[] = 'Veuillez saisir une adresse email valide.';
        }

        if (mb_strlen($donnees['mot_de_passe']) < 8) {
            $erreurs[] = 'Le mot de passe doit contenir au moins 8 caractères.';
        }

        if (mb_strlen($donnees['description_profil']) > 1200) {
            $erreurs[] = 'La description de profil doit rester inférieure à 1200 caractères.';
        }

        if (!$this->estPseudoChessValide($donnees['pseudo_chess'])) {
            $erreurs[] = 'Le pseudo Chess.com doit contenir seulement des lettres, chiffres, tirets ou underscores.';
        }

        if ($this->depotUtilisateurs->trouverParCourriel($donnees['courriel']) !== null) {
            $erreurs[] = 'Un compte existe déjà avec cet email.';
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
        session_regenerate_id(true);
        $_SESSION['identifiant_utilisateur'] = $utilisateur['identifiant'];
        ajouter_message_flash('success', 'Votre compte a été créé avec succès.');
        rediriger_vers(url_route('profil'));
    }

    private function traiterConnexion(): void
    {
        $pageRedirection = $this->resoudrePageRedirection('accueil');
        $courriel = trim((string) ($_POST['courriel'] ?? $_POST['email'] ?? ''));
        $motDePasse = (string) ($_POST['mot_de_passe'] ?? $_POST['password'] ?? '');
        $utilisateur = $this->depotUtilisateurs->trouverParCourriel($courriel);

        if ($utilisateur === null || !password_verify($motDePasse, (string) ($utilisateur['mot_de_passe_hache'] ?? ''))) {
            memoriser_etat_formulaire([
                'ouverte' => true,
                'onglet' => 'connexion',
                'erreurs' => ['Email ou mot de passe incorrect.'],
                'anciennes_valeurs' => ['courriel' => $courriel],
            ]);
            rediriger_vers(url_route($pageRedirection));
        }

        session_regenerate_id(true);
        $_SESSION['identifiant_utilisateur'] = $utilisateur['identifiant'];
        ajouter_message_flash('success', 'Connexion réussie.');
        rediriger_vers(url_route('profil'));
    }

    private function traiterDeconnexion(): void
    {
        unset($_SESSION['identifiant_utilisateur']);
        session_regenerate_id(true);
        ajouter_message_flash('success', 'Vous avez été déconnecté.');
        rediriger_vers(url_route('accueil'));
    }

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
        ];

        $erreurs = [];

        if ($donnees['nom'] === '' || mb_strlen($donnees['nom']) > 100) {
            $erreurs[] = 'Le nom est obligatoire et doit rester raisonnable.';
        }

        if ($donnees['prenom'] === '' || mb_strlen($donnees['prenom']) > 100) {
            $erreurs[] = 'Le prénom est obligatoire et doit rester raisonnable.';
        }

        if ($donnees['date_naissance'] !== '' && !$this->estDateValide($donnees['date_naissance'])) {
            $erreurs[] = 'La date de naissance doit respecter le format attendu.';
        }

        if (mb_strlen($donnees['description_profil']) > 1200) {
            $erreurs[] = 'La description de profil doit rester inférieure à 1200 caractères.';
        }

        if (!$this->estPseudoChessValide($donnees['pseudo_chess'])) {
            $erreurs[] = 'Le pseudo Chess.com doit contenir seulement des lettres, chiffres, tirets ou underscores.';
        }

        if ($erreurs !== []) {
            ajouter_message_flash('error', implode(' ', $erreurs));
            rediriger_vers(url_route('profil'));
        }

        $this->depotUtilisateurs->mettreAJour((string) $utilisateurCourant['identifiant'], $donnees);
        ajouter_message_flash('success', 'Votre profil a été mis à jour.');
        rediriger_vers(url_route('profil'));
    }

    private function traiterCreationArticle(): void
    {
        $utilisateurCourant = $this->obtenirUtilisateurCourant();

        if ($utilisateurCourant === null) {
            ajouter_message_flash('error', 'Vous devez être connecté pour proposer un article.');
            rediriger_vers(url_route('articles'));
        }

        $titre = trim((string) ($_POST['titre'] ?? $_POST['title'] ?? ''));
        $resume = trim((string) ($_POST['resume'] ?? $_POST['excerpt'] ?? ''));
        $contenu = trim((string) ($_POST['contenu'] ?? $_POST['content'] ?? ''));

        $erreurs = [];

        if ($titre === '' || mb_strlen($titre) > 150) {
            $erreurs[] = 'Le titre est obligatoire et doit rester inférieur à 150 caractères.';
        }

        if ($resume === '' || mb_strlen($resume) > 280) {
            $erreurs[] = 'Le résumé est obligatoire et doit rester inférieur à 280 caractères.';
        }

        if (mb_strlen($contenu) < 80) {
            $erreurs[] = "Le contenu de l'article doit contenir au moins 80 caractères.";
        }

        if ($erreurs !== []) {
            ajouter_message_flash('error', implode(' ', $erreurs));
            rediriger_vers(url_route('articles'));
        }

        $nomAuteur = trim((string) $utilisateurCourant['prenom'] . ' ' . (string) $utilisateurCourant['nom']);

        $this->depotArticles->creer([
            'identifiant_auteur' => $utilisateurCourant['identifiant'],
            'nom_auteur' => $nomAuteur !== '' ? $nomAuteur : (string) $utilisateurCourant['courriel'],
            'titre' => $titre,
            'resume' => $resume,
            'contenu' => $contenu,
        ]);

        ajouter_message_flash('success', 'Votre article a été enregistré et attend maintenant une validation future.');
        rediriger_vers(url_route('articles'));
    }

    private function obtenirUtilisateurCourant(): ?array
    {
        $identifiantUtilisateur = isset($_SESSION['identifiant_utilisateur']) ? (string) $_SESSION['identifiant_utilisateur'] : '';

        return $this->depotUtilisateurs->trouverParIdentifiant($identifiantUtilisateur);
    }

    private function resoudrePageRedirection(string $pageParDefaut): string
    {
        $page = trim((string) ($_POST['page_redirection'] ?? $_POST['redirect_page'] ?? ''));

        if ($page === '' || !in_array($page, self::PAGES_AUTORISEES, true)) {
            return $pageParDefaut;
        }

        return $page;
    }

    private function estDateValide(string $valeur): bool
    {
        $date = DateTimeImmutable::createFromFormat('Y-m-d', $valeur);

        return $date instanceof DateTimeImmutable && $date->format('Y-m-d') === $valeur;
    }

    private function estPseudoChessValide(string $valeur): bool
    {
        if ($valeur === '') {
            return true;
        }

        return mb_strlen($valeur) <= 50 && preg_match('/^[A-Za-z0-9_-]+$/', $valeur) === 1;
    }
}
