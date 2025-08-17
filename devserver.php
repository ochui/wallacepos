<?php

/**
 * WallacePOS Development Server
 * 
 * A simple CLI tool to start a local development server with URL rewrite support
 * for the WallacePOS application.
 * 
 * Usage: php devserver.php [port] [host]
 * 
 * @author Ochui, Princewill
 * @since August 17, 2025
 */

// Default configuration
$defaultPort = 8080;
$defaultHost = 'localhost';
$documentRoot = __DIR__ . '/public';

// Parse command line arguments
$port = $argv[1] ?? $defaultPort;
$host = $argv[2] ?? $defaultHost;

// Validate port
if (!is_numeric($port) || $port < 1 || $port > 65535) {
    echo "Error: Invalid port number. Must be between 1 and 65535.\n";
    exit(1);
}

// Check if document root exists
if (!is_dir($documentRoot)) {
    echo "Error: Document root directory does not exist: {$documentRoot}\n";
    exit(1);
}

echo "WallacePOS Development Server\n";
echo "============================\n";
echo "Starting server on http://{$host}:{$port}\n";
echo "Document root: {$documentRoot}\n";
echo "Press Ctrl+C to stop the server\n\n";

// Start the built-in PHP server with our router script
$routerScript = __DIR__ . '/router.php';
$command = sprintf(
    'php -S %s:%d -t %s %s',
    escapeshellarg($host),
    $port,
    escapeshellarg($documentRoot),
    escapeshellarg($routerScript)
);

// Execute the server
passthru($command);
