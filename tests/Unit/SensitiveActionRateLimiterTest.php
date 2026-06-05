<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\SensitiveActionRateLimiter;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

final class SensitiveActionRateLimiterTest extends TestCase
{
    protected function tearDown(): void
    {
        RateLimiter::clear('legacy-action|connexion|127.0.0.1|alice@example.test');

        parent::tearDown();
    }

    public function test_la_connexion_est_bloquee_apres_cinq_tentatives_rapprochees(): void
    {
        $limiteur = new SensitiveActionRateLimiter();
        $donnees = [
            'identifiant_connexion' => 'alice@example.test',
            '__ip' => '127.0.0.1',
        ];

        for ($tentative = 0; $tentative < 5; $tentative += 1) {
            self::assertNull($limiteur->verifierBlocage('connexion', $donnees));
            $limiteur->enregistrerTentative('connexion', $donnees);
        }

        $blocage = $limiteur->verifierBlocage('connexion', $donnees);

        self::assertIsArray($blocage);
        self::assertSame('connexion', $blocage['action'] ?? null);
        self::assertGreaterThan(0, (int) ($blocage['retry_after'] ?? 0));
    }

    public function test_un_succes_reinitialise_le_compteur_de_connexion(): void
    {
        $limiteur = new SensitiveActionRateLimiter();
        $donnees = [
            'identifiant_connexion' => 'alice@example.test',
            '__ip' => '127.0.0.1',
        ];

        $limiteur->enregistrerTentative('connexion', $donnees);
        $limiteur->enregistrerTentative('connexion', $donnees);
        $limiteur->reinitialiser('connexion', $donnees);

        self::assertNull($limiteur->verifierBlocage('connexion', $donnees));
    }
}
