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