<?php

declare(strict_types=1);

final class ActionController
{
    public function __construct(
        private UserRepository $userRepository,
        private ArticleRepository $articleRepository
    ) {
    }

    public function handle(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return;
        }

        $action = isset($_POST['action']) ? trim((string) $_POST['action']) : '';

        if ($action === '') {
            return;
        }

        if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
            push_flash('error', 'Votre session a expire. Merci de recommencer.');
            redirect_to(route_url('accueil'));
        }

        switch ($action) {
            case 'register':
                $this->handleRegister();
                break;
            case 'login':
                $this->handleLogin();
                break;
            case 'logout':
                $this->handleLogout();
                break;
            case 'update_profile':
                $this->handleProfileUpdate();
                break;
            case 'create_article':
                $this->handleArticleCreation();
                break;
            default:
                push_flash('error', 'Action non prise en charge.');
                redirect_to(route_url('accueil'));
        }
    }

    private function handleRegister(): void
    {
        $redirectPage = $this->resolveRedirectPage('accueil');
        $payload = [
            'last_name' => trim((string) ($_POST['last_name'] ?? '')),
            'first_name' => trim((string) ($_POST['first_name'] ?? '')),
            'birth_date' => trim((string) ($_POST['birth_date'] ?? '')),
            'email' => trim((string) ($_POST['email'] ?? '')),
            'password' => (string) ($_POST['password'] ?? ''),
            'profile_description' => trim((string) ($_POST['profile_description'] ?? '')),
        ];

        $errors = [];

        if ($payload['last_name'] === '' || mb_strlen($payload['last_name']) > 100) {
            $errors[] = 'Le nom est obligatoire et doit rester raisonnable.';
        }

        if ($payload['first_name'] === '' || mb_strlen($payload['first_name']) > 100) {
            $errors[] = 'Le prenom est obligatoire et doit rester raisonnable.';
        }

        if ($payload['birth_date'] !== '' && !$this->isValidDate($payload['birth_date'])) {
            $errors[] = 'La date de naissance doit respecter le format attendu.';
        }

        if (!filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Veuillez saisir une adresse email valide.';
        }

        if (mb_strlen($payload['password']) < 8) {
            $errors[] = 'Le mot de passe doit contenir au moins 8 caracteres.';
        }

        if (mb_strlen($payload['profile_description']) > 1200) {
            $errors[] = 'La description de profil doit rester inferieure a 1200 caracteres.';
        }

        if ($this->userRepository->findByEmail($payload['email']) !== null) {
            $errors[] = 'Un compte existe deja avec cet email.';
        }

        if ($errors !== []) {
            set_form_state([
                'is_open' => true,
                'tab' => 'register',
                'errors' => $errors,
                'old' => $payload,
            ]);
            redirect_to(route_url($redirectPage));
        }

        $user = $this->userRepository->create($payload);
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        push_flash('success', 'Votre compte a ete cree avec succes.');
        redirect_to(route_url('profil'));
    }

    private function handleLogin(): void
    {
        $redirectPage = $this->resolveRedirectPage('accueil');
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $user = $this->userRepository->findByEmail($email);

        if ($user === null || !password_verify($password, (string) ($user['password_hash'] ?? ''))) {
            set_form_state([
                'is_open' => true,
                'tab' => 'login',
                'errors' => ['Email ou mot de passe incorrect.'],
                'old' => ['email' => $email],
            ]);
            redirect_to(route_url($redirectPage));
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        push_flash('success', 'Connexion reussie.');
        redirect_to(route_url('profil'));
    }

    private function handleLogout(): void
    {
        unset($_SESSION['user_id']);
        session_regenerate_id(true);
        push_flash('success', 'Vous avez ete deconnecte.');
        redirect_to(route_url('accueil'));
    }

    private function handleProfileUpdate(): void
    {
        $currentUser = $this->getCurrentUser();

        if ($currentUser === null) {
            push_flash('error', 'Vous devez etre connecte pour modifier votre profil.');
            redirect_to(route_url('accueil'));
        }

        $payload = [
            'last_name' => trim((string) ($_POST['last_name'] ?? '')),
            'first_name' => trim((string) ($_POST['first_name'] ?? '')),
            'birth_date' => trim((string) ($_POST['birth_date'] ?? '')),
            'profile_description' => trim((string) ($_POST['profile_description'] ?? '')),
        ];

        $errors = [];

        if ($payload['last_name'] === '' || mb_strlen($payload['last_name']) > 100) {
            $errors[] = 'Le nom est obligatoire et doit rester raisonnable.';
        }

        if ($payload['first_name'] === '' || mb_strlen($payload['first_name']) > 100) {
            $errors[] = 'Le prenom est obligatoire et doit rester raisonnable.';
        }

        if ($payload['birth_date'] !== '' && !$this->isValidDate($payload['birth_date'])) {
            $errors[] = 'La date de naissance doit respecter le format attendu.';
        }

        if (mb_strlen($payload['profile_description']) > 1200) {
            $errors[] = 'La description de profil doit rester inferieure a 1200 caracteres.';
        }

        if ($errors !== []) {
            push_flash('error', implode(' ', $errors));
            redirect_to(route_url('profil'));
        }

        $this->userRepository->update((string) $currentUser['id'], $payload);
        push_flash('success', 'Votre profil a ete mis a jour.');
        redirect_to(route_url('profil'));
    }

    private function handleArticleCreation(): void
    {
        $currentUser = $this->getCurrentUser();

        if ($currentUser === null) {
            push_flash('error', 'Vous devez etre connecte pour proposer un article.');
            redirect_to(route_url('articles'));
        }

        $title = trim((string) ($_POST['title'] ?? ''));
        $excerpt = trim((string) ($_POST['excerpt'] ?? ''));
        $content = trim((string) ($_POST['content'] ?? ''));

        $errors = [];

        if ($title === '' || mb_strlen($title) > 150) {
            $errors[] = 'Le titre est obligatoire et doit rester inferieur a 150 caracteres.';
        }

        if ($excerpt === '' || mb_strlen($excerpt) > 280) {
            $errors[] = 'Le resume est obligatoire et doit rester inferieur a 280 caracteres.';
        }

        if (mb_strlen($content) < 80) {
            $errors[] = 'Le contenu de l article doit contenir au moins 80 caracteres.';
        }

        if ($errors !== []) {
            push_flash('error', implode(' ', $errors));
            redirect_to(route_url('articles'));
        }

        $authorName = trim((string) $currentUser['first_name'] . ' ' . (string) $currentUser['last_name']);

        $this->articleRepository->create([
            'author_id' => $currentUser['id'],
            'author_name' => $authorName !== '' ? $authorName : (string) $currentUser['email'],
            'title' => $title,
            'excerpt' => $excerpt,
            'content' => $content,
        ]);

        push_flash('success', 'Votre article a ete enregistre et attend maintenant une validation future.');
        redirect_to(route_url('articles'));
    }

    private function getCurrentUser(): ?array
    {
        $userId = isset($_SESSION['user_id']) ? (string) $_SESSION['user_id'] : '';

        return $this->userRepository->findById($userId);
    }

    private function resolveRedirectPage(string $fallback): string
    {
        $page = trim((string) ($_POST['redirect_page'] ?? ''));

        return $page !== '' ? $page : $fallback;
    }

    private function isValidDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);

        return $date instanceof DateTimeImmutable && $date->format('Y-m-d') === $value;
    }
}
