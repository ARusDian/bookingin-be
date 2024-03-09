<?php

namespace App\Http\Controllers\Partner;

use App\Exceptions\AuthorizationError;
use App\Exceptions\NotFoundError;
use App\Http\Controllers\Controller;
use App\Models\Airline\Airline;
use App\Models\Airline\Plane;
use App\Models\Airline\PlaneFlight;
use App\Models\Airline\PlaneSeat;
use App\Models\Airline\PlaneType;
use Illuminate\Http\Request;

class AirlineController extends Controller
{
    // Airline
    public function getAirlines(Request $request)
    {
        $request->validate([
            "page" => "nullable|integer|min:1",
            "item" => "nullable|integer|min:1",
            "search" => "nullable|string",
        ]);

        $page = $request->input("page", 1);
        $item = $request->input("item", 10);

        $airline = Airline::where("user_id", auth()->id());

        if ($request->has("search")) {
            $airline->where("name", "LIKE", "%{$request->input("search")}%");
        }

        $data = $airline->paginate($item, ["*"], "page", $page);

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

    public function createAirline(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'address' => 'required',
            'description' => 'required',
        ]);

        Airline::create([
            'user_id' => auth()->id(),
            'name' => $request->name,
            'address' => $request->address,
            'description' => $request->description,
        ]);

