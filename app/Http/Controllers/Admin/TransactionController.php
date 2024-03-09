<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function get(Request $request)
    {
        $request->validate([
            "page" => "nullable|integer|min:1",
            "item" => "nullable|integer|min:1",
            "search" => "nullable|string",
            "role" => "nullable|string|in:partner,user",
        ]);

        $page = $request->input("page", 1);
        $item = $request->input("item", 10);

        $transactions = Transaction::query();

        if ($request->has("search")) {
            $transactions->where(function ($query) use ($request) {
                $query->where("id", "LIKE", "%{$request->input("search")}%")
                    ->orWhere("user_id", "LIKE", "%{$request->input("search")}%");
            });
        }

        if ($request->has("role")) {
            $transactions->whereHas("user.roles", function ($query) use ($request) {
                $query->where("name", $request->role);
            });
        }

        $data = $transactions->paginate($item, ["*"], "page", $page);

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
