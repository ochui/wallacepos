<?php

/**
 *  Application Bootstrap
 * 
 * This file sets up the application environment, loads dependencies,
 * and returns an application instance that can handle requests.
 */

// Define application base path
if (!defined('APP_BASE_PATH')) {
    define('APP_BASE_PATH', realpath(__DIR__ . '/..'));
}

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

// Helper function for resource path
if (!function_exists('resource_path')) {
    function resource_path($path = '')
    {
        return base_path('resources' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
    }
}

// Helper function for asset path
if (!function_exists('asset_path')) {
    function asset_path($path = '')
    {
        return base_path('public/assets' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
    }
}

if (!function_exists('asset_url')) {
    function asset_url($path = '')
    {
        return '/assets' . ($path ? '/' . ltrim(str_replace('\\', '/', $path), '/') : '');
    }
}

// Helper function for config path
if (!function_exists('config_path')) {
    function config_path($path = '')
    {
        return base_path('config' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
    }
}

// Helper function for configuration access
if (!function_exists('config')) {
    function config($key = null, $default = null)
    {
        if (is_null($key)) {
            return App\Core\Config::all();
        }
        return App\Core\Config::get($key, $default);
    }
}

// Load environment variables
if (file_exists(base_path('.env'))) {
    $dotenv = Dotenv\Dotenv::createImmutable(base_path());
    $dotenv->load();
}

// Load configuration
$config = require_once config_path('app.php');

// Store config globally for compatibility
$GLOBALS['app_config'] = $config;

// Return the application instance
return new App\Core\Application();
