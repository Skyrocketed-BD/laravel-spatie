<?php

namespace App\Http\Controllers;
  
use App\Http\Controllers\Controller;
use App\Models\User;
use Validator;
  
  
class AuthController extends Controller
{
    public function register() {
        $validator = Validator::make(request()->all(), [
            'name'      => 'required',
            'email'     => 'required|email|unique:users',
            'password'  => 'required|confirmed|min:8',
        ]);
  
        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }
  
        $user = new User;
        $user->name = request()->name;
        $user->email = request()->email;
        $user->password = bcrypt(request()->password);
        $user->save();
  
        return response()->json($user, 201);
    }
  
    public function login()
    {
        $credentials = request(['email', 'password']);
  
        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
  
        return $this->respondWithToken($token);
    }
  
    public function me()
    {
        return response()->json(auth()->user());
    }
  
    public function logout()
    {
        auth()->logout();
  
        return response()->json(['message' => 'Successfully logged out']);
    }
  
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token'  => $token,
            'token_type'    => 'bearer',
            'expires_in'    => auth()->factory()->getTTL() * 60
        ]);
    }
}