        return response()->json([
            "code" => 201,
            "status" => "success",
            "message" => "Airline berhasil dibuat",
        ], 201);
    }

    public function editAirline(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'address' => 'required',
            'description' => 'required',
        ]);

        $hotel = Airline::where("user_id", auth()->id())->find($id);

        if (!$hotel) {
            throw new NotFoundError("Airline tidak ditemukan");
        }

        $hotel->update([
            'name' => $request->name,
            'address' => $request->address,
            'description' => $request->description,
        ]);

        return response()->json([
            "code" => 200,
            "status" => "success",
            "message" => "Airline berhasil diubah",
        ]);
    }

    public function deleteAirline($id)
    {
        $airline = Airline::where("user_id", auth()->id())->find($id);

        if (!$airline) {
            throw new NotFoundError("Airline tidak ditemukan");
        }

        $airline->delete();

        return response()->json([
            "code" => 200,
            "status" => "success",
            "message" => "Airline berhasil dihapus",
        ]);
    }

    //Tipe Pesawat
    public function getPlaneTypes(Request $request)
    {
        $request->validate([
            "airline_id" => "required",
            "page" => "nullable|integer|min:1",
            "item" => "nullable|integer|min:1",
            "search" => "nullable|string",
        ]);

        $page = $request->input("page", 1);
        $item = $request->input("item", 10);

        $airline = Airline::find($request->airline_id);

        if (!$airline) {
            throw new NotFoundError("Airline tidak ditemukan");
        }

        if ($airline->user_id !== auth()->id()) {
            throw new AuthorizationError("Anda tidak berhak mengakses airline ini");
        }

        $types = PlaneType::where("airline_id", $request->airline_id);

        if ($request->has("search")) {
            $types->where("name", "LIKE", "%{$request->input("search")}%");
        }

        $data = $types->paginate($item, ["*"], "page", $page);

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

    public function createPlaneType(Request $request)
    {
        $request->validate([
            'airline_id' => 'required',
            'name' => 'required',
            'description' => 'required',
        ]);

        $airline = Airline::find($request->airline_id);

        if (!$airline) {
            throw new NotFoundError("Airline tidak ditemukan");
        }

        if ($airline->user_id !== auth()->id()) {
            throw new AuthorizationError("Anda tidak berhak mengakses airline ini");
        }

        PlaneType::create([
            'airline_id' => $request->airline_id,
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json([
            "code" => 201,
            "status" => "success",
            "message" => "Tipe Pesawat berhasil dibuat",
        ], 201);
    }

    public function editPlaneType(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'description' => 'required',
        ]);

        $type = PlaneType::find($id);

        if (!$type) {
            throw new NotFoundError("Tipe Pesawat tidak ditemukan");
        }

        $airline = Airline::find($type->airline_id);

        if (!$airline) {
            throw new NotFoundError("Airline tidak ditemukan");
        }

        if ($airline->user_id !== auth()->id()) {
            throw new AuthorizationError("Anda tidak berhak mengakses airline ini");
        }

        $type->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json([
            "code" => 200,
            "status" => "success",
            "message" => "Tipe Pesawat berhasil diubah",
        ]);
    }

    public function deletePlaneType($id)
    {
        $type = PlaneType::find($id);

        if (!$type) {
            throw new NotFoundError("Tipe Pesawat tidak ditemukan");
        }

        $airline = Airline::find($type->airline_id);

        if (!$airline) {
            throw new NotFoundError("Airline tidak ditemukan");
        }

        if ($airline->user_id !== auth()->id()) {
            throw new AuthorizationError("Anda tidak berhak mengakses airline ini");
        }

        $type->delete();

        return response()->json([
            "code" => 200,
            "status" => "success",
            "message" => "Tipe Pesawat berhasil dihapus",
        ]);
    }

    // Pesawat
    public function getPlanes(Request $request)
    {
        $request->validate([
            "airline_id" => "required",
            "page" => "nullable|integer|min:1",
            "item" => "nullable|integer|min:1",
            "search" => "nullable|string",
        ]);

        $page = $request->input("page", 1);
        $item = $request->input("item", 10);

        $airline = Airline::find($request->airline_id);

        if (!$airline) {
            throw new NotFoundError("Airline tidak ditemukan");
        }

        if ($airline->user_id !== auth()->id()) {
            throw new AuthorizationError("Anda tidak berhak mengakses airline ini");
        }

        $planes = Plane::where("airline_id", $request->airline_id);

        if ($request->has("search")) {
            $planes->where("name", "LIKE", "%{$request->input("search")}%");
        }

        $data = $planes->paginate($item, ["*"], "page", $page);

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

    public function createPlane(Request $request)
    {
        $request->validate([
            'airline_id' => 'required',
            'plane_type_id' => 'required',
            'name' => 'required',
            'description' => 'required',
        ]);

        $airline = Airline::find($request->airline_id);

        if (!$airline) {
            throw new NotFoundError("Airline tidak ditemukan");
        }

        if ($airline->user_id !== auth()->id()) {
            throw new AuthorizationError("Anda tidak berhak mengakses airline ini");
        }

        $type = PlaneType::find($request->plane_type_id);

        if (!$type) {
            throw new NotFoundError("Tipe Pesawat tidak ditemukan");
        }

        if ($type->airline_id !== $request->airline_id) {
            throw new NotFoundError("Tipe Pesawat tidak ditemukan di airline ini");
        }

        Plane::create([
            'airline_id' => $request->airline_id,
            'plane_type_id' => $request->plane_type_id,
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json([
            "code" => 201,
            "status" => "success",
            "message" => "Pesawat berhasil dibuat",
        ], 201);
    }

    public function editPlane(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'description' => 'required',
        ]);

        $plane = Plane::find($id);

        if (!$plane) {
            throw new NotFoundError("Pesawat tidak ditemukan");
        }

        $airline = Airline::find($plane->airline_id);

        if (!$airline) {
            throw new NotFoundError("Airline tidak ditemukan");
        }

        if ($airline->user_id !== auth()->id()) {
            throw new AuthorizationError("Anda tidak berhak mengakses airline ini");
        }

        $plane->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json([
            "code" => 200,
            "status" => "success",
            "message" => "Pesawat berhasil diubah",
        ]);
    }

    public function deletePlane($id)
    {
        $plane = Plane::find($id);

        if (!$plane) {
            throw new NotFoundError("Pesawat tidak ditemukan");
        }

        $airline = Airline::find($plane->airline_id);

        if (!$airline) {
            throw new NotFoundError("Airline tidak ditemukan");
        }

        if ($airline->user_id !== auth()->id()) {
            throw new AuthorizationError("Anda tidak berhak mengakses airline ini");
        }

        $plane->delete();

        return response()->json([
            "code" => 200,
            "status" => "success",
            "message" => "Pesawat berhasil dihapus",
        ]);
    }

    // Kursi Pesawat
    public function getPlaneSeats(Request $request)
    {
        $request->validate([
            "plane_id" => "required",
            "page" => "nullable|integer|min:1",
            "item" => "nullable|integer|min:1",
            "search" => "nullable|string",
        ]);

        $page = $request->input("page", 1);
        $item = $request->input("item", 10);

        $plane = Plane::find($request->plane_id);

        if (!$plane) {
            throw new NotFoundError("Pesawat tidak ditemukan");
        }

        $airline = Airline::find($plane->airline_id);

        if (!$airline) {
            throw new NotFoundError("Airline tidak ditemukan");
        }

        if ($airline->user_id !== auth()->id()) {
            throw new AuthorizationError("Anda tidak berhak mengakses airline ini");
        }

        $seats = PlaneSeat::where("plane_id", $request->plane_id);

        if ($request->has("search")) {
            $seats->where("name", "LIKE", "%{$request->input("search")}%");
        }

        $data = $seats->paginate($item, ["*"], "page", $page);

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

    public function createPlaneSeat(Request $request)
    {
        $request->validate([
            'plane_id' => 'required',
            'name' => 'array|required',
        ]);

        $plane = Plane::find($request->plane_id);

        if (!$plane) {
            throw new NotFoundError("Pesawat tidak ditemukan");
        }

        $airline = Airline::find($plane->airline_id);

        if (!$airline) {
            throw new NotFoundError("Airline tidak ditemukan");
        }

        if ($airline->user_id !== auth()->id()) {
            throw new AuthorizationError("Anda tidak berhak mengakses airline ini");
        }

        $data = collect($request->name)->map(function ($item) use ($request) {
            return [
                'plane_id' => $request->plane_id,
                'name' => $item,
            ];
        })->toArray();

        PlaneSeat::insert($data);

        return response()->json([
            "code" => 201,
            "status" => "success",
            "message" => "Kursi Pesawat berhasil dibuat",
        ], 201);
    }

    public function editPlaneSeat(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
        ]);

        $seat = PlaneSeat::find($id);

        if (!$seat) {
            throw new NotFoundError("Kursi Pesawat tidak ditemukan");
        }

        $plane = Plane::find($seat->plane_id);

        if (!$plane) {
            throw new NotFoundError("Pesawat tidak ditemukan");
        }

        $airline = Airline::find($plane->airline_id);

        if (!$airline) {
            throw new NotFoundError("Airline tidak ditemukan");
        }

        if ($airline->user_id !== auth()->id()) {
            throw new AuthorizationError("Anda tidak berhak mengakses airline ini");
        }

        $seat->update([
            'name' => $request->name,
        ]);

        return response()->json([
            "code" => 200,
            "status" => "success",
            "message" => "Kursi Pesawat berhasil diubah",
        ]);
    }

    public function deletePlaneSeat($id)
    {
        $seat = PlaneSeat::find($id);

        if (!$seat) {
            throw new NotFoundError("Kursi Pesawat tidak ditemukan");
        }

        $plane = Plane::find($seat->plane_id);

        if (!$plane) {
            throw new NotFoundError("Pesawat tidak ditemukan");
        }

        $airline = Airline::find($plane->airline_id);

        if (!$airline) {
            throw new NotFoundError("Airline tidak ditemukan");
        }

        if ($airline->user_id !== auth()->id()) {
            throw new AuthorizationError("Anda tidak berhak mengakses airline ini");
        }

        $seat->delete();

        return response()->json([
            "code" => 200,
            "status" => "success",
            "message" => "Kursi Pesawat berhasil dihapus",
        ]);
    }

    // Penerbangan
    public function getPlaneFlights(Request $request)
    {
        $request->validate([
            "plane_id" => "required",
            "page" => "nullable|integer|min:1",
            "item" => "nullable|integer|min:1",
            "search" => "nullable|string",
        ]);

        $page = $request->input("page", 1);
        $item = $request->input("item", 10);

        $plane = Plane::find($request->plane_id);

        if (!$plane) {
            throw new NotFoundError("Pesawat tidak ditemukan");
        }

        $airline = Airline::find($plane->airline_id);

        if (!$airline) {
            throw new NotFoundError("Airline tidak ditemukan");
        }

        if ($airline->user_id !== auth()->id()) {
            throw new AuthorizationError("Anda tidak berhak mengakses airline ini");
        }

        $flights = PlaneFlight::where("plane_id", $request->plane_id);

        if ($request->has("search")) {
            $flights->where("name", "LIKE", "%{$request->input("search")}%");
        }

        $data = $flights->paginate($item, ["*"], "page", $page);

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

    public function createPlaneFlight(Request $request)
    {
        $request->validate([
            'plane_id' => 'required',
            'last_check_in' => 'required|date_format:Y-m-d H:i|before:date:departure_time',
            'departure_time' => 'required|date_format:Y-m-d H:i',
            'arrival_time' => 'required|date_format:Y-m-d H:i|after:date:departure_time',
            'departure_airport' => 'required',
            'arrival_airport' => 'required',
            'price' => 'required|integer|min:0',
        ]);

        $plane = Plane::find($request->plane_id);

        if (!$plane) {
            throw new NotFoundError("Pesawat tidak ditemukan");
        }

        $airline = Airline::find($plane->airline_id);

        if (!$airline) {
            throw new NotFoundError("Airline tidak ditemukan");
        }

        if ($airline->user_id !== auth()->id()) {
            throw new AuthorizationError("Anda tidak berhak mengakses airline ini");
        }

        PlaneFlight::create([
            'plane_id' => $request->plane_id,
            'last_check_in' => $request->last_check_in,
            'departure_time' => $request->departure_time,
            'arrival_time' => $request->arrival_time,
            'departure_airport' => $request->departure_airport,
            'arrival_airport' => $request->arrival_airport,
            'price' => $request->price,
        ]);

        return response()->json([
            "code" => 201,
            "status" => "success",
            "message" => "Penerbangan berhasil dibuat",
        ], 201);
    }

    public function editPlaneFlight(Request $request, $id)
    {
        $request->validate([
            'last_check_in' => 'required|date_format:Y-m-d H:i|before:date:departure_time',
            'departure_time' => 'required|date_format:Y-m-d H:i',
            'arrival_time' => 'required|date_format:Y-m-d H:i|after:date:departure_time',
            'departure_airport' => 'required',
            'arrival_airport' => 'required',
            'price' => 'required|integer|min:0',
        ]);

        $flight = PlaneFlight::find($id);

        if (!$flight) {
            throw new NotFoundError("Penerbangan tidak ditemukan");
        }

        $plane = Plane::find($flight->plane_id);

        if (!$plane) {
            throw new NotFoundError("Pesawat tidak ditemukan");
        }

        $airline = Airline::find($plane->airline_id);

        if (!$airline) {
            throw new NotFoundError("Airline tidak ditemukan");
        }

        if ($airline->user_id !== auth()->id()) {
            throw new AuthorizationError("Anda tidak berhak mengakses airline ini");
        }

        $flight->update([
            'last_check_in' => $request->last_check_in,
            'departure_time' => $request->departure_time,
            'arrival_time' => $request->arrival_time,
            'departure_airport' => $request->departure_airport,
            'arrival_airport' => $request->arrival_airport,
            'price' => $request->price,
        ]);

        return response()->json([
            "code" => 200,
            "status" => "success",
            "message" => "Penerbangan berhasil diubah",
        ]);
    }

    public function deletePlaneFlight($id)
    {
        $flight = PlaneFlight::find($id);

        if (!$flight) {
            throw new NotFoundError("Penerbangan tidak ditemukan");
        }

        $plane = Plane::find($flight->plane_id);

        if (!$plane) {
            throw new NotFoundError("Pesawat tidak ditemukan");
        }

        $airline = Airline::find($plane->airline_id);

        if (!$airline) {
            throw new NotFoundError("Airline tidak ditemukan");
        }

        if ($airline->user_id !== auth()->id()) {
            throw new AuthorizationError("Anda tidak berhak mengakses airline ini");
        }

        $flight->delete();

        return response()->json([
            "code" => 200,
            "status" => "success",
            "message" => "Penerbangan berhasil dihapus",
        ]);
    }
}
