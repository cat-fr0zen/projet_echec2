<?php

declare(strict_types=1);

final class DepotArticlesOracle
{
    public function __construct(private BaseDeDonneesOracle $base)
    {
    }

    public function trouverPublies(): array
    {
        return $this->chargerArticles('WHERE a.code_statut = :statut ORDER BY a.cree_le DESC', [
            'statut' => DepotArticles::STATUT_PUBLIE,
        ]);
    }

    public function trouverParIdentifiantAuteur(string $identifiantAuteur): array
    {
        return $this->chargerArticles('WHERE a.identifiant_auteur = :identifiant_auteur ORDER BY a.cree_le DESC', [
            'identifiant_auteur' => $identifiantAuteur,
        ]);
    }

    public function listerTous(): array
    {
        return $this->chargerArticles('ORDER BY a.cree_le DESC');
    }

    public function trouverParIdentifiant(string $identifiant): ?array
    {
        $articles = $this->chargerArticles('WHERE a.identifiant = :identifiant', [
            'identifiant' => $identifiant,
        ]);

        return $articles[0] ?? null;
    }

    public function creer(array $donnees): array
    {
        $identifiant = 'article_' . bin2hex(random_bytes(8));

        $this->base->transaction(function () use ($identifiant, $donnees): void {
            $this->base->executer(
                'INSERT INTO article (
                    identifiant, identifiant_auteur, nom_auteur, auteur_affiche,
                    titre, resume, contenu, code_statut, cree_le
                ) VALUES (
                    :identifiant, :identifiant_auteur, :nom_auteur, :auteur_affiche,
                    :titre, :resume, :contenu, :code_statut, SYSDATE
                )',
                [
                    'identifiant' => $identifiant,
                    'identifiant_auteur' => (string) $donnees['identifiant_auteur'],
                    'nom_auteur' => (string) $donnees['nom_auteur'],
                    'auteur_affiche' => (string) ($donnees['auteur_affiche'] ?? $donnees['nom_auteur']),
                    'titre' => (string) $donnees['titre'],
                    'resume' => (string) $donnees['resume'],
                    'contenu' => $this->limiter((string) $donnees['contenu'], 4000),
                    'code_statut' => DepotArticles::STATUT_EN_ATTENTE,
                ]
            );

            $this->remplacerBlocs($identifiant, (array) ($donnees['blocs'] ?? []));
        });

