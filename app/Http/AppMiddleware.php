<?php

namespace App\Http;

use App\Http\Middleware\LoggingContext;
use Illuminate\Foundation\Configuration\Middleware;

class AppMiddleware
{
    public function __invoke(Middleware $middleware)
    {
        $middleware->alias([
            'jwtChecking'              => \App\Http\Middleware\JwtChecking::class,
            'jsonApiData'              => \App\Http\Middleware\JsonApiData::class,
            'checkClosingYear'         => \App\Http\Middleware\CheckClosingYear::class,
            'CheckPreferenceKey'       => \App\Http\Middleware\CheckPreferenceKey::class,
            'CheckCoaPreference'       => \App\Http\Middleware\CheckCoaPreference::class,
        ]);

        $middleware->append([
            LoggingContext::class,
            \App\Http\Middleware\CorsMiddleware::class,
        ]);
    }
}
