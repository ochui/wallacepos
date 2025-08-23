<?php

/**
 * 
 * SocketIO is used to send data to the node.js socket.io (websocket) server
 * It uses ElephantIO library to send the data
 *
 */

namespace App\Communication;

use ElephantIO\Client as Client;
use ElephantIO\Engine\SocketIO\Version4X as Version4X;
use App\Controllers\Admin\AdminSettings;
use App\Controllers\Admin\AdminUtilities;

class SocketIO
{

    /**
     * @var ElephantIO\Client|null The elephant IO client
     */
    private $elephant = null;
    /**
     * @var string This hashkey provides authentication for php operations
     */
    private $hashkey = "supersecretkey";

    /**
     * Initialise the elephantIO object and set the hashkey
     */
    function __construct()
    {
        $conf = AdminSettings::getConfigFileValues(true);
        if (isset($conf->feedserver_key)) {
            $this->hashkey = $conf->feedserver_key;
        }

        $this->elephant = new Client(new Version4X($conf->feedserver_host . ':' . $conf->feedserver_port . '/?hashkey=' .  $this->hashkey));
    }

    /**
     * Sends session updates to the node.js feed server, optionally removing the corresponding session
     * @param $event
     * @param $data
     * @return bool
     */
    private function sendData($event, $data)
    {
        set_error_handler(function () { /* ignore warnings */
        }, E_WARNING);
        try {
            $this->elephant->connect();
            $this->elephant->emit($event, $data);
        } catch (\Exception $e) {
            restore_error_handler();
            return $e->getMessage();
        }
        restore_error_handler();
        return true;
    }

    /**
     * Sends session updates to the node.js feed server, optionally removing the corresponding session
     * @param $data
     * @param bool $remove
     * @return bool
     */
    public function sendSessionData($data, $remove = false)
    {

        return $this->sendData('session', ['hashkey' => $this->hashkey, 'data' => $data, 'remove' => $remove]);
    }

    /**
     * Generate a random hashkey for php -> node.js authentication
     * @return bool
     */
    public function generateHashKey()
    {
        $key = hash('sha256', AdminUtilities::getToken(256));
        AdminSettings::setConfigFileValue('feedserver_key', $key);

        $socket = new SocketControl();
        if ($socket->isServerRunning())
            $this->sendData('hashkey', ['hashkey' => $this->hashkey, 'newhashkey' => $key]);

        return;
    }

    /**
     * Send a reset request to all pos devices or the device specified
     * @param null $devices
     * @return bool
     */
    public function sendResetCommand($devices = null)
    {

        return $this->sendDataToDevices(['a' => 'reset'], $devices);
    }

    /**
     * Send data to the specified devices, if no devices specified then all receive it.
     * @param $data
     * @param null $devices
     * @return bool
     */
    private function sendDataToDevices($data, $devices = null)
    {
        // sends message to all authenticated devices
        return $this->sendData('send', ['hashkey' => $this->hashkey, 'include' => $devices, 'data' => $data]);
    }

    /**
     * Send a message to the specified devices, if no devices specified then all receive it. Admin dash excluded
     * @param $devices
     * @param $message
     * @return bool
     */
    public function sendMessageToDevices($devices, $message)
    {
        // send message to specified devices
        return $this->sendDataToDevices(['a' => 'msg', 'data' => $message], $devices);
    }

    /**
     * Broadcast a stored item addition/update/delete to all connected devices.
     * @param $item
     * @return bool
     */
    public function sendItemUpdate($item)
    {
        // item updates get sent to all authenticated clients
        return $this->sendDataToDevices(['a' => 'item', 'data' => $item], null);
    }

    /**
     * Broadcast a customer addition/update/delete to all connected devices.
     * @param $customer
     * @return bool
     */
    public function sendCustomerUpdate($customer)
    {

        return $this->sendDataToDevices(['a' => 'customer', 'data' => $customer], null);
    }

    /**
     * Send a sale update to the specified devices, if no devices specified, all receive.
     * @param $sale
     * @param null $devices
     * @return bool
     */
    public function sendSaleUpdate($sale, $devices = null)
    { // device that the record was updated on

        return $this->sendDataToDevices(['a' => 'sale', 'data' => $sale], $devices);
    }

    /**
     * Broadcast a configuration update to all connected devices.
     * @param $newconfig
     * @param $configset; the set name for the values
     * @return bool
     */
    public function sendConfigUpdate($newconfig, $configset)
    {
        return $this->sendDataToDevices(['a' => 'config', 'type' => $configset, 'data' => $newconfig], null);
    }

    /**
     * Send updated device specific config
     * @param $newconfig
     * @return bool
     */
    public function sendDeviceConfigUpdate($newconfig)
    {
        return $this->sendDataToDevices(['a' => 'config', 'type' => 'deviceconfig', 'data' => $newconfig], null);
    }
}
