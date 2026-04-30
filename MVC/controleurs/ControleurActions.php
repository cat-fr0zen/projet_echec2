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
        'admin',
    ];

    public function __construct(
        private DepotUtilisateurs $depotUtilisateurs,
        private DepotArticles $depotArticles,
        private DepotMedias $depotMedias,
        private DepotCommandes $depotCommandes,
        private string $dossierUploadMedias
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
            ajouter_message_flash('error', 'Votre session a expire. Merci de recommencer.');
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

        $erreurs = $this->validerDonneesProfil($donnees, true);

        if ($this->depotUtilisateurs->trouverParCourriel($donnees['courriel']) !== null) {
            $erreurs[] = 'Un compte existe deja avec cet email.';
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
        ajouter_message_flash('success', 'Votre compte a ete cree avec succes.');
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

        if (($utilisateur['statut_compte'] ?? '') !== DepotUtilisateurs::STATUT_COMPTE_ACTIF) {
            memoriser_etat_formulaire([
                'ouverte' => true,
                'onglet' => 'connexion',
                'erreurs' => ['Votre compte est actuellement suspendu.'],
                'anciennes_valeurs' => ['courriel' => $courriel],
            ]);
            rediriger_vers(url_route($pageRedirection));
        }

        session_regenerate_id(true);
        $_SESSION['identifiant_utilisateur'] = $utilisateur['identifiant'];
        ajouter_message_flash('success', 'Connexion reussie.');
        rediriger_vers(url_route('profil'));
    }

    private function traiterDeconnexion(): void
    {
        unset($_SESSION['identifiant_utilisateur']);
        session_regenerate_id(true);
        ajouter_message_flash('success', 'Vous avez ete deconnecte.');
        rediriger_vers(url_route('accueil'));
    }

    private function traiterMiseAJourProfil(): void
    {
        $utilisateurCourant = $this->obtenirUtilisateurCourant();

        if ($utilisateurCourant === null) {
            ajouter_message_flash('error', 'Vous devez etre connecte pour modifier votre profil.');
            rediriger_vers(url_route('accueil'));
        }

        $donnees = [
            'nom' => trim((string) ($_POST['nom'] ?? $_POST['last_name'] ?? '')),
            'prenom' => trim((string) ($_POST['prenom'] ?? $_POST['first_name'] ?? '')),
            'date_naissance' => trim((string) ($_POST['date_naissance'] ?? $_POST['birth_date'] ?? '')),
            'description_profil' => trim((string) ($_POST['description_profil'] ?? $_POST['profile_description'] ?? '')),
            'pseudo_chess' => trim((string) ($_POST['pseudo_chess'] ?? $_POST['chess_username'] ?? '')),
            'courriel' => (string) ($utilisateurCourant['courriel'] ?? ''),
            'mot_de_passe' => 'ignore',
        ];

        $erreurs = $this->validerDonneesProfil($donnees, false);

        if ($erreurs !== []) {
            ajouter_message_flash('error', implode(' ', $erreurs));
            rediriger_vers(url_route('profil'));
        }

        $this->depotUtilisateurs->mettreAJour((string) $utilisateurCourant['identifiant'], $donnees);
        ajouter_message_flash('success', 'Votre profil a ete mis a jour.');
        rediriger_vers(url_route('profil'));
    }

    private function traiterCreationArticle(): void
    {
        $utilisateurCourant = $this->obtenirUtilisateurCourant();

        if ($utilisateurCourant === null) {
            ajouter_message_flash('error', 'Vous devez etre connecte pour proposer un article.');
            rediriger_vers(url_route('articles'));
        }

        if (!$this->utilisateurPeutPublierContenu($utilisateurCourant)) {
            ajouter_message_flash('error', 'Seuls les adherents du club peuvent proposer des articles.');
            rediriger_vers(url_route('articles'));
        }

        $titre = trim((string) ($_POST['titre'] ?? $_POST['title'] ?? ''));
        $resume = trim((string) ($_POST['resume'] ?? $_POST['excerpt'] ?? ''));
        $contenu = trim((string) ($_POST['contenu'] ?? $_POST['content'] ?? ''));

        $erreurs = [];

        if ($titre === '' || mb_strlen($titre) > 150) {
            $erreurs[] = 'Le titre est obligatoire et doit rester inferieur a 150 caracteres.';
        }

        if ($resume === '' || mb_strlen($resume) > 280) {
            $erreurs[] = 'Le resume est obligatoire et doit rester inferieur a 280 caracteres.';
        }

        if (mb_strlen($contenu) < 80) {
            $erreurs[] = "Le contenu de l'article doit contenir au moins 80 caracteres.";
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

        ajouter_message_flash('success', 'Votre article a ete enregistre et attend validation.');
        rediriger_vers(url_route('articles'));
    }

    private function traiterSoumissionMedia(): void
    {
        $utilisateurCourant = $this->obtenirUtilisateurCourant();

        if ($utilisateurCourant === null) {
            ajouter_message_flash('error', 'Vous devez etre connecte pour proposer un media.');
            rediriger_vers(url_route('mediatheque'));
        }

        if (!$this->utilisateurPeutPublierContenu($utilisateurCourant)) {
            ajouter_message_flash('error', 'Seuls les adherents du club peuvent proposer des photos ou des videos.');
            rediriger_vers(url_route('mediatheque'));
        }

        $titre = trim((string) ($_POST['titre_media'] ?? $_POST['media_title'] ?? ''));
        $description = trim((string) ($_POST['description_media'] ?? $_POST['media_description'] ?? ''));
        $typeMedia = trim((string) ($_POST['type_media'] ?? $_POST['media_type'] ?? ''));
        $fichier = $_FILES['media_fichier'] ?? null;

        $erreurs = [];

        if ($titre === '' || mb_strlen($titre) > 150) {
            $erreurs[] = 'Le titre du media est obligatoire et doit rester inferieur a 150 caracteres.';
        }

        if (mb_strlen($description) > 500) {
            $erreurs[] = 'La description du media doit rester inferieure a 500 caracteres.';
        }

        if (!in_array($typeMedia, [DepotMedias::TYPE_PHOTO, DepotMedias::TYPE_VIDEO], true)) {
            $erreurs[] = 'Le type de media est invalide.';
        }

        if (!is_array($fichier) || (($fichier['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK)) {
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

        if (!is_dir($this->dossierUploadMedias)) {
            mkdir($this->dossierUploadMedias, 0777, true);
        }

        $nomStocke = 'media_' . bin2hex(random_bytes(12)) . '.' . $validationFichier['extension'];
        $cheminDestination = rtrim($this->dossierUploadMedias, '/\\') . DIRECTORY_SEPARATOR . $nomStocke;

        if (!move_uploaded_file((string) $fichier['tmp_name'], $cheminDestination)) {
            ajouter_message_flash('error', "Le televersement du media a echoue.");
            rediriger_vers(url_route('mediatheque'));
        }

        $nomAuteur = trim((string) $utilisateurCourant['prenom'] . ' ' . (string) $utilisateurCourant['nom']);

        $this->depotMedias->creer([
            'identifiant_auteur' => $utilisateurCourant['identifiant'],
            'nom_auteur' => $nomAuteur !== '' ? $nomAuteur : (string) $utilisateurCourant['courriel'],
            'type_media' => $typeMedia,
            'titre' => $titre,
            'description' => $description,
            'nom_fichier_original' => (string) ($fichier['name'] ?? ''),
            'nom_fichier_stocke' => $nomStocke,
            'chemin_public' => 'ressources/media/uploads/' . $nomStocke,
            'type_mime' => $validationFichier['mime'],
            'taille_octets' => (int) ($fichier['size'] ?? 0),
        ]);

        ajouter_message_flash('success', 'Votre media a ete envoye et attend validation.');
        rediriger_vers(url_route('mediatheque'));
    }

    private function traiterModerationArticle(): void
    {
        $this->exigerAdmin();

        $identifiantArticle = trim((string) ($_POST['identifiant_article'] ?? ''));
        $statut = trim((string) ($_POST['statut_article'] ?? ''));

        if ($identifiantArticle === '' || $this->depotArticles->changerStatut($identifiantArticle, $statut) === null) {
            ajouter_message_flash('error', "Impossible de mettre a jour l'article.");
            rediriger_vers(url_route('admin'));
        }

        ajouter_message_flash('success', "Le statut de l'article a ete mis a jour.");
        rediriger_vers(url_route('admin'));
    }

    private function traiterModerationMedia(): void
    {
        $this->exigerAdmin();

        $identifiantMedia = trim((string) ($_POST['identifiant_media'] ?? ''));
        $statut = trim((string) ($_POST['statut_media'] ?? ''));

        if ($identifiantMedia === '' || $this->depotMedias->changerStatut($identifiantMedia, $statut) === null) {
            ajouter_message_flash('error', 'Impossible de mettre a jour le media.');
            rediriger_vers(url_route('admin'));
        }

        ajouter_message_flash('success', 'Le statut du media a ete mis a jour.');
        rediriger_vers(url_route('admin'));
    }

    private function traiterCommandeProduit(): void
    {
        $utilisateurCourant = $this->obtenirUtilisateurCourant();

        if ($utilisateurCourant === null || ($utilisateurCourant['statut_compte'] ?? '') !== DepotUtilisateurs::STATUT_COMPTE_ACTIF) {
            ajouter_message_flash('error', 'Vous devez etre connecte pour commander un article.');
            rediriger_vers(url_route('accueil'));
        }

        $produit = trim((string) ($_POST['produit'] ?? ''));
        $categorie = trim((string) ($_POST['categorie'] ?? ''));

        if ($produit === '' || $categorie === '') {
            ajouter_message_flash('error', 'Produit invalide.');
            rediriger_vers(url_route('boutique'));
        }

        $nomUtilisateur = trim((string) $utilisateurCourant['prenom'] . ' ' . (string) $utilisateurCourant['nom']);

        $this->depotCommandes->creer([
            'identifiant_utilisateur' => $utilisateurCourant['identifiant'],
            'nom_utilisateur' => $nomUtilisateur !== '' ? $nomUtilisateur : (string) $utilisateurCourant['courriel'],
            'produit' => $produit,
            'categorie' => $categorie,
        ]);

        ajouter_message_flash('success', 'La commande a ete enregistree avec le statut En attente.');
        rediriger_vers(url_route('boutique'));
    }

    private function traiterMiseAJourStatutCommande(): void
    {
        $this->exigerAdmin();

        $identifiantCommande = trim((string) ($_POST['identifiant_commande'] ?? ''));
        $statut = trim((string) ($_POST['statut_commande'] ?? ''));

        if ($identifiantCommande === '' || $this->depotCommandes->changerStatut($identifiantCommande, $statut) === null) {
            ajouter_message_flash('error', 'Impossible de mettre a jour la commande.');
            rediriger_vers(url_route('admin'));
        }

        ajouter_message_flash('success', 'Le statut de la commande a ete mis a jour.');
        rediriger_vers(url_route('admin'));
    }

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
            ajouter_message_flash('error', "L'administrateur principal ne peut pas retirer son propre role admin ici.");
            rediriger_vers(url_route('admin'));
        }

        $utilisateur = $this->depotUtilisateurs->mettreAJourAcces($identifiantUtilisateur, $role, $statutCompte, $statutAdhesion);

        if ($utilisateur === null) {
            ajouter_message_flash('error', "Impossible de mettre a jour les acces de l'utilisateur.");
            rediriger_vers(url_route('admin'));
        }

        ajouter_message_flash('success', "Les acces de l'utilisateur ont ete mis a jour.");
        rediriger_vers(url_route('admin'));
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

    private function validerDonneesProfil(array $donnees, bool $verifierMotDePasse): array
    {
        $erreurs = [];

        if ($donnees['nom'] === '' || mb_strlen($donnees['nom']) > 100) {
            $erreurs[] = 'Le nom est obligatoire et doit rester raisonnable.';
        }

        if ($donnees['prenom'] === '' || mb_strlen($donnees['prenom']) > 100) {
            $erreurs[] = 'Le prenom est obligatoire et doit rester raisonnable.';
        }

        if ($donnees['date_naissance'] !== '' && !$this->estDateValide($donnees['date_naissance'])) {
            $erreurs[] = 'La date de naissance doit respecter le format attendu.';
        }

        if (!filter_var($donnees['courriel'], FILTER_VALIDATE_EMAIL)) {
            $erreurs[] = 'Veuillez saisir une adresse email valide.';
        }

        if ($verifierMotDePasse && mb_strlen($donnees['mot_de_passe']) < 8) {
            $erreurs[] = 'Le mot de passe doit contenir au moins 8 caracteres.';
        }

        if (mb_strlen($donnees['description_profil']) > 1200) {
            $erreurs[] = 'La description de profil doit rester inferieure a 1200 caracteres.';
        }

        if (!$this->estPseudoChessValide($donnees['pseudo_chess'])) {
            $erreurs[] = 'Le pseudo Chess.com doit contenir seulement des lettres, chiffres, tirets ou underscores.';
        }

        return $erreurs;
    }

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

        if ($extension === '' || !in_array($extension, $extensionsAutorisees, true)) {
            $erreurs[] = 'L extension du fichier n est pas autorisee.';
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo !== false ? (string) finfo_file($finfo, (string) ($fichier['tmp_name'] ?? '')) : '';
        if ($finfo !== false) {
            finfo_close($finfo);
        }

        if ($mime === '' || !in_array($mime, $mimeAutorises, true)) {
            $erreurs[] = 'Le type de fichier envoye n est pas autorise.';
        }

        return [
            'erreurs' => $erreurs,
            'extension' => $extension,
            'mime' => $mime,
        ];
    }

    private function utilisateurPeutPublierContenu(array $utilisateur): bool
    {
        if (($utilisateur['statut_compte'] ?? '') !== DepotUtilisateurs::STATUT_COMPTE_ACTIF) {
            return false;
        }

        return in_array(
            (string) ($utilisateur['role'] ?? ''),
            [DepotUtilisateurs::ROLE_ADHERENT, DepotUtilisateurs::ROLE_ADMIN],
            true
        );
    }

    private function exigerAdmin(): void
    {
        $utilisateurCourant = $this->obtenirUtilisateurCourant();

        if (
            $utilisateurCourant === null
            || ($utilisateurCourant['role'] ?? '') !== DepotUtilisateurs::ROLE_ADMIN
            || ($utilisateurCourant['statut_compte'] ?? '') !== DepotUtilisateurs::STATUT_COMPTE_ACTIF
        ) {
            ajouter_message_flash('error', 'Acces reserve a l administrateur du site.');
            rediriger_vers(url_route('accueil'));
        }
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
