<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Response;

class PermissionsController extends Controller
{
    public function index()
    {
        // dd(auth()->user(), auth()->user()->getRoleNames());
        return Response::json(auth()->user()->getAllPermissions());
    }
}
