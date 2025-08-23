<?php

/**
 *
 * AdminSettings is used to retrieve and update the system configuration sets
 *
 */

namespace App\Controllers\Admin;

use App\Communication\SocketIO;
use App\Database\ConfigModel;
use App\Integration\GoogleIntegration;
use App\Utility\Logger;

class AdminSettings
{
    /**
     * @var stdClass provided data (updated config)
     */
    private $data;
    /**
     * @var String the name of the current configuration
     */
    private $name;
    /**
     * @var ConfigModel the config DB object
     */
    private $configMdl;
    /**
     * @var stdClass the current configuration
     */
    private $curconfig;

    /**
     * Init object with any provided data
     * @param null $data
     */
    function __construct($data = null)
    {
        if ($data !== null) {
            // parse the data and put it into an object
            $this->data = $data;
            $this->name = (isset($this->data->name) ? $this->data->name : null);
            unset($this->data->name);
        } else {
            $this->data = new \stdClass();
        }
    }

    /**
     * Set the name of the configuration set to update.
     * @param $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get a config set by name
     * @param $name
     * @return bool|mixed
     */
    public static function getSettingsObject($name)
    {
        $configMdl = new ConfigModel();
        $data = $configMdl->get($name);
        if ($data === false) {
            return false;
        }

        if (!$result = json_decode($data[0]['data'])) {
            return false;
        }

        return $result;
    }

    /**
     * Put a setting value, using section name, key, value
     * @param $name
     * @param $key
     * @param $value
     * @return bool|mixed
     */
    public static function putValue($name, $key, $value)
    {
        $configMdl = new ConfigModel();
        $data = $configMdl->get($name);
        if ($data === false) {
            return false;
        }
        if (!$result = json_decode($data[0]['data'])) {
            return false;
        }

        $result->{$key} = $value;

        if ($configMdl->edit($name, json_encode($result)) === false) {
            return false;
        }

        return true;
    }

    /**
     * Get all config values as an array
     * @return bool|mixed
     */
    public function getAllSettings($getServersideValues = false)
    {
        $this->configMdl = new ConfigModel();
        $data = $this->configMdl->get();
        if ($data === false) {
            return false;
        }
        $settings = [];
        foreach ($data as $setting) {
            $settings[$setting['name']] = json_decode($setting['data']);
            if ($setting['name'] == "general") {
                $settings["general"]->gcontactaval = $settings["general"]->gcontacttoken != '';
                unset($settings["general"]->gcontacttoken);
                // get file-stored config values
                $filesettings = $this->getConfigFileValues($getServersideValues);
                $settings["general"] = (object) array_merge((array) $settings["general"], (array) $filesettings);
            } elseif ($setting['name'] == "accounting") {
                $settings[$setting['name']]->xeroaval = $settings[$setting['name']]->xerotoken != '';
                unset($settings[$setting['name']]->xerotoken);
            }
        }

        return $settings;
    }

    /**
     * API method to retrieve a config.
     * @param $result
     * @return mixed
     */
    public function getSettings($result)
    {
        if (!isset($this->name)) {
            $result['error'] = "A config-set name must be supplied";
            return $result;
        }
        $this->configMdl = new ConfigModel();
        $data = $this->configMdl->get($this->name);
        if ($data !== false) {
            $data = json_decode($data[0]['data']);
            if ($this->name == "general") {
                $fileconfig = $this->getConfigFileValues(true);
                $data = (object) array_merge((array) $data, (array) $fileconfig);
                $data->gcontactaval = $data->gcontacttoken != '';
                unset($data->gcontacttoken);
            } else if ($this->name == "accounting") {
                // check xero token expiry TODO: Remove when we become xero partner
                if ($data->xerotoken != '' && $data->xerotoken->expiredt < time()) {
                    $data->xerotoken = '';
                }
                $data->xeroaval = $data->xerotoken != '';
                unset($data->xerotoken);
            }
            $result['data'] = $data;
        } else {
            $result['error'] = "Could not retrive the selected config record: " . $this->configMdl->errorInfo;
        }

        return $result;
    }

