<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : MailProviderConfig.
 */

declare(strict_types=1);

namespace App\Support;

final class MailProviderConfig
{
    /**
     * @return array{provider: string, host: string, port: int, encryption: ?string}
     */
    public static function resolve(
        ?string $provider,
        ?string $host,
        int|string|null $port,
        ?string $encryption
    ): array {
        $providerNormalise = self::normaliserProvider($provider);
        $preset = self::preset($providerNormalise);

        $hostFinal = trim((string) ($host ?? ''));
        $encryptionFinale = self::normaliserEncryption($encryption);
        $portFinal = self::normaliserPort($port);

        if ($hostFinal === '' && $preset !== null) {
            $hostFinal = $preset['host'];
        }

        if ($portFinal === null && $preset !== null) {
            $portFinal = $preset['port'];
        }

        if ($encryptionFinale === null && $preset !== null) {
            $encryptionFinale = $preset['encryption'];
        }

        return [
            'provider' => $providerNormalise,
            'host' => $hostFinal !== '' ? $hostFinal : '127.0.0.1',
            'port' => $portFinal ?? 1025,
            'encryption' => $encryptionFinale,
        ];
    }

    /**
     * @return array<string, array{host: string, port: int, encryption: ?string}>
     */
    public static function supportedProviders(): array
    {
        return [
            'custom' => [
                'host' => '',
                'port' => 1025,
                'encryption' => null,
            ],
            'gmail' => [
                'host' => 'smtp.gmail.com',
                'port' => 587,
                'encryption' => 'tls',
            ],
            'ovh' => [
                'host' => 'smtp.mail.ovh.net',
                'port' => 587,
                'encryption' => 'tls',
            ],
            'infomaniak' => [
                'host' => 'mail.infomaniak.com',
                'port' => 587,
                'encryption' => 'tls',
            ],
            'brevo' => [
                'host' => 'smtp-relay.brevo.com',
                'port' => 587,
                'encryption' => 'tls',
            ],
            'outlook365' => [
                'host' => 'smtp.office365.com',
                'port' => 587,
                'encryption' => 'tls',
            ],
        ];
    }

    private static function normaliserProvider(?string $provider): string
    {
        $providerNormalise = strtolower(trim((string) $provider));

        return array_key_exists($providerNormalise, self::supportedProviders())
            ? $providerNormalise
            : 'custom';
    }

    /**
     * @return array{host: string, port: int, encryption: ?string}|null
     */
    private static function preset(string $provider): ?array
    {
        return self::supportedProviders()[$provider] ?? null;
    }

    private static function normaliserPort(int|string|null $port): ?int
    {
        if ($port === null || $port === '') {
            return null;
        }

        $portNumerique = (int) $port;

        return $portNumerique > 0 ? $portNumerique : null;
    }

    private static function normaliserEncryption(?string $encryption): ?string
    {
        $encryptionNormalisee = strtolower(trim((string) $encryption));

        if ($encryptionNormalisee === '' || $encryptionNormalisee === 'null') {
            return null;
        }

        return in_array($encryptionNormalisee, ['tls', 'ssl'], true)
            ? $encryptionNormalisee
            : null;
    }
}
