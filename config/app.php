<?php


return [
    'timezone' => getenv('TIMEZONE') ?: 'UTC',
    'feedserver_host' => getenv('FEED_SERVER_HOST') ?: '127.0.0.1',
    'feedserver_port' => getenv('FEED_SERVER_PORT') ?: 3000,
    'feedserver_key' => getenv('FEED_SERVER_KEY') ?: 'aaaBBBcccDDDeeeFF1234566',
];
