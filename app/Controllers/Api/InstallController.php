<?php

/**
 * API Installation Controller
 * Handles installation-related API endpoints
 */

namespace App\Controllers\Api;

use App\Utility\DbUpdater;
use App\Controllers\Admin\AdminSettings;
use App\Database\DbConfig;

class InstallController
{
    private $result = ["errorCode" => "OK", "error" => "OK", "data" => ""];

    public function __construct()
    {
        header("Content-Type: application/json");
        
        // Start session for multi-step installer
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Helper function to get base path
     */
    private function basePath($path = '')
    {
        $basePath = dirname(dirname(dirname(__DIR__)));
        return $basePath . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : '');
    }

    /**
     * Helper function to get storage path
     */
    private function storagePath($path = '')
    {
        $storagePath = $this->basePath('storage');
        return $storagePath . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : '');
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
            $this->storagePath() => 'Storage directory',
            $this->storagePath('logs') => 'Logs directory', 
            $this->basePath('bootstrap/cache') => 'Bootstrap cache directory'
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

        // Check composer packages are installed
        $vendorPath = $this->basePath('vendor');
        $composerInstalled = is_dir($vendorPath) && file_exists($vendorPath . '/autoload.php');
        $result['requirements'][] = [
            'name' => 'Composer Dependencies',
            'status' => $composerInstalled,
            'current' => $composerInstalled ? 'Installed' : 'Not installed',
            'required' => 'Run: composer install',
            'type' => 'dependency'
        ];
        if (!$composerInstalled) {
            $result['all'] = false;
        }

        // Check .env file exists (instead of old .dbconfig.json)
        $envPath = $this->basePath('.env');
        $envExists = file_exists($envPath);
        $result['requirements'][] = [
            'name' => 'Environment Configuration',
            'status' => $envExists,
            'current' => $envExists ? 'Configured' : 'Not configured',
            'required' => 'Will be created during installation',
            'type' => 'configuration'
        ];

