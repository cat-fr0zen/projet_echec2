<?php

declare(strict_types=1);

final class ArticleRepository
{
    public function __construct(private JsonStore $store)
    {
    }

    public function findApproved(): array
    {
        $articles = array_filter(
            $this->store->read(),
            static fn (array $article): bool => ($article['status'] ?? '') === 'approved'
        );

        usort(
            $articles,
            static fn (array $left, array $right): int => strcmp((string) ($right['created_at'] ?? ''), (string) ($left['created_at'] ?? ''))
        );

        return array_values($articles);
    }

    public function findByAuthorId(string $authorId): array
    {
        $articles = array_filter(
            $this->store->read(),
            static fn (array $article): bool => ($article['author_id'] ?? '') === $authorId
        );

        usort(
            $articles,
            static fn (array $left, array $right): int => strcmp((string) ($right['created_at'] ?? ''), (string) ($left['created_at'] ?? ''))
        );

        return array_values($articles);
    }

    public function create(array $payload): array
    {
        $articles = $this->store->read();

        $article = [
            'id' => 'article_' . bin2hex(random_bytes(8)),
            'author_id' => $payload['author_id'],
            'author_name' => $payload['author_name'],
            'title' => $payload['title'],
            'excerpt' => $payload['excerpt'],
            'content' => $payload['content'],
            'status' => 'pending_review',
            'created_at' => gmdate('c'),
        ];

        $articles[] = $article;
        $this->store->write($articles);

        return $article;
    }
}
