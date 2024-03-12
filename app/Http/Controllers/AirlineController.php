<?php

namespace App\Http\Controllers;

use App\Exceptions\NotFoundError;
use App\Http\Services\LogService;
use App\Models\Airline\Airline;
use App\Models\Airline\PlaneFlight;
use App\Models\Airline\PlaneSeat;
use App\Models\User\FlightTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AirlineController extends Controller
{
    public function getAirline(Request $request)
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

        if (Auth::check()) {
            LogService::create("User melakukan pencarian airline");
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

    public function showAirline($id)
    {
        $airline = Airline::with('planes.type')->find($id);

        if (!$airline) {
            throw new NotFoundError("Airline not found");
        }

        if (Auth::check()) {
            LogService::create("User melihat airline dengan ids $id");
        }

        return response()->json([
            "code" => 200,
            "status" => "success",
            "data" => $airline
        ]);
    }

    public function getFlights(Request $request)
    {
        $request->validate([
            "page" => "nullable|integer|min:1",
            "item" => "nullable|integer|min:1",
            "search" => "nullable|string",
            "available" => "nullable|boolean",
        ]);

        $page = $request->input("page", 1);
        $item = $request->input("item", 10);

        $flights = PlaneFlight::with('plane');

        if ($request->has("available")) {
            $flights = $flights->where('last_check_in', '>', now());
        }

        if ($request->has("search")) {
            $flights = $flights->where(function ($query) use ($request) {
                $query->where("departure_airport", "LIKE", "%{$request->input("search")}%")
                    ->orWhere("arrival_airport", "LIKE", "%{$request->input("search")}%");
            });
        }

        $data = $flights
            ->select([
                'plane_flights.*',
                DB::raw("(SELECT COUNT(*) FROM plane_seats WHERE plane_seats.plane_id = planes.id) AS seats_count"),
                DB::raw("(SELECT COUNT(*) FROM flight_tickets WHERE flight_tickets.plane_flight_id = plane_flights.id) AS tickets_count"),
                DB::raw("(COALESCE((SELECT COUNT(*) FROM plane_seats WHERE plane_seats.plane_id = planes.id), 0) - COALESCE((SELECT COUNT(*) FROM flight_tickets WHERE flight_tickets.plane_flight_id = plane_flights.id), 0)) AS available_seats_count"),
            ])
            ->join('planes', 'plane_flights.plane_id', '=', 'planes.id')
            ->paginate($item, ["*"], "page", $page);

        if (Auth::check()) {
            LogService::create("User mencari penerbangan");
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

    public function showFlight($id)
    {
        $flight = PlaneFlight::with('plane.type', 'plane.airline')->find($id);

        if (!$flight) {
            throw new NotFoundError("Flight not found");
        }

        $seats = PlaneSeat::where('plane_id', $flight->plane_id)->get();
        $tickets = FlightTicket::where('plane_flight_id', $id)->get();

        $flight->seats_count = count($seats);
        $flight->tickets_count = count($tickets);
        $flight->available_seats_count = count($seats) - count($tickets);
        $flight->seats = $seats->map(function ($seat) use ($tickets) {
            $ticket = $tickets->firstWhere('plane_seat_id', $seat->id);
            return [
                "id" => $seat->id,
                "name" => $seat->name,
                "available" => !$ticket,
            ];
        });

        if (Auth::check()) {
            LogService::create("User melihat penerbangan dengan ids $id");
        }

        return response()->json([
            "code" => 200,
            "status" => "success",
            "data" => $flight
        ]);
    }
}