        return $result;
    }

    /**
     * Test database connection and configuration
     */
    public function testDatabase()
    {
        try {
            $host = $_POST['host'] ?? $_GET['host'] ?? 'localhost';
            $port = $_POST['port'] ?? $_GET['port'] ?? '3306';
            $database = $_POST['database'] ?? $_GET['database'] ?? '';
            $username = $_POST['username'] ?? $_GET['username'] ?? '';
            $password = $_POST['password'] ?? $_GET['password'] ?? '';

            if (empty($database) || empty($username)) {
                $this->result['errorCode'] = "validation";
                $this->result['error'] = "Database name and username are required";
                return $this->returnResult();
            }

            // Test database connection
            $testResult = $this->testDatabaseConnection($host, $port, $database, $username, $password);
            
            if ($testResult === true) {
                $this->result['data'] = [
                    'status' => 'success',
                    'message' => 'Database connection successful'
                ];
            } else {
                $this->result['errorCode'] = "database";
                $this->result['error'] = $testResult;
            }
        } catch (\Exception $e) {
            $this->result['errorCode'] = "exception";
            $this->result['error'] = "Database test failed: " . $e->getMessage();
        }

        return $this->returnResult();
    }

    /**
     * Save database configuration to .env file
     */
    public function saveDatabaseConfig()
    {
        try {
            $host = $_POST['host'] ?? $_GET['host'] ?? 'localhost';
            $port = $_POST['port'] ?? $_GET['port'] ?? '3306';
            $database = $_POST['database'] ?? $_GET['database'] ?? '';
            $username = $_POST['username'] ?? $_GET['username'] ?? '';
            $password = $_POST['password'] ?? $_GET['password'] ?? '';

            if (empty($database) || empty($username)) {
                $this->result['errorCode'] = "validation";
                $this->result['error'] = "Database name and username are required";
                return $this->returnResult();
            }

            // Test connection first
            $testResult = $this->testDatabaseConnection($host, $port, $database, $username, $password);
            if ($testResult !== true) {
                $this->result['errorCode'] = "database";
                $this->result['error'] = $testResult;
                return $this->returnResult();
            }

            // Update .env file
            $envUpdated = $this->updateEnvFile($host, $port, $database, $username, $password);
            
            if ($envUpdated) {
                $this->result['data'] = [
                    'status' => 'success',
                    'message' => 'Database configuration saved successfully'
                ];
            } else {
                $this->result['errorCode'] = "env";
                $this->result['error'] = "Failed to update .env file. Please check file permissions.";
            }
        } catch (\Exception $e) {
            $this->result['errorCode'] = "exception";
            $this->result['error'] = "Failed to save database config: " . $e->getMessage();
        }

        return $this->returnResult();
    }

    /**
     * Configure admin user during installation
     */
    public function configureAdmin()
    {
        try {
            $password = $_POST['password'] ?? $_GET['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? $_GET['confirm_password'] ?? '';

            if (empty($password)) {
                $this->result['errorCode'] = "validation";
                $this->result['error'] = "Password is required";
                return $this->returnResult();
            }

            if (strlen($password) < 8) {
                $this->result['errorCode'] = "validation";
                $this->result['error'] = "Password must be at least 8 characters long";
                return $this->returnResult();
            }

            if ($password !== $confirmPassword) {
                $this->result['errorCode'] = "validation";
                $this->result['error'] = "Passwords do not match";
                return $this->returnResult();
            }

            // Store admin hash in session for later use during installation
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['admin_hash'] = hash('sha256', $password);

            $this->result['data'] = [
                'status' => 'success',
                'message' => 'Admin configuration saved'
            ];
        } catch (\Exception $e) {
            $this->result['errorCode'] = "exception";
            $this->result['error'] = "Failed to configure admin: " . $e->getMessage();
        }

        return $this->returnResult();
    }

    /**
     * Install with admin configuration
     */
    public function installWithConfig()
    {
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $adminHash = $_SESSION['admin_hash'] ?? null;
            if (!$adminHash) {
                $this->result['errorCode'] = "validation";
                $this->result['error'] = "Admin configuration is required. Please go back and set admin password.";
                return $this->returnResult();
            }

            // Set up admin configuration for installer
            $_REQUEST['setupvars'] = json_encode(['adminhash' => $adminHash]);

            $dbUpdater = new DbUpdater();
            $result = $dbUpdater->install();
            
            if (strpos($result, 'Installation Completed!') !== false || 
                strpos($result, 'Database detected, skipping full installation.') !== false) {
                $this->result['data'] = $result;
                // Clear session data after successful installation
                unset($_SESSION['admin_hash']);
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
     * Test database connection
     */
    private function testDatabaseConnection($host, $port, $database, $username, $password)
    {
        try {
            $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
            $pdo = new \PDO($dsn, $username, $password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_TIMEOUT => 5
            ]);
            
            // Test if we can execute a simple query
            $stmt = $pdo->query("SELECT 1");
            $stmt->closeCursor();
            
            return true;
        } catch (\PDOException $e) {
            return "Database connection failed: " . $e->getMessage();
        } catch (\Exception $e) {
            return "Database test error: " . $e->getMessage();
        }
    }

    /**
     * Update .env file with database configuration
     */
    private function updateEnvFile($host, $port, $database, $username, $password)
    {
        try {
            $envPath = $this->basePath('.env');
            $envExamplePath = $this->basePath('.env.example');
            
            // If .env doesn't exist, copy from .env.example
            if (!file_exists($envPath) && file_exists($envExamplePath)) {
                copy($envExamplePath, $envPath);
            }
            
            if (!file_exists($envPath)) {
                // Create basic .env if it doesn't exist
                $envContent = "APP_NAME=FreePOS\n";
                $envContent .= "APP_ENV=production\n";
                $envContent .= "APP_KEY=\n";
                $envContent .= "APP_DEBUG=false\n";
                $envContent .= "APP_URL=http://localhost\n\n";
                $envContent .= "DB_CONNECTION=mysql\n";
                $envContent .= "DB_HOST=$host\n";
                $envContent .= "DB_PORT=$port\n";
                $envContent .= "DB_DATABASE=$database\n";
                $envContent .= "DB_USERNAME=$username\n";
                $envContent .= "DB_PASSWORD=$password\n";
                
                return file_put_contents($envPath, $envContent) !== false;
            } else {
                // Update existing .env file
                $envContent = file_get_contents($envPath);
                
                $dbConfig = [
                    'DB_HOST' => $host,
                    'DB_PORT' => $port,
                    'DB_DATABASE' => $database,
                    'DB_USERNAME' => $username,
                    'DB_PASSWORD' => $password
                ];
                
                foreach ($dbConfig as $key => $value) {
                    // Escape special characters in password
                    if ($key === 'DB_PASSWORD' && (strpos($value, ' ') !== false || strpos($value, '#') !== false || strpos($value, '"') !== false)) {
                        $value = '"' . addslashes($value) . '"';
                    }
                    
                    $pattern = "/^$key=.*$/m";
                    if (preg_match($pattern, $envContent)) {
                        $envContent = preg_replace($pattern, "$key=$value", $envContent);
                    } else {
                        $envContent .= "\n$key=$value";
                    }
                }
                
                return file_put_contents($envPath, $envContent) !== false;
            }
        } catch (\Exception $e) {
            error_log("Failed to update .env file: " . $e->getMessage());
            return false;
        }
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