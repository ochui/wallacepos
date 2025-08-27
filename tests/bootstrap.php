<?php

/**
 * PHPUnit Bootstrap File
 * Sets up the testing environment for FreePOS
 */

// Include Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Define testing constants
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// Load environment variables if .env.testing exists
$envTestingPath = BASE_PATH . '/.env.testing';
if (file_exists($envTestingPath)) {
    $dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH, '.env.testing');
    $dotenv->load();
}

// Helper functions for tests
if (!function_exists('base_path')) {
    function base_path($path = '') {
        return BASE_PATH . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('storage_path')) {
    function storage_path($path = '') {
        return base_path('storage') . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

// Set timezone for consistent test results
date_default_timezone_set('UTC');

// Set testing environment flag
$_ENV['TESTING'] = true;
putenv('TESTING=true');

// Initialize session for testing
if (!session_id()) {
    session_start();
}

// Set up clean test environment
$_SERVER = array_merge($_SERVER, [
    'REQUEST_METHOD' => 'GET',
    'REQUEST_URI' => '/test',
    'HTTP_HOST' => 'localhost',
    'SERVER_NAME' => 'localhost',
    'SCRIPT_NAME' => '/index.php',
    'QUERY_STRING' => '',
    'DOCUMENT_ROOT' => base_path('public'),
    'CONTENT_TYPE' => 'application/json',
    'HTTP_ACCEPT' => 'application/json',
]);

// Start output buffering to prevent unwanted output during tests
ob_start();