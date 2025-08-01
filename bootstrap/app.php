<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Exceptions\UnauthorizedException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware(['api'])->group(__DIR__ . '/../routes/api/main.php');
            Route::middleware(['api'])->group(__DIR__ . '/../routes/api/finance.php');
            Route::middleware(['api'])->group(__DIR__ . '/../routes/api/operation.php');
        }
    )
    ->withMiddleware(new App\Http\AppMiddleware())
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Unauthorized',
                    'error'   => ['error' => $e->getMessage()],
                ], 401);
            }
        });

        $exceptions->renderable(function (UnauthorizedException $e, Request $request) {
            if ($request->is('api/*')) {
                // return response()->json([
                //     'status'  => false,
                //     'message' => 'Unauthorized',
                //     'error'   => ['error' => $e->getMessage()],
                // ], 401);
                return response()->json([
                    'status'  => false,
                    'errors'   => 'Unauthorized',
                    'message' => $e->getMessage(),
                ], 401);
            }
        });

        $exceptions->renderable(function (AuthorizationException $e, Request $request) {
            // if ($request->is('api/*')) {
            //     return response()->json([
            //         'status'  => false,
            //         'message' => 'Unauthorized',
            //         'error'   => ['error' => $e->getMessage()],
            //     ], 401);
            // }
            if ($request->is('api/*')) {
                return response()->json([
                    'status'  => false,
                    'errors'   => 'Unauthorized',
                    'message' => $e->getMessage(),
                ], 401);
            }
        });
    })->create();
