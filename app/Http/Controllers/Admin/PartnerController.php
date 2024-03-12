<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\InvariantError;
use App\Exceptions\NotFoundError;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    public function show($id)
    {
        $partner = User::with('hotels', 'airlines')->find($id);

        if (!$partner) {
            throw new NotFoundError("Partner tidak ditemukan");
        }

        if ($partner->getRoleNames()->first() !== "PARTNER") {
            throw new InvariantError("User bukan partner");
        }

        return response()->json([
            "code" => 200,
            "status" => "success",
            "data" => $partner,
        ]);
    }
}
