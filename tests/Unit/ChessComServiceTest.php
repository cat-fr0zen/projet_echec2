<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ChessComService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class ChessComServiceTest extends TestCase
{
    public function test_le_service_chesscom_utilise_le_client_http_laravel_et_normalise_le_resultat(): void
    {
        Http::fake([
            'https://api.chess.com/pub/player/testpseudo' => Http::response([
                'username' => 'TestPseudo',
                'name' => 'Joueur Test',
                'url' => 'https://www.chess.com/member/TestPseudo',
                'country' => 'https://api.chess.com/pub/country/FR',
            ], 200),
            'https://api.chess.com/pub/player/testpseudo/stats' => Http::response([
                'chess_rapid' => [
                    'last' => ['rating' => 1520],
                    'best' => ['rating' => 1600, 'date' => 1718000000],
                ],
            ], 200),
        ]);

        $service = new ChessComService(null);
        $instantane = $service->recupererInstantaneJoueur('TestPseudo');

        self::assertSame('linked', $instantane['status']);
        self::assertSame('FR', $instantane['player']['country']);
        Http::assertSentCount(2);
    }
}
