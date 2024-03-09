<?php

namespace App\Http\Controllers;

use App\Exceptions\AuthenticationError;
use App\Exceptions\InvariantError;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required',
            'phone' => 'required|unique:users,phone',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'phone' => $request->phone,
        ]);

        $user->assignRole("USER");

        return response()->json([
            "code" => 201,
            "status" => "success",
            "message" => "Registrasi berhasil"
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            throw new InvariantError($validator->errors());
        }

        if (!Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            throw new AuthenticationError("Email atau Password salah");
        }

        $token = $request->user()->createToken("API_TOKEN", ['*'], now()->addHours(12));
        $expireTime = $token->accessToken->expires_at->toDateTimeString();

        return response()->json([
            "code" => 200,
            "status" => "success",
            "data" => [
                "token" => $token->plainTextToken,
                "expires_at" => $expireTime,
            ]
        ]);
    }
}
