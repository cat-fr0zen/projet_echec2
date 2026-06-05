<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\MailProviderConfig;
use PHPUnit\Framework\TestCase;

final class MailProviderConfigTest extends TestCase
{
    public function test_un_preset_gmail_est_resolu_correctement(): void
    {
        $config = MailProviderConfig::resolve('gmail', '', null, null);

        self::assertSame('gmail', $config['provider']);
        self::assertSame('smtp.gmail.com', $config['host']);
        self::assertSame(587, $config['port']);
        self::assertSame('tls', $config['encryption']);
    }

    public function test_un_preset_brevo_est_resolu_correctement(): void
    {
        $config = MailProviderConfig::resolve('brevo', '', null, null);

        self::assertSame('brevo', $config['provider']);
        self::assertSame('smtp-relay.brevo.com', $config['host']);
        self::assertSame(587, $config['port']);
        self::assertSame('tls', $config['encryption']);
    }

    public function test_les_surcharges_manuelles_prennent_le_pas_sur_le_preset(): void
    {
        $config = MailProviderConfig::resolve('ovh', 'smtp.personnalise.test', '2525', 'ssl');

        self::assertSame('ovh', $config['provider']);
        self::assertSame('smtp.personnalise.test', $config['host']);
        self::assertSame(2525, $config['port']);
        self::assertSame('ssl', $config['encryption']);
    }

    public function test_un_provider_inconnu_retombe_sur_custom(): void
    {
        $config = MailProviderConfig::resolve('provider-inconnu', '', null, null);

        self::assertSame('custom', $config['provider']);
        self::assertSame('127.0.0.1', $config['host']);
        self::assertSame(1025, $config['port']);
        self::assertNull($config['encryption']);
    }
}
