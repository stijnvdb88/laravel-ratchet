<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Laravel Ratchet Configuration
    |--------------------------------------------------------------------------
    |
    | Here you can define the default settings for Laravel Ratchet.
    |
    */

    'class'           => \Askedio\LaravelRatchet\Examples\Pusher::class,

    // use_routes: if set to true, this bypasses the above class and uses routes instead. example route:
    // WebSocketsRoute::webSocket('/test', App\TestWebSocketHandler::class);
    // the routed classes need to implement Ratchet\WebSocket\MessageComponentInterface (not compatible with extends RatchetWsServer at time of writing)
    'use_routes'      => false, 

    'host'            => '0.0.0.0', // Prepend tls:// to host address to enable SSL/TLS. Example: tls://0.0.0.0
    'port'            => '8080',
    'connectionLimit' => false,
    'throttle'        => [
        'onOpen'    => '5:1',
        'onMessage' => '20:1',
     ],
    'abortOnMessageThrottle' => false,
    'blackList'              => [],
    'zmq'                    => [
        'host'   => '127.0.0.1',
        'port'   => 5555,
        'method' => \ZMQ::SOCKET_PULL,
    ],
    /**
     * Look up http://php.net/manual/en/context.ssl.php to configure SSL/TLS.
     */
    'tls'             => [
        // 'peer_name' => '',
        // 'verify_peer' => true,
        // 'verify_peer_name' => true,
        // 'allow_self_signed' => false,
        'cafile' => env('SSL_CA_FILE', ''),
        'capath' => env('SSL_CA_PATH', ''),
        'local_cert' => env('SSL_PUBLIC_CERT', ''),
        'local_pk' => env('SSL_PRIVATE_KEY', ''),
        'passphrase' => env('SSL_PASSPHRASE', ''),
    ],
];
