<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Article;
use App\Support\NomAffichageUtilisateur;
use DateTimeImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Throwable;

final class ArticleRepository
{
    public function trouverPublies(): array
    {
        return $this->chargerArticles(
            $this->requeteArticles()
                ->where('article.code_statut', Article::STATUT_PUBLIE)
                ->orderByDesc('article.cree_le')
                ->get()
                ->all()
        );
    }

    public function trouverParIdentifiantAuteur(string $identifiantAuteur): array
    {
        return $this->chargerArticles(
            $this->requeteArticles()
                ->where('article.identifiant_auteur', $identifiantAuteur)
                ->orderByDesc('article.cree_le')
                ->get()
                ->all()
        );
    }

    public function listerTous(): array
    {
        return $this->chargerArticles($this->requeteArticles()->orderByDesc('article.cree_le')->get()->all());
    }

    public function trouverParIdentifiant(string $identifiant): ?array
    {
        $row = $this->requeteArticles()->where('article.identifiant', $identifiant)->first();

        return $row !== null ? $this->normaliserArticle((array) $row) : null;
    }

    public function creer(array $donnees): array
    {
        $identifiant = 'article_' . bin2hex(random_bytes(8));

        DB::transaction(function () use ($identifiant, $donnees): void {
            DB::table('article')->insert([
                'identifiant' => $identifiant,
                'identifiant_auteur' => (string) ($donnees['identifiant_auteur'] ?? ''),
                'titre' => (string) ($donnees['titre'] ?? ''),
                'resume' => (string) ($donnees['resume'] ?? ''),
                'contenu_plat_cache' => $this->limiter((string) ($donnees['contenu'] ?? ''), 4000),
                'code_statut' => Article::STATUT_EN_ATTENTE,
                'cree_le' => date('Y-m-d H:i:s'),
            ]);

            $this->remplacerBlocs($identifiant, (array) ($donnees['blocs'] ?? []));
        });

        return $this->trouverParIdentifiant($identifiant) ?? [];
    }

    public function changerStatut(string $identifiant, string $statut): ?array
    {
        if (!in_array($statut, [Article::STATUT_EN_ATTENTE, Article::STATUT_PUBLIE, Article::STATUT_REFUSE], true)) {
            return null;
        }

        $updated = DB::table('article')
            ->where('identifiant', $identifiant)
            ->update([
                'code_statut' => $statut,
                'mis_a_jour_le' => date('Y-m-d H:i:s'),
            ]);

        return $updated > 0 ? $this->trouverParIdentifiant($identifiant) : null;
    }

    public function supprimer(string $identifiant): bool
    {
        return DB::table('article')->where('identifiant', $identifiant)->delete() > 0;
    }

    /**
     * @param array<int, object> $rows
     * @return array<int, array<string, mixed>>
     */
    private function chargerArticles(array $rows): array
    {
        return array_map(fn (object $row): array => $this->normaliserArticle((array) $row), $rows);
    }

    private function normaliserArticle(array $row): array
    {
        $nomAuteur = NomAffichageUtilisateur::depuisValeurs(
            $row['auteur_prenom_compte'] ?? '',
            $row['auteur_nom_compte'] ?? '',
            $row['auteur_courriel_compte'] ?? '',
            'Auteur'
        );
        $status = (string) ($row['code_statut'] ?? Article::STATUT_EN_ATTENTE);
        $createdAt = $this->formaterDateIso($row['cree_le'] ?? null);

        return [
            'identifiant' => (string) ($row['identifiant'] ?? ''),
            'identifiant_auteur' => (string) ($row['identifiant_auteur'] ?? ''),
            'nom_auteur' => $nomAuteur,
            'auteur_affiche' => $nomAuteur,
            'titre' => (string) ($row['titre'] ?? ''),
            'resume' => (string) ($row['resume'] ?? ''),
            'contenu' => (string) ($row['contenu_plat_cache'] ?? ''),
            'blocs' => $this->chargerBlocs((string) ($row['identifiant'] ?? '')),
            'statut' => $status,
            'libelle_statut' => match ($status) {
                Article::STATUT_PUBLIE => 'Publie',
                Article::STATUT_REFUSE => 'Refuse',
                default => 'En attente',
            },
            'cree_le' => $createdAt,
            'date_creation_libelle' => $this->formaterDateArticle($createdAt),
            'mis_a_jour_le' => $this->formaterDateIso($row['mis_a_jour_le'] ?? null),
        ];
    }

    private function requeteArticles(): Builder
    {
        return DB::table('article')
            ->leftJoin('compte_membre as auteur', 'auteur.identifiant', '=', 'article.identifiant_auteur')
            ->select(
                'article.*',
                'auteur.nom as auteur_nom_compte',
                'auteur.prenom as auteur_prenom_compte',
                'auteur.courriel as auteur_courriel_compte'
            );
    }

    private function chargerBlocs(string $identifiantArticle): array
    {
        if ($identifiantArticle === '') {
            return [];
        }

        $rows = DB::table('article_bloc')
            ->where('identifiant_article', $identifiantArticle)
            ->orderBy('ordre_affichage')
            ->get()
            ->all();

        return array_map(
            static fn (object $row): array => [
                'type' => (string) ($row->code_type ?? Article::TYPE_BLOC_PARAGRAPHE),
                'texte' => (string) ($row->texte ?? ''),
                'chemin_public' => (string) ($row->chemin_public ?? ''),
                'type_mime' => (string) ($row->type_mime ?? ''),
                'texte_alternatif' => (string) ($row->texte_alternatif ?? ''),
                'legende' => (string) ($row->legende ?? ''),
                'nom_fichier_original' => (string) ($row->nom_fichier_original ?? ''),
                'taille_octets' => (int) ($row->taille_octets ?? 0),
            ],
            $rows
        );
    }

    private function remplacerBlocs(string $identifiantArticle, array $blocs): void
    {
        DB::table('article_bloc')->where('identifiant_article', $identifiantArticle)->delete();

        foreach (array_values($blocs) as $index => $bloc) {
            if (!is_array($bloc)) {
                continue;
            }

            $type = (string) ($bloc['type'] ?? Article::TYPE_BLOC_PARAGRAPHE);
            if (!in_array($type, [
                Article::TYPE_BLOC_PARAGRAPHE,
                Article::TYPE_BLOC_SOUS_TITRE,
                Article::TYPE_BLOC_IMAGE,
                Article::TYPE_BLOC_VIDEO,
            ], true)) {
                $type = Article::TYPE_BLOC_PARAGRAPHE;
            }

            DB::table('article_bloc')->insert([
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
            ]);
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

    private function formaterDateIso(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return (new DateTimeImmutable((string) $value))->format('c');
        } catch (Throwable) {
            return (string) $value;
        }
    }

    private function limiter(string $valeur, int $limite): string
    {
        return function_exists('mb_substr') ? mb_substr($valeur, 0, $limite) : substr($valeur, 0, $limite);
    }
}
