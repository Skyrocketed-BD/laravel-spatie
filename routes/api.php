<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PermissionsController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\MenuCategoryController;
use App\Http\Controllers\MenuBodyController;
use App\Http\Controllers\MenuChildController;
use App\Http\Controllers\RoleController;

Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'
], function ($router) {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api')->name('logout');
    Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('auth:api')->name('refresh');
    Route::post('/me', [AuthController::class, 'me'])->middleware('auth:api')->name('me');
});

Route::group([
    'middleware' => ['jwtChecking', 'jsonApiData'],
], function ($router) {
    Route::prefix('menu')->group(function () {
        // begin:: menu category
        Route::prefix('categories')->group(function () {
            Route::get('/{id_menu_module}', [MenuCategoryController::class, 'index']);
            Route::post('/', [MenuCategoryController::class, 'store']);
            Route::get('/{id_menu_module}/{id_menu_category}', [MenuCategoryController::class, 'show']);
            Route::put('/{id_menu_module}/{id_menu_category}', [MenuCategoryController::class, 'update']);
            Route::delete('/{id_menu_module}/{id_menu_category}', [MenuCategoryController::class, 'destroy']);
        });
        // end:: menu category

        // begin:: menu body
        Route::prefix('bodies')->group(function () {
            Route::get('/{id_menu_category}', [MenuBodyController::class, 'index']);
            Route::post('/', [MenuBodyController::class, 'store']);
            Route::get('/{id_menu_category}/{id_menu_body}', [MenuBodyController::class, 'show']);
            Route::put('/{id_menu_category}/{id_menu_body}', [MenuBodyController::class, 'update']);
            Route::delete('/{id_menu_category}/{id_menu_body}', [MenuBodyController::class, 'destroy']);
            Route::post('/active/{id_menu_category}/{id_menu_body}', [MenuBodyController::class, 'active']);
        });
        // end:: menu body

        // begin:: menu child
        Route::prefix('child')->group(function () {
            Route::get('/{id_menu_body}', [MenuChildController::class, 'index']);
            Route::post('/', [MenuChildController::class, 'store']);
            Route::get('/{id_menu_body}/{id_menu_child}', [MenuChildController::class, 'show']);
            Route::put('/{id_menu_body}/{id_menu_child}', [MenuChildController::class, 'update']);
            Route::delete('/{id_menu_body}/{id_menu_child}', [MenuChildController::class, 'destroy']);
        });
        // end:: menu child
    });

    Route::controller(PermissionsController::class)->prefix('permissions')->group(function () {
        Route::get('/', 'index')->middleware('permission:list-produk');
    });

    Route::prefix('roles')->group(function () {
        Route::get('/access/{id_role}/{id_menu_module}', [RoleController::class, 'access']);
    });

    Route::prefix('role-accesses')->group(function () {
        Route::post('/action/{id_role_access}', [RoleAccessController::class, 'action']);
        Route::get('/trees/{id_role}/{id_menu_module}', [RoleAccessController::class, 'trees']);
    });

    Route::controller(ProdukController::class)->prefix('produk')->group(function () {
        Route::get('/', 'index')->middleware('permission:list-produk');
        Route::get('/{id}', 'show')->middleware('permission:show-produk');
        Route::post('/', 'store')->middleware('permission:create-produk');
        Route::put('/{id}', 'update')->middleware('permission:update-produk');
        Route::delete('/{id}', 'destroy')->middleware('permission:delete-produk');
    });
});
