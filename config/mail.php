<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : mail.
 */

use App\Support\MailProviderConfig;

$mailProvider = MailProviderConfig::resolve(
    env('MAIL_PROVIDER', 'custom'),
    env('MAIL_HOST', ''),
    env('MAIL_PORT', 1025),
    env('MAIL_ENCRYPTION')
);

$mailVerifyPeer = filter_var(env('MAIL_VERIFY_PEER', true), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
$mailVerifyPeerName = filter_var(env('MAIL_VERIFY_PEER_NAME', true), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
$mailAllowSelfSigned = filter_var(env('MAIL_ALLOW_SELF_SIGNED', false), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
$mailCaFile = trim((string) env('MAIL_CA_FILE', ''));

return [
    'default' => env('MAIL_MAILER', 'smtp'),

    'mailers' => [
        'smtp' => [
            'transport' => 'smtp',
            'host' => $mailProvider['host'],
            'port' => $mailProvider['port'],
            'encryption' => $mailProvider['encryption'],
            'username' => env('MAIL_USERNAME', env('MAIL_FROM')),
            'password' => env('MAIL_PASSWORD', env('MAIL_PASS')),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN'),
            'verify_peer' => $mailVerifyPeer ?? true,
            'stream' => [
                'ssl' => array_filter([
                    'verify_peer' => $mailVerifyPeer ?? true,
                    'verify_peer_name' => $mailVerifyPeerName ?? true,
                    'allow_self_signed' => $mailAllowSelfSigned ?? false,
                    'cafile' => $mailCaFile !== '' ? $mailCaFile : null,
                ], static fn (mixed $value): bool => $value !== null),
            ],
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],
    ],

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', env('MAIL_FROM', 'noreply@cavaliers-herouville.fr')),
        'name' => env('MAIL_FROM_NAME', "Cavaliers d'Herouville"),
    ],
];
