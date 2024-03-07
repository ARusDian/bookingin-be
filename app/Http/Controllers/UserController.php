<?php

namespace App\Http\Controllers;

use App\Exceptions\AuthenticationError;
use App\Exceptions\InvariantError;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function register(Request $request)
    {

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

        if ($request->user()->expired_date < now()) {
            throw new AuthenticationError("Akun anda tidak aktif");
        }

        $token = $request->user()->createToken("API_TOKEN")->plainTextToken;

        return response()->json([
            "status" => "success",
            "data" => [
                "is_new" => $request->user()->is_new,
                "token" => $token
            ]
        ]);
    }
}
