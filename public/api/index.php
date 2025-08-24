<?php

/**
 * Main API Entry Point
 * 
 */

$vendorAutoload = __DIR__ . '/../../vendor/autoload.php';
$composerInstalled = file_exists($vendorAutoload);

if (!$composerInstalled) {

    header('Content-Type: application/json');
    echo json_encode([
        'errorCode' => 'dependency',
        'error' => 'Composer dependencies not installed. Please run "composer install" first.',
        'data' => null
    ]);
    exit;
}

require $vendorAutoload;

/** @var \App\Core\Application $app */
$app = require_once __DIR__ . '/../../bootstrap/app.php';

$app->handleRequest();
