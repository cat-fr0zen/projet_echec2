<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class ProductionReadinessTest extends TestCase
{
    public function test_le_fichier_env_exemple_fournit_un_profil_public_plus_sur(): void
    {
        $contenu = (string) file_get_contents(base_path('.env.example'));

        self::assertStringContainsString('APP_ENV=production', $contenu);
        self::assertStringContainsString('APP_DEBUG=false', $contenu);
        self::assertStringContainsString('APP_URL=https://', $contenu);
        self::assertStringContainsString('APP_FORCE_HTTPS=true', $contenu);
        self::assertStringContainsString('SESSION_SECURE_COOKIE=true', $contenu);
        self::assertStringContainsString('TRUSTED_PROXIES=', $contenu);
        self::assertStringContainsString('QUEUE_CONNECTION=database', $contenu);
        self::assertStringContainsString('MAIL_MAILER=smtp', $contenu);
    }

    public function test_la_page_accueil_expose_des_en_tetes_http_de_base_pour_la_prod(): void
    {
        $reponse = $this->get('/');

        $reponse->assertOk();
        $reponse->assertHeader('X-Content-Type-Options', 'nosniff');
        $reponse->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $reponse->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $reponse->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $reponse->assertHeader('Content-Security-Policy');
        self::assertStringContainsString("default-src 'self'", (string) $reponse->headers->get('Content-Security-Policy'));
        self::assertStringContainsString("script-src 'self' 'nonce-", (string) $reponse->headers->get('Content-Security-Policy'));
    }

    public function test_la_fenetre_de_consentement_propose_un_parcours_essentiel_non_bloquant(): void
    {
        $reponse = $this->get('/');

        $reponse->assertOk();
        $reponse->assertSee('data-consent-continue', false);
        $reponse->assertSeeText('Continuer avec les cookies essentiels');
        $reponse->assertSeeText('Autoriser aussi le cookie de thème');
    }
}
