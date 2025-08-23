<?php
// Simple router for PHP development server
// This handles URL rewriting since .htaccess doesn't work with PHP dev server

$uri = $_SERVER['REQUEST_URI'];
$uri = rtrim($uri, '/');

// Remove query string for routing
$path = parse_url($uri, PHP_URL_PATH);

// Handle installer route
if ($path === '/installer' || $path === '') {
    $_SERVER['REQUEST_URI'] = '/installer';
    require_once 'api/index.php';
    return true;
}

// Handle API routes  
if (strpos($path, '/api/') === 0) {
    require_once 'api/index.php';
    return true;
}

// Handle static files
if (file_exists(__DIR__ . $uri)) {
    return false; // Serve the requested resource as-is
}

// Default to index.html for other routes
require_once 'index.html';
return true;