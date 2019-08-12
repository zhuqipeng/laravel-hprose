<?php

return [
    'tcp_uris' => (function () {
        $uris = [];
        for ($i = 1; $i <= 10; $i++) {
            $uri = env(sprintf('HPROSE_TCP_URI%s', $i));
            $uri and array_push($uris, $uri);
        }
        return $uris;
    })(),

    'http_uri' => env('HPROSE_HTTP_URI', 'http://0.0.0.0:8086'),

    'enable_servers' => array_keys(array_filter([
        'hprose.socket_server' => env('ENABLE_SOCKET_SERVER', true),
        'hprose.swoole_http_server' => env('ENABLE_SWOOLE_HTTP_SERVER', true),
    ], function ($v, $k) {
        return $v;
    }, ARRAY_FILTER_USE_BOTH)),

    /**
     * true开启 false关闭，开启后将自动对外发布一个远程调用方法 `demo`
     * $client->demo()
     */
    'demo' => env('HPROSE_DEMO'),

    'parameter' => 'App\\Controllers\\Parameters',

    'controller' => 'App\\Controllers',
];