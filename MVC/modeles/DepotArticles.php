<?php

declare(strict_types=1);

final class DepotArticles
{
    public function __construct(private StockageJson $stockage)
    {
    }

    public function trouverPublies(): array
    {
        $articles = array_filter(
            $this->stockage->lire(),
            fn (array $enregistrement): bool => $this->normaliserArticle($enregistrement)['statut'] === 'publie'
        );

        usort(
            $articles,
            fn (array $gauche, array $droite): int => strcmp(
                (string) ($this->normaliserArticle($droite)['cree_le'] ?? ''),
                (string) ($this->normaliserArticle($gauche)['cree_le'] ?? '')
            )
        );

        return array_map(fn (array $enregistrement): array => $this->normaliserArticle($enregistrement), array_values($articles));
    }

    public function trouverParIdentifiantAuteur(string $identifiantAuteur): array
    {
        $articles = array_filter(
            $this->stockage->lire(),
            fn (array $enregistrement): bool => $this->normaliserArticle($enregistrement)['identifiant_auteur'] === $identifiantAuteur
        );

        usort(
            $articles,
            fn (array $gauche, array $droite): int => strcmp(
                (string) ($this->normaliserArticle($droite)['cree_le'] ?? ''),
                (string) ($this->normaliserArticle($gauche)['cree_le'] ?? '')
            )
        );

        return array_map(fn (array $enregistrement): array => $this->normaliserArticle($enregistrement), array_values($articles));
    }

    public function creer(array $donnees): array
    {
        $articles = $this->stockage->lire();

        $article = [
            'identifiant' => 'article_' . bin2hex(random_bytes(8)),
            'identifiant_auteur' => $donnees['identifiant_auteur'],
            'nom_auteur' => $donnees['nom_auteur'],
            'titre' => $donnees['titre'],
            'resume' => $donnees['resume'],
            'contenu' => $donnees['contenu'],
            'statut' => 'en_attente_validation',
            'cree_le' => gmdate('c'),
        ];

        $articles[] = $article;
        $this->stockage->ecrire($articles);

        return $article;
    }

    private function normaliserArticle(array $enregistrement): array
    {
        $statutBrut = (string) ($enregistrement['statut'] ?? $enregistrement['status'] ?? 'en_attente_validation');
        $statut = match ($statutBrut) {
            'approved', 'publie' => 'publie',
            default => 'en_attente_validation',
        };

        return [
            'identifiant' => (string) ($enregistrement['identifiant'] ?? $enregistrement['id'] ?? ''),
            'identifiant_auteur' => (string) ($enregistrement['identifiant_auteur'] ?? $enregistrement['author_id'] ?? ''),
            'nom_auteur' => (string) ($enregistrement['nom_auteur'] ?? $enregistrement['author_name'] ?? ''),
            'titre' => (string) ($enregistrement['titre'] ?? $enregistrement['title'] ?? ''),
            'resume' => (string) ($enregistrement['resume'] ?? $enregistrement['excerpt'] ?? ''),
            'contenu' => (string) ($enregistrement['contenu'] ?? $enregistrement['content'] ?? ''),
            'statut' => $statut,
            'cree_le' => (string) ($enregistrement['cree_le'] ?? $enregistrement['created_at'] ?? ''),
        ];
    }
}
