<?php

namespace App\Core;

/**
 * Configuration management class for WallacePOS
 * 
 * Provides Laravel-style configuration access while maintaining
 * compatibility with legacy configuration methods.
 */
class Config
{
    private static $config = [];
    private static $loaded = false;

    /**
     * Load configuration from files
     */
    private static function loadConfig()
    {
        if (self::$loaded) {
            return;
        }

        // Load main app config
        if (function_exists('config_path') && file_exists(config_path('app.php'))) {
            self::$config['app'] = require config_path('app.php');
        }

        // Load database config if it exists
        if (function_exists('config_path') && file_exists(config_path('database.php'))) {
            self::$config['database'] = require config_path('database.php');
        }

        // Load from global config if available (for compatibility)
        if (isset($GLOBALS['app_config'])) {
            self::$config = array_merge(self::$config, ['app' => $GLOBALS['app_config']]);
        }

        self::$loaded = true;
    }

    /**
     * Get a configuration value using dot notation
     * 
     * @param string $key Configuration key (e.g., 'app.timezone', 'database.host')
     * @param mixed $default Default value if key not found
     * @return mixed Configuration value
     */
    public static function get($key, $default = null)
    {
        self::loadConfig();

        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return $default;
            }
        }

        return $value;
    }

    /**
     * Set a configuration value
     * 
     * @param string $key Configuration key
     * @param mixed $value Configuration value
     */
    public static function set($key, $value)
    {
        self::loadConfig();

        $keys = explode('.', $key);
        $config = &self::$config;

        while (count($keys) > 1) {
            $segment = array_shift($keys);
            if (!isset($config[$segment]) || !is_array($config[$segment])) {
                $config[$segment] = [];
            }
            $config = &$config[$segment];
        }

        $config[array_shift($keys)] = $value;
    }

    /**
     * Get all configuration
     * 
     * @return array All configuration values
     */
    public static function all()
    {
        self::loadConfig();
        return self::$config;
    }
}