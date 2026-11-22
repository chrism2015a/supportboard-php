<?php

return [
    'secret' => env('JWT_SECRET'),
    'ttl' => 60,
    'refresh_ttl' => 20160,
    'auth' => 'Tymon\JWTAuth\Providers\Auth\IlluminateAuthAdapter',
    'users' => 'Tymon\JWTAuth\Providers\User\EloquentUserAdapter',
    'storage' => 'Tymon\JWTAuth\Providers\Storage\IlluminateCacheAdapter',
];