    /**
     * Update and save the current configuration
     * @param $result
     * @return mixed
     */
    public function saveSettings($result)
    {
        if (!isset($this->name)) {
            $result['error'] = "A config-set name must be supplied";
            return $result;
        }
        $this->configMdl = new ConfigModel();
        $config = $this->configMdl->get($this->name);
        if ($config !== false) {
            if (sizeof($config) > 0) {

                $this->curconfig = json_decode($config[0]['data']); // get the json object
                $configbk = $this->curconfig;

                if (isset($this->data->gcontactcode) && $this->data->gcontactcode != '') {
                    // Get google access token
                    $tokens = GoogleIntegration::processGoogleAuthCode($this->data->gcontactcode);
                    if ($tokens) {
                        $tokens = json_decode($tokens);
                        $this->data->gcontacttoken = $tokens;
                        $this->data->gcontact = 1;
                    }
                    unset($this->data->gcontactcode);
                }

                // generate new qr code
                if ($this->name == "pos") {
                    if ($this->data->recqrcode !== $configbk->recqrcode && $this->data->recqrcode != "") {
                        $this->generateQRCode();
                    }
                    $this->curconfig->{"negative_items"} = false;
                }

                foreach ($this->curconfig as $key => $value) {
                    if (isset($this->data->{$key})) { // update the config value if specified in the data
                        $this->curconfig->{$key} = $this->data->{$key};
                    }
                }

                if ($this->configMdl->edit($this->name, json_encode($this->curconfig)) === false) {
                    $result['error'] = "Could not update config record: " . $this->configMdl->errorInfo;
                } else {

                    $conf = $this->curconfig;
                    if ($this->name == "general") {
                        unset($conf->gcontacttoken);
                        // update Laravel-style config values (no longer using file config)
                        // Set runtime config values for broadcasting to POS terminals
                        if (isset($this->data->timezone)) {
                            $conf->timezone = $this->data->timezone;
                        }
                        if (isset($this->data->feedserver_port)) {
                            $conf->feedserver_port = $this->data->feedserver_port;
                        }
                        if (isset($this->data->feedserver_proxy)) {
                            $conf->feedserver_proxy = $this->data->feedserver_proxy;
                        }
                    } else if ($this->name == "accounting") {
                        unset($conf->xerotoken);
                    }

                    // send config update to POS terminals
                    $socket = new SocketIO();
                    $socket->sendConfigUpdate($conf, $this->name);

                    // Success; log data
                    Logger::write("System configuration updated:" . $this->name, "CONFIG", json_encode($conf));

                    // Return config including server side value; remove sensitive tokens
                    if ($this->name == "general") {
                        unset($this->curconfig);
                    } else if ($this->name == "accounting") {
                        unset($this->curconfig);
                    }

                    $result['data'] = $conf;
                }
            } else {
                // if current settings are null, create a new record with the specified name
                if ($this->configMdl->create($this->name, json_encode($this->data)) === false) {
                    $result['error'] = "Could not insert new config record: " . $this->configMdl->errorInfo;
                }
            }
        } else {
            $result['error'] = "Could not retrieve the selected config record: " . $this->configMdl->errorInfo;
        }

        return $result;
    }

    /**
     * Get general config values using Laravel-style configuration
     * @param bool $includeServerside
     * @return mixed|stdClass
     */
    public static function getConfigFileValues($includeServerside = false)
    {
        $config = new \stdClass();

        // Use Laravel-style config
        $config->timezone = config('app.timezone', 'UTC');
        $config->feedserver_host = config('app.feedserver_host', '127.0.0.1');
        $config->feedserver_port = config('app.feedserver_port', 3000);

        if ($includeServerside) {
            $config->email_host = config('app.email.host', '');
            $config->email_port = config('app.email.port', 587);
            $config->email_tls = config('app.email.encryption', 'tls');
            $config->email_user = config('app.email.username', '');
            $config->email_pass = config('app.email.password', '');
            $config->feedserver_key = config('app.feedserver_key', '');
        }

        return $config;
    }

    /**
     * Updates config values using Laravel-style configuration
     * @param $key
     * @param $value
     * @return bool
     */
    public static function setConfigFileValue($key, $value)
    {
        // Store in runtime config for this request
        // Configuration should be managed through .env variables and config files

        $configKey = "app.{$key}";
        if (class_exists('\App\Core\Config')) {
            \App\Core\Config::set($configKey, $value);
            return true;
        }

        return false;
    }

    /**
     * Generate a QR code, executed if qrcode text has changed
     */
    public function generateQRCode()
    {
        //echo("Creating QR code");
        $qrCode = new \Endroid\QrCode\QrCode($this->data->recqrcode);
        $qrCode->setSize(300);
        $qrCode->setErrorCorrectionLevel(\Endroid\QrCode\ErrorCorrectionLevel::Low);
        $writer = new \Endroid\QrCode\Writer\PngWriter();
        $result = $writer->write($qrCode);

        $qrPath = asset_path('qrcode.png');
        file_put_contents($qrPath, $result->getString());
    }
}
