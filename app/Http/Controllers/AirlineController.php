<?php

namespace App\Http\Controllers;

use App\Exceptions\NotFoundError;
use App\Models\Airline\Airline;
use Illuminate\Http\Request;

class AirlineController extends Controller
{
    public function get(Request $request)
    {
        $request->validate([
            "page" => "nullable|integer|min:1",
            "item" => "nullable|integer|min:1",
            "search" => "nullable|string",
        ]);

        $page = $request->input("page", 1);
        $item = $request->input("item", 10);

        $airlines = Airline::query();

        if ($request->has("search")) {
            $airlines->where("name", "LIKE", "%{$request->input("search")}%");
        }

        $data = $airlines->paginate($item, ["*"], "page", $page);

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

    public function show($id)
    {
        $airline = Airline::find($id);

        if (!$airline) {
            throw new NotFoundError("Airline not found");
        }

        return response()->json([
            "code" => 200,
            "status" => "success",
            "data" => $airline
        ]);
    }
}
