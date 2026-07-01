<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : services.
 */

return [
    'o2switch' => [
        'laravel_base_path' => env('LARAVEL_BASE_PATH', base_path()),
        'pdf_storage_path' => env('PDF_STORAGE_PATH', storage_path('app/private/uploads/cours')),
        'newsletter_delivery_mode' => env('NEWSLETTER_DELIVERY_MODE', 'direct'),
        'newsletter_batch_size' => (int) env('NEWSLETTER_BATCH_SIZE', 20),
    ],
    'external_api' => [
        'user_agent' => env('API_USER_AGENT', 'cavaliersherouville.fr contact@cavaliersherouville.fr'),
        'cache_ttl' => (int) env('API_CACHE_TTL', 3600),
        'lichess_base_url' => rtrim((string) env('LICHESS_BASE_URL', 'https://lichess.org/api'), '/'),
        'chesscom_base_url' => rtrim((string) env('CHESSCOM_BASE_URL', 'https://api.chess.com/pub'), '/'),
    ],
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
