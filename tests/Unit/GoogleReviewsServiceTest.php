<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : GoogleReviewsServiceTest.
 */

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\GoogleReviewsService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class GoogleReviewsServiceTest extends TestCase
{
    public function test_le_service_google_avis_utilise_le_client_http_laravel_et_retourne_un_instantane_disponible(): void
    {
        Http::fake([
            'https://places.googleapis.com/v1/places:searchText' => Http::response([
                'places' => [[
                    'id' => 'place-123',
                    'displayName' => ['text' => 'Club test'],
                    'formattedAddress' => '1 rue du Test',
                    'googleMapsUri' => 'https://maps.google.test/place-123',
                ]],
            ], 200),
            'https://places.googleapis.com/v1/places/place-123' => Http::response([
                'id' => 'place-123',
                'displayName' => ['text' => 'Club test'],
                'formattedAddress' => '1 rue du Test',
                'googleMapsUri' => 'https://maps.google.test/place-123',
                'rating' => 4.7,
                'userRatingCount' => 12,
                'reviews' => [[
                    'rating' => 5,
                    'relativePublishTimeDescription' => 'il y a 2 jours',
                    'text' => ['text' => 'Très bon club'],
                    'authorAttribution' => ['displayName' => 'Alice'],
                ]],
            ], 200),
        ]);

        $service = new GoogleReviewsService(null, 'cle-api-test');
        $instantane = $service->recupererAvisLieu('club-test', 'Cavaliers Herouville');

        self::assertSame('disponible', $instantane['statut']);
        self::assertSame('Club test', $instantane['nom_lieu']);
        Http::assertSentCount(2);
    }
}
