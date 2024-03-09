<?php

namespace App\Http\Controllers;

use App\Exceptions\NotFoundError;
use App\Models\Hotel\Hotel;
use App\Models\Hotel\Room;
use Illuminate\Http\Request;

class HotelController extends Controller
{
    public function getHotel(Request $request)
    {
        $request->validate([
            "page" => "nullable|integer|min:1",
            "item" => "nullable|integer|min:1",
            "search" => "nullable|string",
        ]);

        $page = $request->input("page", 1);
        $item = $request->input("item", 10);

        $hotels = Hotel::query();

        if ($request->has("search")) {
            $hotels->where("name", "LIKE", "%{$request->input("search")}%");
        }

        $data = $hotels->paginate($item, ["*"], "page", $page);

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

    public function showHotel($id)
    {
        $hotel = Hotel::with('rooms.type')->find($id);

        if (!$hotel) {
            throw new NotFoundError("Hotel not found");
        }

        return response()->json([
            "code" => 200,
            "status" => "success",
            "data" => $hotel
        ]);
    }

    public function showHotelRoom($id, $roomId)
    {
        $room = Room::with('hotel', 'type', 'reservations')->find($roomId);

        if (!$room) {
            throw new NotFoundError("Room not found");
        }

        return response()->json([
            "code" => 200,
            "status" => "success",
            "data" => $room
        ]);
    }
}
