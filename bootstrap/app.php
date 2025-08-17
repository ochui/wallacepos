<?php
/**
 * WallacePOS Application Bootstrap
 * 
 * This file sets up the application environment, loads dependencies,
 * and returns an application instance that can handle requests.
 */

// Load environment variables
if (file_exists(__DIR__.'/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/..');
    $dotenv->load();
}

// Load configuration
require_once __DIR__.'/../config/app.php';

// Return the application instance
return new App\Core\Application();