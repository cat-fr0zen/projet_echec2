<?php

declare(strict_types=1);

final class PageController
{
    public function __construct(
        private SiteModel $siteModel,
        private UserRepository $userRepository,
        private ArticleRepository $articleRepository,
        private array $flashMessages,
        private array $formState
    ) {
    }

    public function handle(string $slug): string
    {
        $siteData = $this->siteModel->getSiteData();
        $pages = $this->siteModel->getPages();
        $currentUser = $this->userRepository->findById(isset($_SESSION['user_id']) ? (string) $_SESSION['user_id'] : null);

        $siteData['theme'] = current_theme();
        $siteData['csrf_token'] = csrf_token();
        $siteData['flash_messages'] = $this->flashMessages;
        $siteData['form_state'] = $this->formState;
        $siteData['current_page'] = $slug;
        $siteData['auth'] = $this->buildAuthData($currentUser);
        $siteData['guide_cards'] = $this->siteModel->getGuideCards();
        $siteData['media_cards'] = $this->siteModel->getMediaCards();
        $siteData['merch_cards'] = $this->siteModel->getMerchCards();
        $siteData['published_articles'] = $this->articleRepository->findApproved();
        $siteData['my_articles'] = $currentUser !== null ? $this->articleRepository->findByAuthorId((string) $currentUser['id']) : [];

        $pageData = $pages[$slug] ?? null;

        if ($pageData === null) {
            http_response_code(404);
            $currentPage = 'not-found';
            $pageData = $this->siteModel->getNotFoundData();
            $pageTitle = 'Page introuvable';
            $viewFile = __DIR__ . '/../vues/pages/not-found.php';
        } else {
            $currentPage = $slug;
            $pageTitle = $pageData['title'];
            $viewFile = __DIR__ . '/../vues/pages/' . $pageData['view'];
        }

        $metaTitle = $pageTitle . ' | ' . $siteData['brand'];
        $metaDescription = $pageData['meta_description'] ?? $siteData['tagline'];

        ob_start();
        require __DIR__ . '/../vues/layout.php';

        return (string) ob_get_clean();
    }

    private function buildAuthData(?array $user): array
    {
        if ($user === null) {
            return [
                'is_authenticated' => false,
                'display_name' => '',
                'user' => null,
            ];
        }

        $displayName = trim((string) ($user['first_name'] ?? '') . ' ' . (string) ($user['last_name'] ?? ''));

        return [
            'is_authenticated' => true,
            'display_name' => $displayName !== '' ? $displayName : (string) ($user['email'] ?? ''),
            'user' => $user,
        ];
    }
}
