<?php

namespace App\Http\Controllers;

use App\Exceptions\NotFoundError;
use App\Http\Services\LogService;
use App\Models\Hotel\Hotel;
use App\Models\Hotel\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        if (Auth::check()) {
            LogService::create("User melakukan pencarian hotel");
        }

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

        if (Auth::check()) {
            LogService::create("User melihat hotel dengan ids $id");
        }

        return response()->json([
            "code" => 200,
            "status" => "success",
            "data" => $hotel
        ]);
    }

    public function showHotelRoom($id, $roomId)
    {
        $room = Room::with('hotel', 'type.facilities', 'reservations')->find($roomId);

        if (!$room) {
            throw new NotFoundError("Room not found");
        }

        if (Auth::check()) {
            LogService::create("User melihat ruangan hotel dengan ids $roomId");
        }

        return response()->json([
            "code" => 200,
            "status" => "success",
            "data" => $room
        ]);
    }
}
