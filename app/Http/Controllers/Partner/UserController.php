<?php

namespace App\Http\Controllers\Partner;

use App\Exceptions\NotFoundError;
use App\Http\Controllers\Controller;
use App\Http\Services\LogService;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function show($id)
    {
        $user = User::find($id);

        if (!$user) {
            throw new NotFoundError('User tidak ditemukan');
        }

        LogService::create("User melihat detail user dengan id $id");

        return response()->json([
            "code" => 200,
            "status" => "success",
            "data" => $user
        ]);
    }
}
