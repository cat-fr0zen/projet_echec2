<?php
declare(strict_types=1);

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

require_once dirname(__DIR__) . '/app/config.php';

$base = o2switch_require_laravel_base_path();

require $base . '/vendor/autoload.php';

$app = require $base . '/bootstrap/app.php';
$app->handleRequest(Request::capture());
