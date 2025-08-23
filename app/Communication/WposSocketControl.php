<?php

/**
 *
 * SocketControl is used to control the node.js websocket server
 *
 */

namespace App\Communication;

class SocketControl
{

    private $isWindows = false;

    /**
     * SocketControl constructor.
     */
    function __construct()
    {
        $this->isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    /**
     * Start the socket server
     * @param $result array Current result array
     * @return mixed API result array
     */
    public function startSocketServer($result = ['error' => 'OK'])
    {
        $basePath = base_path();
        $serverScript = $basePath . 'api/server.js';

        if ($this->isWindows) {
            pclose(popen('START "WPOS" node ' . $serverScript, 'r'));
        } else {
            $args = $serverScript . " > /dev/null &";
            exec("nodejs " . $args, $output, $res);
            // try the alternative command if nodejs fails
            if ($res > 0)
                exec("node " . $args, $output, $res);
        }
        sleep(1); // Wait a bit to see if nodejs exits
        if ($this->getServerStat() === false) {
            $result['error'] = "Failed to start the feed server! " . (isset($output) ? json_encode($output) : '');
        }
        return $result;
    }

    /**
     * Stop the socket server
     * @param $result array Current result array
     * @return mixed API result array
     */
    public function stopSocketServer($result = ['error' => 'OK'])
    {
        if ($this->isWindows) {
            exec('TASKKILL /F /FI "WindowTitle eq POS"', $output);
        } else {
            exec('kill `ps aux | grep "[n]odejs ' . $_SERVER['DOCUMENT_ROOT'] . '" | awk \'{print $2}\'`', $output);
        }
        if ($this->getServerStat() === true) {
            $result['error'] = "Failed to stop the feed server! " . (isset($output) ? json_encode($output) : '');
        }
        return $result;
    }

    /**
     * Checks if the server is currently running
     * @param $result array Current result array
     * @return mixed API result array
     */
    public function isServerRunning($result = ['error' => 'OK'])
    {
        $result['data'] = ["status" => $this->getServerStat()];
        return $result;
    }

    /**
     * Restart the server
     * @param $result array Current result array
     * @return mixed API result array
     */
    public function restartSocketServer($result = ['error' => 'OK'])
    {
        $result['data'] = true; // server currently running
        $result = $this->stopSocketServer($result);
        if ($result['error'] == "OK") { // successfully stopped server
            $result = $this->startSocketServer($result);
            if ($result['error'] !== "OK") {
                $result['data'] = false;
            }
        }
        return $result;
    }

    /**
     * Checks if the server is running
     * @return bool
     */
    private function getServerStat()
    {
        if ($this->isWindows) {
            exec('TASKLIST /NH /V /FI "WindowTitle eq POS"', $output);
            if (strpos($output[0], 'INFO') !== false) {
                $output[0] = 'Offline';
                return false;
            } else {
                $output[0] = 'Online';
                return true;
            }
        } else {
            exec('ps aux | grep -E "[n]ode(js)? ' . $_SERVER['DOCUMENT_ROOT'] . '"', $output);
            if (strpos($output[0], $_SERVER['DOCUMENT_ROOT']) !== false) {
                return true;
            } else {
                return false;
            }
        }
    }
}
