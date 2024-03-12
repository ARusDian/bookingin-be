<?php

namespace App\Http\Controllers\Partner;

use App\Exceptions\AuthorizationError;
use App\Exceptions\NotFoundError;
use App\Http\Controllers\Controller;
use App\Http\Services\LogService;
use App\Models\Hotel\Hotel;
use App\Models\Hotel\Room;
use App\Models\Hotel\RoomFacility;
use App\Models\Hotel\RoomType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HotelController extends Controller
{
    // Hotel
    public function getHotel(Request $request)
    {
        $request->validate([
            "page" => "nullable|integer|min:1",
            "item" => "nullable|integer|min:1",
            "search" => "nullable|string",
        ]);

        $page = $request->input("page", 1);
        $item = $request->input("item", 10);

        $hotels = Hotel::where("user_id", auth()->id());

        if ($request->has("search")) {
            $hotels->where("name", "LIKE", "%{$request->input("search")}%");
        }

        $data = $hotels->paginate($item, ["*"], "page", $page);

        LogService::create("User melakukan pencarian hotel miliknya");

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

    public function createHotel(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'address' => 'required',
            'description' => 'required',
        ]);

        Hotel::create([
            'user_id' => auth()->id(),
            'name' => $request->name,
            'address' => $request->address,
            'description' => $request->description,
        ]);

        LogService::create("User membuat hotel baru");

        return response()->json([
            "code" => 201,
            "status" => "success",
            "message" => "Hotel berhasil dibuat",
        ], 201);
    }

    public function editHotel(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'address' => 'required',
            'description' => 'required',
        ]);

        $hotel = Hotel::where("user_id", auth()->id())->find($id);

        if (!$hotel) {
            throw new NotFoundError("Hotel tidak ditemukan");
        }

        $hotel->update([
            'name' => $request->name,
            'address' => $request->address,
            'description' => $request->description,
        ]);

        LogService::create("User mengubah hotel dengan id $id");

        return response()->json([
            "code" => 200,
            "status" => "success",
            "message" => "Hotel updated successfully",
        ]);
    }

    public function deleteHotel($id)
    {
        $hotel = Hotel::where("user_id", auth()->id())->find($id);

        if (!$hotel) {
            throw new NotFoundError("Hotel tidak ditemukan");
        }

        $hotel->delete();

        LogService::create("User menghapus hotel dengan id $id");

        return response()->json([
            "code" => 200,
            "status" => "success",
            "message" => "Hotel berhasil dihapus",
        ]);
    }

    // facilities
    public function getFacilities(Request $request)
    {
        $request->validate([
            "hotel_id" => "required",
            "page" => "nullable|integer|min:1",
            "item" => "nullable|integer|min:1",
            "search" => "nullable|string",
        ]);

        $page = $request->input("page", 1);
        $item = $request->input("item", 10);

        $hotel = Hotel::find($request->hotel_id);

        if (!$hotel) {
            throw new NotFoundError("Hotel tidak ditemukan");
        }

        if ($hotel->user_id !== auth()->id()) {
            throw new AuthorizationError("Anda tidak berhak mengakses fasilitas ini");
        }

        $facilities = RoomFacility::where("id", $request->hotel_id);

        if ($request->has("search")) {
            $facilities->where("name", "LIKE", "%{$request->input("search")}%");
        }

        $data = $facilities->paginate($item, ["*"], "page", $page);

        LogService::create("User melihat fasilitas hotel dengan id $request->hotel_id");

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

    public function createFacility(Request $request)
    {
        $request->validate([
            'hotel_id' => 'required',
            'name' => 'required',
            'description' => 'required',
        ]);

        $hotel = Hotel::find($request->hotel_id);

        if (!$hotel) {
            throw new NotFoundError("Hotel tidak ditemukan");
        }

        if ($hotel->user_id !== auth()->id()) {
            throw new AuthorizationError("Anda tidak berhak menambah fasilitas di hotel ini");
        }

        RoomFacility::create([
            'hotel_id' => $request->hotel_id,
            'name' => $request->name,
            'description' => $request->description,
        ]);

        LogService::create("User membuat fasilitas baru di hotel dengan id $request->hotel_id");

        return response()->json([
            "code" => 201,
            "status" => "success",
            "message" => "Fasilitas berhasil dibuat",
        ], 201);
    }

    public function editFacility(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'description' => 'required',
        ]);

        $facility = RoomFacility::find($id);

        if (!$facility) {
            throw new NotFoundError("Facility tidak ditemukan");
        }

        if ($facility->hotel->user_id !== auth()->id()) {
            throw new AuthorizationError("Anda tidak berhak mengubah fasilitas ini");
        }

        $facility->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        LogService::create("User mengubah fasilitas dengan id $id");

        return response()->json([
            "code" => 200,
            "status" => "success",
            "message" => "Fasilitas berhasil diubah",
        ]);
    }

    public function deleteFacility($id)
    {
        $facility = RoomFacility::find($id);

        if (!$facility) {
            throw new NotFoundError("Facility tidak ditemukan");
        }

        if ($facility->hotel->user_id !== auth()->id()) {
            throw new AuthorizationError("Anda tidak berhak menghapus fasilitas ini");
        }

        $facility->delete();

        LogService::create("User menghapus fasilitas dengan id $id");

        return response()->json([
            "code" => 200,
            "status" => "success",
            "message" => "Fasilitas berhasil dihapus",
        ]);
    }

    // Room Type
    public function getRoomType(Request $request)
    {
        $request->validate([
            "hotel_id" => "required",
            "page" => "nullable|integer|min:1",
            "item" => "nullable|integer|min:1",
            "search" => "nullable|string",
        ]);

        $page = $request->input("page", 1);
        $item = $request->input("item", 10);

        $hotel = Hotel::find($request->hotel_id);

        if (!$hotel) {
            throw new NotFoundError("Hotel tidak ditemukan");
        }

        if ($hotel->user_id !== auth()->id()) {
            throw new AuthorizationError("Anda tidak berhak mengakses fasilitas ini");
        }

        $types = RoomType::where("hotel_id", $request->hotel_id);

        if ($request->has("search")) {
            $types->where("name", "LIKE", "%{$request->input("search")}%");
        }

        $data = $types->paginate($item, ["*"], "page", $page);

        LogService::create("User melihat tipe ruangan hotel dengan id $request->hotel_id");

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

    public function createRoomType(Request $request)
    {
        $request->validate([
            'hotel_id' => 'required',
            'name' => 'required',
            'description' => 'required',
            'price' => 'required|integer',
            'facilities' => 'nullable|array',
            'facilities.*' => 'exists:room_facilities,id',
        ]);

        $hotel = Hotel::find($request->hotel_id);

        if (!$hotel) {
            throw new NotFoundError("Hotel tidak ditemukan");
        }

        if ($hotel->user_id !== auth()->id()) {
            throw new AuthorizationError("Anda tidak berhak menambah ruangan di hotel ini");
        }

        DB::transaction(function () use ($request) {
            $type = RoomType::create([
                'hotel_id' => $request->hotel_id,
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
            ]);

            if ($request->has("facilities")) {
                // need validate facilities to his own hotel

                $type->facilities()->attach($request->facilities);
            }
        });

        LogService::create("User membuat tipe ruangan baru di hotel dengan id $request->hotel_id");

        return response()->json([
            "code" => 201,
            "status" => "success",
            "message" => "Tipe ruangan berhasil dibuat",
        ], 201);
    }

    public function editRoomType(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'description' => 'required',
            'price' => 'required|integer',
            'facilities' => 'nullable|array',
        ]);

        $type = RoomType::find($id);

        if (!$type) {
            throw new NotFoundError("Room type tidak ditemukan");
        }

        if ($type->hotel->user_id !== auth()->id()) {
            throw new AuthorizationError("Anda tidak berhak mengedit fasilitas");
        }

        $type->update([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
        ]);

        if ($request->has("facilities")) {
            $type->facilities()->sync($request->facilities);
        }

        LogService::create("User mengubah tipe ruangan dengan id $id");

        return response()->json([
            "code" => 200,
            "status" => "success",
            "message" => "Tipe ruangan berhasil diubah",
        ]);
    }

    public function deleteRoomType($id)
    {
        $type = RoomType::find($id);

        if (!$type) {
            throw new NotFoundError("Room type tidak ditemukan");
        }

        if ($type->hotel->user_id !== auth()->id()) {
            throw new AuthorizationError("Anda tidak berhak menghapus fasilitas ini");
        }

        $type->facilities()->detach();
        $type->delete();

        LogService::create("User menghapus tipe ruangan dengan id $id");

        return response()->json([
            "code" => 200,
            "status" => "success",
            "message" => "Tipe ruangan berhasil dihapus",
        ]);
    }

    // Room
    public function getRoom(Request $request)
    {
        $request->validate([
            "hotel_id" => "required",
            "page" => "nullable|integer|min:1",
            "item" => "nullable|integer|min:1",
            "search" => "nullable|string",
        ]);

        $page = $request->input("page", 1);
        $item = $request->input("item", 10);

        $rooms = Room::where("hotel_id", $request->hotel_id);

        if ($request->has("search")) {
            $rooms->where("name", "LIKE", "%{$request->input("search")}%");
        }

        $data = $rooms->paginate($item, ["*"], "page", $page);

        LogService::create("User melihat ruangan hotel dengan id $request->hotel_id");

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

    public function createRoom(Request $request)
    {
        $request->validate([
            'hotel_id' => 'required',
            'type_id' => 'required|exists:room_types,id',
            'name' => 'required',
            'description' => 'required',
        ]);

        $hotel = Hotel::find($request->hotel_id);

        if (!$hotel) {
            throw new NotFoundError("Hotel tidak ditemukan");
        }

        if ($hotel->user_id !== auth()->id()) {
            throw new AuthorizationError("Anda tidak berhak menambah ruangan di hotel ini");
        }

        $type = $hotel->types()->find($request->type_id);

        if (!$type) {
            throw new NotFoundError("Tipe ruangan tidak ditemukan");
        }

        Room::create([
            'hotel_id' => $request->hotel_id,
            'room_type_id' => $request->type_id,
            'name' => $request->name,
            'description' => $request->description,
        ]);

        LogService::create("User membuat ruangan baru di hotel dengan id $request->hotel_id");

        return response()->json([
            "code" => 201,
            "status" => "success",
            "message" => "Ruangan berhasil dibuat",
        ], 201);
    }

    public function editRoom(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'description' => 'required',
        ]);

        $room = Room::find($id);

        if (!$room) {
            throw new NotFoundError("Room tidak ditemukan");
        }

        if ($room->hotel->user_id !== auth()->id()) {
            throw new AuthorizationError("Anda tidak berhak mengubah ruangan ini");
        }

        $room->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        LogService::create("User mengubah ruangan dengan id $id");

        return response()->json([
            "code" => 200,
            "status" => "success",
            "message" => "Ruangan berhasil diubah",
        ]);
    }

    public function deleteRoom($id)
    {
        $room = Room::find($id);

        if (!$room) {
            throw new NotFoundError("Room tidak ditemukan");
        }

        if ($room->hotel->user_id !== auth()->id()) {
            throw new AuthorizationError("Anda tidak berhak menghapus ruangan ini");
        }

        $room->delete();

        LogService::create("User menghapus ruangan dengan id $id");

        return response()->json([
            "code" => 200,
            "status" => "success",
            "message" => "Ruangan berhasil dihapus",
        ]);
    }
}
