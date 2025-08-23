<?php

/**
 *
 * Logger is a simple, static logging class. That is all.
 *
 */

namespace App\Utility;

use App\Auth;


class Logger
{

    /**
     * @var string the directory to store logs relative to project root (doc+app root).
     */
    private static $directory = "storage/logs";

    /**
     * Log an event into the log file
     * @param $msg
     * @param string $type
     * @param null $data
     */
    public static function write($msg, $type = "Misc", $data = null, $showUser = true)
    {
        $logDir = storage_path('logs');
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        if ($showUser) {
            if (php_sapi_name() === 'cli') {
                $user = "system:cli";
            } else {
                $auth = new Auth();
                $user = $auth->isLoggedIn() ? $auth->getUserId() . ":" . $auth->getUsername() : ($auth->isCustomerLoggedIn() ? $auth->getCustomerId() . ":" . $auth->getCustomerUsername() : 'system');
            }
        }
        $fd = fopen($logDir . DIRECTORY_SEPARATOR . "wpos_log_" . date("y-m-d") . ".txt", "a");
        if ($fd === false) {
            return;
        }
        fwrite($fd, "[" . date("y-m-d H:i:s") . "] (" . $type . (isset($user) ? ' - ' . $user . ') ' : ') ') . $msg . ($data != null ? "\nData: " . $data : "") . "\n");
        fclose($fd);
    }

    /**
     * Read a log file using it's filename
     * @param $filename
     * @return string
     */
    public static function read($filename)
    {
        return file_get_contents(storage_path('logs' . DIRECTORY_SEPARATOR . $filename));
    }

    /**
     * List the contents of the log dir
     * @return array
     */
    public static function ls()
    {
        $logDir = storage_path('logs');
        $dir = scandir($logDir);
        unset($dir[0]);
        unset($dir[1]);
        return $dir;
    }
}
