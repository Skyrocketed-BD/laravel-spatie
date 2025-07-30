<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ProdukController extends Controller
{
    public function index()
    {
        // dd(auth()->user()->getAllPermissions());
        
        return Response::json([
            'message' => 'this is index'
        ]);
    }

    public function show($id)
    {
        return Response::json([
            'message' => 'this is show'
        ]);
    }

    public function store(Request $request)
    {
        return Response::json([
            'message' => 'this is store'
        ]);
    }

    public function update(Request $request, $id)
    {
        return Response::json([
            'message' => 'this is update'
        ]);
    }

    public function destroy($id)
    {
        return Response::json([
            'message' => 'this is destroy'
        ]);
    }
}
