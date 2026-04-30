<?php

declare(strict_types=1);

final class DepotArticles
{
    public const STATUT_EN_ATTENTE = 'en_attente_validation';
    public const STATUT_PUBLIE = 'publie';
    public const STATUT_REFUSE = 'refuse';

    public function __construct(private StockageJson $stockage)
    {
    }

    public function trouverPublies(): array
    {
        $articles = array_filter(
            $this->stockage->lire(),
            fn (array $enregistrement): bool => $this->normaliserArticle($enregistrement)['statut'] === self::STATUT_PUBLIE
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

    public function listerTous(): array
    {
        $articles = array_map(
            fn (array $enregistrement): array => $this->normaliserArticle($enregistrement),
            $this->stockage->lire()
        );

        usort(
            $articles,
            fn (array $gauche, array $droite): int => strcmp(
                (string) ($droite['cree_le'] ?? ''),
                (string) ($gauche['cree_le'] ?? '')
            )
        );

        return $articles;
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
            'statut' => self::STATUT_EN_ATTENTE,
            'cree_le' => gmdate('c'),
        ];

        $articles[] = $article;
        $this->stockage->ecrire($articles);

        return $article;
    }

    public function changerStatut(string $identifiant, string $statut): ?array
    {
        if (!in_array($statut, [self::STATUT_EN_ATTENTE, self::STATUT_PUBLIE, self::STATUT_REFUSE], true)) {
            return null;
        }

        $articles = $this->stockage->lire();

        foreach ($articles as $index => $enregistrement) {
            $article = $this->normaliserArticle($enregistrement);

            if (($article['identifiant'] ?? '') !== $identifiant) {
                continue;
            }

            $articles[$index] = [
                'identifiant' => $article['identifiant'],
                'identifiant_auteur' => $article['identifiant_auteur'],
                'nom_auteur' => $article['nom_auteur'],
                'titre' => $article['titre'],
                'resume' => $article['resume'],
                'contenu' => $article['contenu'],
                'statut' => $statut,
                'cree_le' => $article['cree_le'],
                'mis_a_jour_le' => gmdate('c'),
            ];

            $this->stockage->ecrire($articles);

            return $this->normaliserArticle($articles[$index]);
        }

        return null;
    }

    private function normaliserArticle(array $enregistrement): array
    {
        $statutBrut = (string) ($enregistrement['statut'] ?? $enregistrement['status'] ?? 'en_attente_validation');
        $statut = match ($statutBrut) {
            'approved', 'publie' => self::STATUT_PUBLIE,
            'refuse' => self::STATUT_REFUSE,
            default => self::STATUT_EN_ATTENTE,
        };

        return [
            'identifiant' => (string) ($enregistrement['identifiant'] ?? $enregistrement['id'] ?? ''),
            'identifiant_auteur' => (string) ($enregistrement['identifiant_auteur'] ?? $enregistrement['author_id'] ?? ''),
            'nom_auteur' => (string) ($enregistrement['nom_auteur'] ?? $enregistrement['author_name'] ?? ''),
            'titre' => (string) ($enregistrement['titre'] ?? $enregistrement['title'] ?? ''),
            'resume' => (string) ($enregistrement['resume'] ?? $enregistrement['excerpt'] ?? ''),
            'contenu' => (string) ($enregistrement['contenu'] ?? $enregistrement['content'] ?? ''),
            'statut' => $statut,
            'libelle_statut' => match ($statut) {
                self::STATUT_PUBLIE => 'Publie',
                self::STATUT_REFUSE => 'Refuse',
                default => 'En attente',
            },
            'cree_le' => (string) ($enregistrement['cree_le'] ?? $enregistrement['created_at'] ?? ''),
            'mis_a_jour_le' => (string) ($enregistrement['mis_a_jour_le'] ?? $enregistrement['updated_at'] ?? ''),
        ];
    }
}
