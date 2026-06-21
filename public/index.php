<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : index.
 */

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$app->handleRequest(Request::capture());
