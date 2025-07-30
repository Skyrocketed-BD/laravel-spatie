<?php

namespace App\Http;

use Illuminate\Foundation\Configuration\Middleware;

class AppMiddleware
{
    public function __invoke(Middleware $middleware)
    {
        $middleware->alias([
            'jwtChecking' => \App\Http\Middleware\JwtChecking::class,
            'jsonApiData' => \App\Http\Middleware\JsonApiData::class,

            'role'               => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'         => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

        $middleware->append([
        ]);
    }
}
