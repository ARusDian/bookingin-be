<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Log;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function get(Request $request)
    {
        $request->validate([
            "page" => "nullable|integer|min:1",
            "item" => "nullable|integer|min:1",
            "search" => "nullable|string",
            "user_id" => "nullable|integer",
        ]);

        $page = $request->input("page", 1);
        $item = $request->input("item", 10);

        $logs = Log::query();

        if ($request->has("search")) {
            $logs->where(function ($query) use ($request) {
                $query->where("user_id", "LIKE", "%{$request->input("search")}%")
                    ->orWhere("name", "LIKE", "%{$request->input("search")}%")
                    ->orWhere("description", "LIKE", "%{$request->input("search")}%");
            });
        }

        if ($request->has("user_id")) {
            $logs->where("user_id", $request->input("user_id"));
        }

        $data = $logs->paginate($item, ["*"], "page", $page);

        return response()->json([
            "code" => 200,
            "status" => "success",
            "data" => $data->items(),
            "meta" => [
                "currentPage" => $page,
                "item" => $item,
                "totalItems" => $data->total(),
                "totalPages" => $data->lastPage(),
            ],
        ]);
    }
}
