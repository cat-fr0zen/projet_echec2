<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Repositories\MediaRepository;
use Tests\TestCase;

final class MediaRepositoryTest extends TestCase
{
    public function test_le_repository_retourne_une_liste_vide_si_les_tables_medias_sont_absentes(): void
    {
        $repository = new MediaRepository();

        self::assertSame([], $repository->trouverPublies());
        self::assertSame([], $repository->listerTous());
        self::assertNull($repository->trouverParIdentifiant('media_test'));
    }

    public function test_la_recherche_par_nom_de_fichier_retourne_null_si_les_tables_sont_absentes(): void
    {
        $repository = new MediaRepository();

        self::assertNull($repository->trouverParNomFichierStocke('photo-test.jpg'));
    }
}
