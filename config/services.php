<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : services.
 */

return [
    'google_places' => [
        'api_key' => env('GOOGLE_PLACES_API_KEY', ''),
    ],
    'shop_card' => [
        'enabled' => (bool) env('SHOP_CARD_PAYMENT_ENABLED', false),
        'provider' => env('SHOP_CARD_PROVIDER', 'stripe'),
        'provider_label' => env('SHOP_CARD_PROVIDER_LABEL', 'Prestataire carte bancaire'),
        'checkout_url' => env('SHOP_CARD_CHECKOUT_URL', ''),
    ],
];
