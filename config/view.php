<?php

use Illuminate\Support\Str;

return [

    'paths' => [
        resource_path('views'),
    ],

    'compiled' => env('VIEW_COMPILED_PATH', '/var/www/html/storage/framework/views'),

    'realpath' => true,

    'fresh' => false,

    'compact' => false,

    'namespace' => null,

    'relative_hash' => false,

    'cache' => true,

    'cache_lifetime' => null,

    'cache_store' => null,

    'cache_component' => null,

    'cache_ignore_patterns' => [],

    'cache_ignore_directories' => [],

];
