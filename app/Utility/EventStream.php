<?php

namespace App\Utility;

/**
 *
 * EventStream is used to output progress/result in HTML5 EventSource format
 *
 */
class EventStream
{
    public static function iniStream()
    {
        header('Connection: keep-alive');
        header('Cache-Control: no-cache');
        header("Content-Type: text/event-stream\n\n");
        # Set this so PHP doesn't timeout during a long stream
        set_time_limit(0);
        # Disable Apache and PHP's compression of output to the client
        apache_setenv('no-gzip', 1);
        ini_set('zlib.output_compression', 0);
        # Set implicit flush, and flush all current buffers
        ini_set('implicit_flush', 1);
        for ($i = 0; $i < ob_get_level(); $i++)
            ob_end_flush();
        ob_implicit_flush(1);
    }
    public static function sendStreamData($data)
    {
        // echo eventsource event object, followed by 2x\n to cause browser to fire event
        $data['output'] = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\x9F](\[1G)?/u', '', $data['output']); // replace control codes in terminal output
        echo ('data: ' . json_encode($data) . "\n\n");
    }
}
