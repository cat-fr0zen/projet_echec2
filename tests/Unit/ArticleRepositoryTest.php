<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Repositories\ArticleRepository;
use Tests\TestCase;

final class ArticleRepositoryTest extends TestCase
{
    public function test_le_repository_retourne_une_liste_vide_si_les_tables_articles_sont_absentes(): void
    {
        $repository = new ArticleRepository();

        self::assertSame([], $repository->trouverPublies());
        self::assertSame([], $repository->listerTous());
        self::assertNull($repository->trouverParIdentifiant('article_test'));
    }

    public function test_la_recherche_de_bloc_media_retourne_null_si_les_tables_sont_absentes(): void
    {
        $repository = new ArticleRepository();

        self::assertNull($repository->trouverBlocMediaParNomFichierStocke('image-test.jpg'));
    }
}
