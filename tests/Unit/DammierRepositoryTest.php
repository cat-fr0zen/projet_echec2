<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Repositories\DammierRepository;
use Tests\TestCase;

final class DammierRepositoryTest extends TestCase
{
    public function test_le_repository_retourne_un_puzzle_de_secours_si_les_tables_sont_absentes(): void
    {
        $repository = new DammierRepository();
        $puzzle = $repository->obtenirPuzzleHebdomadaire();

        self::assertSame('dammier_secours', $puzzle['dammier_id']);
        self::assertSame('fallback_local', $puzzle['dammier_source']);
        self::assertNotEmpty($puzzle['dammier_week_key']);
    }

    public function test_le_classement_retourne_une_liste_vide_si_les_tables_scores_sont_absentes(): void
    {
        $repository = new DammierRepository();

        self::assertSame([], $repository->listerClassementHebdomadaire('2026-W25', 'dammier_test'));
    }
}
