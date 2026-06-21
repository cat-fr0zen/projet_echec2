<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : trustedproxy.
 */

return [
    'proxies' => env('TRUSTED_PROXIES'),
    'headers' => env('TRUSTED_PROXY_HEADERS', 'forwarded'),
    'force_https' => (bool) env('APP_FORCE_HTTPS', false),
];
