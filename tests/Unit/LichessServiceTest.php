<?php
/**
 * Verifie l'integration publique Lichess.
 */

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\LichessService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class LichessServiceTest extends TestCase
{
    public function test_le_service_lichess_utilise_le_client_http_laravel_et_normalise_le_resultat(): void
    {
        Http::fake([
            'https://lichess.org/api/user/testlichess' => Http::response([
                'username' => 'TestLichess',
                'url' => 'https://lichess.org/@/TestLichess',
                'nbFollowers' => 12,
                'seenAt' => 1718000000000,
                'profile' => [
                    'country' => 'FR',
                    'fideRating' => 1842,
                ],
                'perfs' => [
                    'blitz' => [
                        'rating' => 1620,
                        'games' => 53,
                        'prog' => 12,
                    ],
                ],
            ], 200),
        ]);

        $service = new LichessService(null);
        $instantane = $service->recupererInstantaneJoueur('TestLichess');

        self::assertSame('linked', $instantane['status']);
        self::assertSame('FR', $instantane['player']['country']);
        self::assertSame(1842, $instantane['player']['fide']);
        self::assertSame(1620, $instantane['ratings'][0]['rating']);
        Http::assertSentCount(1);
    }
}

