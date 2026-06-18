<?php

return [
    'proxies' => env('TRUSTED_PROXIES'),
    'headers' => env('TRUSTED_PROXY_HEADERS', 'forwarded'),
    'force_https' => (bool) env('APP_FORCE_HTTPS', false),
];
