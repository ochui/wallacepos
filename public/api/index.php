<?php

/**
 * Main API Entry Point
 * 
 */

// Register the Composer autoloader...
require __DIR__ . '/../../vendor/autoload.php';

// Bootstrap and handle the request...
/** @var \App\Core\Application $app */
$app = require_once __DIR__ . '/../../bootstrap/app.php';

$app->handleRequest();
