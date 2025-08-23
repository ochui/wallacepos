<?php
/**
 * Simple Installation API Endpoint
 * Handles installation-related requests without complex routing
 */

// Define base paths
define('APP_BASE_PATH', realpath(__DIR__ . '/../..'));

// Simple autoloader for FreePOS classes
spl_autoload_register(function ($class) {
    // Convert namespace to file path
    $file = str_replace(['App\\', '\\'], ['', '/'], $class);
    $path = APP_BASE_PATH . '/app/' . $file . '.php';
    
    if (file_exists($path)) {
        require_once $path;
        return true;
    }
    
    return false;
});

// Helper function for base path
if (!function_exists('base_path')) {
    function base_path($path = '')
    {
        return APP_BASE_PATH . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : '');
    }
}

// Helper function for storage path
if (!function_exists('storage_path')) {
    function storage_path($path = '')
    {
        return base_path('storage' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
    }
}

// Basic environment variable loader (fallback for .env if no Composer)
if (file_exists(base_path('.env'))) {
    $lines = file(base_path('.env'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }
}

try {
    // Handle installation API routes manually
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    // Create the installer controller
    $controller = new App\Controllers\Api\InstallController();
    
    // Route to appropriate methods based on action parameter
    switch ($action) {
        case 'requirements':
            $controller->requirements();
            break;
        case 'test-database':
            $controller->testDatabase();
            break;
        case 'save-database':
            $controller->saveDatabaseConfig();
            break;
        case 'configure-admin':
            $controller->configureAdmin();
            break;
        case 'install-with-config':
            $controller->installWithConfig();
            break;
        case 'status':
            $controller->status();
            break;
        case 'upgrade':
            $controller->upgrade();
            break;
        case 'install':
            $controller->install();
            break;
        default:
            // Default action or unknown action
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode([
                'errorCode' => 'invalid_action',
                'error' => 'Invalid or missing action parameter'
            ]);
            break;
    }
    
} catch (Exception $e) {
    // Handle errors gracefully
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'errorCode' => 'exception',
        'error' => 'Installation API error: ' . $e->getMessage()
    ]);
}