        return $this->trouverParIdentifiant($identifiant) ?? [];
    }

    public function changerStatut(string $identifiant, string $statut): ?array
    {
        if (!in_array($statut, [DepotArticles::STATUT_EN_ATTENTE, DepotArticles::STATUT_PUBLIE, DepotArticles::STATUT_REFUSE], true)) {
            return null;
        }

        $lignes = $this->base->executer(
            'UPDATE article
                SET code_statut = :code_statut,
                    mis_a_jour_le = SYSDATE
              WHERE identifiant = :identifiant',
            [
                'code_statut' => $statut,
                'identifiant' => $identifiant,
            ]
        );

        return $lignes > 0 ? $this->trouverParIdentifiant($identifiant) : null;
    }

    public function supprimer(string $identifiant): bool
    {
        return $this->base->executer(
            'DELETE FROM article WHERE identifiant = :identifiant',
            ['identifiant' => $identifiant]
        ) > 0;
    }

    private function chargerArticles(string $suffixe = '', array $parametres = []): array
    {
        $lignes = $this->base->lireTout(
            'SELECT
                a.identifiant,
                a.identifiant_auteur,
                a.nom_auteur,
                a.auteur_affiche,
                a.titre,
                a.resume,
                a.contenu,
                a.code_statut,
                TO_CHAR(a.cree_le, \'YYYY-MM-DD"T"HH24:MI:SS"Z"\') cree_le,
                TO_CHAR(a.mis_a_jour_le, \'YYYY-MM-DD"T"HH24:MI:SS"Z"\') mis_a_jour_le
            FROM article a ' . $suffixe,
            $parametres
        );

        return array_map(fn (array $ligne): array => $this->normaliserArticle($ligne), $lignes);
    }

    private function normaliserArticle(array $ligne): array
    {
        $statut = (string) ($ligne['code_statut'] ?? DepotArticles::STATUT_EN_ATTENTE);
        $creeLe = (string) ($ligne['cree_le'] ?? '');

        return [
            'identifiant' => (string) ($ligne['identifiant'] ?? ''),
            'identifiant_auteur' => (string) ($ligne['identifiant_auteur'] ?? ''),
            'nom_auteur' => (string) ($ligne['nom_auteur'] ?? ''),
            'auteur_affiche' => (string) ($ligne['auteur_affiche'] ?? $ligne['nom_auteur'] ?? ''),
            'titre' => (string) ($ligne['titre'] ?? ''),
            'resume' => (string) ($ligne['resume'] ?? ''),
            'contenu' => (string) ($ligne['contenu'] ?? ''),
            'blocs' => $this->chargerBlocs((string) ($ligne['identifiant'] ?? '')),
            'statut' => $statut,
            'libelle_statut' => match ($statut) {
                DepotArticles::STATUT_PUBLIE => 'Publie',
                DepotArticles::STATUT_REFUSE => 'Refuse',
                default => 'En attente',
            },
            'cree_le' => $creeLe,
            'date_creation_libelle' => $this->formaterDateArticle($creeLe),
            'mis_a_jour_le' => (string) ($ligne['mis_a_jour_le'] ?? ''),
        ];
    }

    private function chargerBlocs(string $identifiantArticle): array
    {
        if ($identifiantArticle === '') {
            return [];
        }

        $lignes = $this->base->lireTout(
            'SELECT
                code_type,
                texte,
                chemin_public,
                type_mime,
                texte_alternatif,
                legende,
                nom_fichier_original,
                taille_octets
            FROM article_bloc
            WHERE identifiant_article = :identifiant_article
            ORDER BY ordre_affichage',
            ['identifiant_article' => $identifiantArticle]
        );

        return array_map(
            static fn (array $ligne): array => [
                'type' => (string) ($ligne['code_type'] ?? DepotArticles::TYPE_BLOC_PARAGRAPHE),
                'texte' => (string) ($ligne['texte'] ?? ''),
                'chemin_public' => (string) ($ligne['chemin_public'] ?? ''),
                'type_mime' => (string) ($ligne['type_mime'] ?? ''),
                'texte_alternatif' => (string) ($ligne['texte_alternatif'] ?? ''),
                'legende' => (string) ($ligne['legende'] ?? ''),
                'nom_fichier_original' => (string) ($ligne['nom_fichier_original'] ?? ''),
                'taille_octets' => (int) ($ligne['taille_octets'] ?? 0),
            ],
            $lignes
        );
    }

    private function remplacerBlocs(string $identifiantArticle, array $blocs): void
    {
        $this->base->executer(
            'DELETE FROM article_bloc WHERE identifiant_article = :identifiant_article',
            ['identifiant_article' => $identifiantArticle]
        );

        foreach (array_values($blocs) as $index => $bloc) {
            if (!is_array($bloc)) {
                continue;
            }

            $type = (string) ($bloc['type'] ?? DepotArticles::TYPE_BLOC_PARAGRAPHE);
            if (!in_array($type, [
                DepotArticles::TYPE_BLOC_PARAGRAPHE,
                DepotArticles::TYPE_BLOC_SOUS_TITRE,
                DepotArticles::TYPE_BLOC_IMAGE,
                DepotArticles::TYPE_BLOC_VIDEO,
            ], true)) {
                $type = DepotArticles::TYPE_BLOC_PARAGRAPHE;
            }

            $this->base->executer(
                'INSERT INTO article_bloc (
                    identifiant_bloc, identifiant_article, ordre_affichage, code_type,
                    texte, chemin_public, type_mime, texte_alternatif, legende,
                    nom_fichier_original, taille_octets
                ) VALUES (
                    :identifiant_bloc, :identifiant_article, :ordre_affichage, :code_type,
                    :texte, :chemin_public, :type_mime, :texte_alternatif, :legende,
                    :nom_fichier_original, :taille_octets
                )',
                [
                    'identifiant_bloc' => 'article_bloc_' . bin2hex(random_bytes(8)),
                    'identifiant_article' => $identifiantArticle,
                    'ordre_affichage' => $index + 1,
                    'code_type' => $type,
                    'texte' => $this->limiter((string) ($bloc['texte'] ?? ''), 4000),
                    'chemin_public' => (string) ($bloc['chemin_public'] ?? ''),
                    'type_mime' => (string) ($bloc['type_mime'] ?? ''),
                    'texte_alternatif' => (string) ($bloc['texte_alternatif'] ?? ''),
                    'legende' => (string) ($bloc['legende'] ?? ''),
                    'nom_fichier_original' => (string) ($bloc['nom_fichier_original'] ?? ''),
                    'taille_octets' => (int) ($bloc['taille_octets'] ?? 0),
                ]
            );
        }
    }

    private function formaterDateArticle(string $dateIso): string
    {
        if ($dateIso === '') {
            return '';
        }

        try {
            return (new DateTimeImmutable($dateIso))->format('d/m/Y');
        } catch (Throwable) {
            return '';
        }
    }

    private function limiter(string $valeur, int $limite): string
    {
        return function_exists('mb_substr') ? mb_substr($valeur, 0, $limite) : substr($valeur, 0, $limite);
    }
}
