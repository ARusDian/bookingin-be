<?php

namespace App\Http\Controllers\Admin\Partner;

use App\Exceptions\NotFoundError;
use App\Http\Controllers\Controller;
use App\Http\Services\LogService;
use App\Models\User\FlightTicket;
use App\Models\User\Reservation;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function getTicket(Request $request, string $partner)
    {
        $request->validate([
            "page" => "nullable|integer|min:1",
            "item" => "nullable|integer|min:1",
            "search" => "nullable|string",
            "plane_flight_id" => "nullable|integer",
            "plane_id" => "nullable|integer",
        ]);

        $page = $request->input("page", 1);
        $item = $request->input("item", 10);

        $data = FlightTicket::whereHas('flight.plane.airline', function ($query) use ($partner) {
            $query->where('user_id', $partner);
        })->with('user', 'flight');

        if ($request->has("search")) {
            $data->where(function ($query) use ($request) {
                $query->where("code", "LIKE", "%{$request->input("search")}%")
                    ->orWhereHas('user', function ($query) use ($request) {
                        $query->where("name", "LIKE", "%{$request->input("search")}%");
                    });
            });
        }

        if ($request->has("plane_flight_id")) {
            $data->where("plane_flight_id", $request->input("plane_flight_id"));
        }

        if ($request->has("plane_id")) {
            $data->whereHas('flight.plane', function ($query) use ($request) {
                $query->where("id", $request->input("plane_id"));
            });
        }

        $data = $data->paginate($item, ["*"], "page", $page);

        LogService::create("Melihat List Tiket Penerbangan milik pesawatnya");

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

    public function getTicketDetail($id, string $partner)
    {
        $data = FlightTicket::whereHas('flight.plane.airline', function ($query) use ($partner) {
            $query->where('user_id', $partner);
        })->with('user')->find($id);

        if (!$data) {
            throw new NotFoundError('Tiket tidak ditemukan');
        }

        LogService::create("Melihat Detail Tiket Penerbangan dengan id $id");

        return response()->json([
            "code" => 200,
            "status" => "success",
            "data" => $data,
        ]);
    }

    public function getReservation(Request $request, string $partner)
    {
        $request->validate([
            "page" => "nullable|integer|min:1",
            "item" => "nullable|integer|min:1",
            "search" => "nullable|string",
            "room_id" => "nullable|integer",
            "hotel_id" => "nullable|integer",
        ]);

        $page = $request->input("page", 1);
        $item = $request->input("item", 10);

        $data = Reservation::whereHas('room.hotel', function ($query) use ($partner) {
            $query->where('user_id', $partner);
        })->with('user', 'room');

        if ($request->has("search")) {
            $data->where(function ($query) use ($request) {
                $query->where("code", "LIKE", "%{$request->input("search")}%")
                    ->orWhereHas('user', function ($query) use ($request) {
                        $query->where("name", "LIKE", "%{$request->input("search")}%");
                    });
            });
        }

        if ($request->has("room_id")) {
            $data->where("room_id", $request->input("room_id"));
        }

        if ($request->has("hotel_id")) {
            $data->whereHas('room.hotel', function ($query) use ($request) {
                $query->where("id", $request->input("hotel_id"));
            });
        }

        $data = $data->paginate($item, ["*"], "page", $page);

        LogService::create("Melihat List Pemesanan Kamar milik hotelnya");

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

    public function getReservationDetail($id, string $partner)
    {
        $data = Reservation::whereHas('room.hotel', function ($query, $partner) {
            $query->where('user_id', $partner);
        })->with('user', 'room')->find($id);

        if (!$data) {
            throw new NotFoundError('Pemesanan tidak ditemukan');
        }

        LogService::create("Melihat Detail Pemesanan Kamar dengan id $id");

        return response()->json([
            "code" => 200,
            "status" => "success",
            "data" => $data,
        ]);
    }
}
