<?php

namespace App\Http\Services;

use App\Models\Log;
use Illuminate\Support\Facades\Auth;

class LogService
{
    public static function create($description)
    {
        $user = Auth::user();

        Log::create([
            "user_id" => $user->id,
            "name" => $user->name,
            "description" => $description,
        ]);
    }
}
