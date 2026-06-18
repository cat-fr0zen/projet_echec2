<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class ProxySecurityHeadersTest extends TestCase
{
    public function test_le_hsts_est_ajoute_quand_la_requete_arrive_en_https_derriere_un_proxy_de_confiance(): void
    {
        config()->set('trustedproxy.proxies', '*');
        config()->set('trustedproxy.headers', 'forwarded');

        $reponse = $this->withServerVariables([
            'REMOTE_ADDR' => '10.0.0.42',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_HOST' => 'www.cavaliers-herouville.fr',
            'HTTP_X_FORWARDED_PORT' => '443',
        ])->get('/');

        $reponse->assertOk();
        $reponse->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }
}
