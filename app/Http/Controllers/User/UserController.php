<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function getProfile()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $user = array_merge($user->toArray(), [
            "role" => $user->getRoleNames()[0]
        ]);

        return response()->json([
            "code" => 200,
            "status" => "success",
            "data" => $user
        ]);
    }
}
