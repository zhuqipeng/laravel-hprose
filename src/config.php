<?php

return [
    /**
     * 监听地址列表
     * 字符串json格式数组
     */
    'uris' => json_decode(env('HPROSE_URIS', '["tcp://0.0.0.0:1314"]')),

    /**
     * true开启 false关闭，开启后将自动对外发布一个远程调用方法 `demo`
     * $client->demo()
     */
    'demo' => env('HPROSE_DEMO'),

    'parameter' => 'App\\Controllers\\Parameters',

    'controller' => 'App\\Controllers',
];