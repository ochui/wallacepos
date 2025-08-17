<?php
/**
 * WallacePOS Development Server Router
 * 
 * This router script handles URL rewriting for the WallacePOS development server,
 * mimicking the behavior of Apache .htaccess files.
 * 
 * @author Ochui, Princewill
 * @since August 17, 2025
 */

// Get the requested URI
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$queryString = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);

// Remove leading slash for pattern matching
$path = ltrim($requestUri, '/');

// Function to serve a file
function serveFile($file) {
    if (file_exists($file) && is_file($file)) {
        // Set appropriate content type
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        $mimeTypes = [
            'html' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'ico' => 'image/x-icon',
            'svg' => 'image/svg+xml',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject',
            'appcache' => 'text/cache-manifest'
        ];
        
        $contentType = $mimeTypes[$extension] ?? 'application/octet-stream';
        header("Content-Type: {$contentType}");
        
        readfile($file);
        return true;
    }
    return false;
}

// Function to redirect with query string
function redirectWithQuery($target, $action = null, $originalQuery = null) {
    $query = '';
    if ($action) {
        $query = "a=" . urlencode($action);
    }
    if ($originalQuery) {
        $query .= ($query ? '&' : '') . $originalQuery;
    }
    
    $newUri = $target . ($query ? '?' . $query : '');
    $_SERVER['REQUEST_URI'] = $newUri;
    $_GET['a'] = $action;
    
    // Parse additional query parameters
    if ($originalQuery) {
        parse_str($originalQuery, $params);
        $_GET = array_merge($_GET, $params);
    }
    
    return $target;
}

// Block access to sensitive files
if (preg_match('/\.(json|htaccess)$/i', $path)) {
    http_response_code(403);
    echo "403 Forbidden";
    return false;
}

// Handle static files first
$staticFile = __DIR__ . '/public/' . $path;
if (file_exists($staticFile) && is_file($staticFile)) {
    return serveFile($staticFile);
}

// URL Rewriting Logic (based on .htaccess rules)

// Main POS application
if ($path === '' || $path === 'pos' || $path === 'pos/') {
    return serveFile(__DIR__ . '/public/index.html');
}

// Admin interface
if ($path === 'admin' || $path === 'admin/') {
    return serveFile(__DIR__ . '/public/admin/index.html');
}
if (preg_match('#^admin/(.*)$#', $path, $matches)) {
    $adminFile = __DIR__ . '/public/admin/' . $matches[1];
    if (file_exists($adminFile)) {
        return serveFile($adminFile);
    }
}

// Kitchen display
if ($path === 'kitchen' || $path === 'kitchen/') {
    return serveFile(__DIR__ . '/public/kitchen/index.html');
}
if (preg_match('#^kitchen/(.*)$#', $path, $matches)) {
    $kitchenFile = __DIR__ . '/public/kitchen/' . $matches[1];
    if (file_exists($kitchenFile)) {
        return serveFile($kitchenFile);
    }
}

// Customer portal
if ($path === 'customer' || $path === 'customer/') {
    return serveFile(__DIR__ . '/public/customer/index.html');
}
if (preg_match('#^customer/(.*)$#', $path, $matches)) {
    $customerFile = __DIR__ . '/public/customer/' . $matches[1];
    if (file_exists($customerFile)) {
        return serveFile($customerFile);
    }
}

// API requests - route to wpos.php
if (preg_match('#^api/(.*)$#', $path, $matches)) {
    $action = $matches[1];
    $target = redirectWithQuery('/api/wpos.php', $action, $queryString);
    include __DIR__ . '/public/api/wpos.php';
    return true;
}

// Customer API requests - route to customerapi.php  
if (preg_match('#^customerapi/(.*)$#', $path, $matches)) {
    $action = $matches[1];
    $target = redirectWithQuery('/api/customerapi.php', $action, $queryString);
    include __DIR__ . '/public/api/customerapi.php';
    return true;
}

// Library files - proxy to parent directory for PHP includes
if (preg_match('#^library/(.*)$#', $path, $matches)) {
    $libraryFile = __DIR__ . '/library/' . $matches[1];
    if (file_exists($libraryFile)) {
        return serveFile($libraryFile);
    }
}

// Installer - proxy to parent directory
if (preg_match('#^installer/?(.*)$#', $path, $matches)) {
    $installerPath = $matches[1] ?: 'index.php';
    $installerFile = __DIR__ . '/installer/' . $installerPath;
    if (file_exists($installerFile)) {
        if (pathinfo($installerFile, PATHINFO_EXTENSION) === 'php') {
            include $installerFile;
            return true;
        } else {
            return serveFile($installerFile);
        }
    }
}

// Service worker - local file
if ($path === 'sw.js') {
    return serveFile(__DIR__ . '/public/sw.js');
}

// App cache manifest - proxy to parent directory
if ($path === 'wpos.appcache') {
    return serveFile(__DIR__ . '/wpos.appcache');
}

// If no rule matched and file doesn't exist, return 404
http_response_code(404);
echo "404 Not Found - Path: " . htmlspecialchars($path);
return false;
