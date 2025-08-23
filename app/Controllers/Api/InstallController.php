<?php

/**
 * API Installation Controller
 * Handles installation-related API endpoints
 */

namespace App\Controllers\Api;

use App\Utility\DbUpdater;
use App\Controllers\Admin\AdminSettings;

class InstallController
{
    private $result = ["errorCode" => "OK", "error" => "OK", "data" => ""];

    public function __construct()
    {
        header("Content-Type: application/json");
    }

    /**
     * Perform database installation
     */
    public function install()
    {
        try {
            $dbUpdater = new DbUpdater();
            $result = $dbUpdater->install();
            
            if (strpos($result, 'Installation Completed!') !== false || 
                strpos($result, 'Database detected, skipping full installation.') !== false) {
                $this->result['data'] = $result;
            } else {
                $this->result['errorCode'] = "install";
                $this->result['error'] = $result;
            }
        } catch (\Exception $e) {
            $this->result['errorCode'] = "exception";
            $this->result['error'] = "Installation failed: " . $e->getMessage();
        }

        return $this->returnResult();
    }

    /**
     * Perform database upgrade
     */
    public function upgrade()
    {
        try {
            $dbUpdater = new DbUpdater();
            $result = $dbUpdater->upgrade();
            
            if (strpos($result, 'Update completed') !== false || 
                strpos($result, 'Already upgraded') !== false) {
                $this->result['data'] = $result;
            } else {
                $this->result['errorCode'] = "upgrade";
                $this->result['error'] = $result;
            }
        } catch (\Exception $e) {
            $this->result['errorCode'] = "exception";
            $this->result['error'] = "Upgrade failed: " . $e->getMessage();
        }

        return $this->returnResult();
    }

    /**
     * Check installation status
     */
    public function status()
    {
        try {
            $dbUpdater = new DbUpdater();
            // Try to check if database is installed by creating a new instance
            // and checking if we can query the auth table
            $isInstalled = false;
            $version = null;
            
            try {
                $dbConfig = new \App\Database\DbConfig();
                $result = $dbConfig->_db->query("SELECT 1 FROM `auth` LIMIT 1");
                if ($result !== false) {
                    $isInstalled = true;
                    $result->closeCursor();
                    
                    // Try to get version
                    try {
                        if (class_exists('App\Controllers\Admin\AdminSettings')) {
                            $settings = AdminSettings::getSettingsObject('general');
                            if (isset($settings->version)) {
                                $version = $settings->version;
                            }
                        }
                    } catch (\Exception $e) {
                        // Version not set yet
                    }
                }
            } catch (\Exception $e) {
                $isInstalled = false;
            }
            
            $this->result['data'] = [
                'installed' => $isInstalled,
                'version' => $version,
                'latest_version' => $dbUpdater->getLatestVersionName()
            ];
        } catch (\Exception $e) {
            $this->result['errorCode'] = "exception";
            $this->result['error'] = "Status check failed: " . $e->getMessage();
        }

        return $this->returnResult();
    }

    /**
     * Check system requirements
     */
    public function requirements()
    {
        try {
            $requirements = $this->checkRequirements();
            $this->result['data'] = $requirements;
        } catch (\Exception $e) {
            $this->result['errorCode'] = "exception";
            $this->result['error'] = "Requirements check failed: " . $e->getMessage();
        }

        return $this->returnResult();
    }

    /**
     * Check all system requirements
     */
    private function checkRequirements()
    {
        $result = [
            "webserver" => true,
            "php" => true,
            "all" => true,
            "requirements" => []
        ];

        // Check PHP version
        $phpVersion = PHP_VERSION;
        $phpOk = version_compare($phpVersion, "8.0.0") >= 0;
        $result['requirements'][] = [
            'name' => 'PHP Version',
            'status' => $phpOk,
            'current' => $phpVersion,
            'required' => '8.0.0+',
            'type' => 'php'
        ];
        if (!$phpOk) {
            $result['php'] = false;
            $result['all'] = false;
        }

        // Check web server
        $webServer = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
        $result['requirements'][] = [
            'name' => 'Web Server',
            'status' => true,
            'current' => $webServer,
            'required' => 'Any (Apache, Nginx, etc.)',
            'type' => 'webserver'
        ];

        // Check required PHP extensions
        $extensions = [
            'pdo' => 'PDO',
            'pdo_mysql' => 'PDO MySQL',
            'json' => 'JSON',
            'curl' => 'cURL',
            'mbstring' => 'Multibyte String',
            'openssl' => 'OpenSSL',
            'xml' => 'XML',
            'zip' => 'ZIP'
        ];

        foreach ($extensions as $ext => $name) {
            $loaded = extension_loaded($ext);
            $result['requirements'][] = [
                'name' => "PHP Extension: $name",
                'status' => $loaded,
                'current' => $loaded ? 'Installed' : 'Not installed',
                'required' => 'Required',
                'type' => 'php_extension'
            ];
            if (!$loaded) {
                $result['all'] = false;
            }
        }

        // Check file permissions
        $paths = [
            storage_path() => 'Storage directory',
            storage_path('logs') => 'Logs directory', 
            base_path('bootstrap/cache') => 'Bootstrap cache directory'
        ];

        foreach ($paths as $path => $name) {
            $writable = is_dir($path) && is_writable($path);
            $result['requirements'][] = [
                'name' => "Write Permission: $name",
                'status' => $writable,
                'current' => $writable ? 'Writable' : 'Not writable',
                'required' => 'Must be writable',
                'type' => 'permission'
            ];
            if (!$writable) {
                $result['all'] = false;
            }
        }

        // Check database configuration file
        $dbConfigPath = base_path('library/wpos/.dbconfig.json');
        $dbConfigExists = file_exists($dbConfigPath);
        $result['requirements'][] = [
            'name' => 'Database Configuration',
            'status' => $dbConfigExists,
            'current' => $dbConfigExists ? 'Configured' : 'Not configured',
            'required' => 'Database connection must be configured',
            'type' => 'database'
        ];

        return $result;
    }

    /**
     * Encodes and returns the json result object
     */
    private function returnResult()
    {
        if (($resstr = json_encode($this->result)) === false) {
            echo json_encode(["error" => "Failed to encode the response data into json"]);
        } else {
            echo $resstr;
        }
        exit();
    }
}