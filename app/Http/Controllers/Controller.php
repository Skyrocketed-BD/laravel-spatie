<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

/**
 * @OA\Info(
 *    title="OpenAPI For Skyrocketed",
 *    version="1.0.0",
 * )
 * @OA\SecurityScheme(
 *     type="http",
 *     securityScheme="bearerAuth",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 * @OA\Server(
 *     url="http://localhost:8000/api",
 *     description="local server"
 * )
 * @OA\Server(
 *     url="http://192.168.1.11:8000/api",
 *     description="staging server"
 * )
 * @OA\Server(
 *     url="https://api.fiscahutama.com/api",
 *     description="production server"
 * )
 */

abstract class Controller
{
    public $user;

    public function __construct()
    {
        $this->user = Auth::user();
    }
}
