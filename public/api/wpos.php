<?php
/**
 * WallacePOS Main API Entry Point
 * 
 * This file implements the modern bootstrap pattern for API requests.
 * It replaces the legacy direct instantiation approach with proper
 * PSR-4 autoloading and dependency injection.
 */

// Register the Composer autoloader...
require __DIR__ . '/../../vendor/autoload.php';

// Bootstrap and handle the request...
/** @var \App\Core\Application $app */
$app = require_once __DIR__ . '/../../bootstrap/app.php';

$app->handleRequest();
