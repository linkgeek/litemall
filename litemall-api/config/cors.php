<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    // 路径
    'paths' => ['wx/*'],
    // 允许的方法
    'allowed_methods' => ['*'],
    // 允许所有域名(h5)
    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],
    // 可允许请求头
    'allowed_headers' => ['*'],
    // 对响应头特殊配置
    'exposed_headers' => [],
    // 嗅探 0 每次嗅探 单位s
    'max_age' => 0,
    // 是否允许携带cookie
    'supports_credentials' => false,

];
