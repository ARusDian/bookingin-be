<?php

namespace App\Http\Controllers\User;

use App\Exceptions\InvariantError;
use App\Exceptions\NotFoundError;
use App\Http\Controllers\Controller;
use App\Http\Services\LogService;
use App\Models\Airline\PlaneFlight;
use App\Models\Log;
use App\Utils\Constants;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AirlineController extends Controller
{
    public function buyTicket(Request $request)
    {
        $request->validate([
            'plane_flight_id' => 'required|integer',
            'plane_seat_id' => 'required|integer',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $flight = PlaneFlight::find($request->input('plane_flight_id'));

        if (!$flight) {
            throw new NotFoundError('Penerbangan tidak ditemukan');
        }

        if (Carbon::parse($flight->last_check_in)->subDays(7) < now()) {
            throw new InvariantError('Pembelian Tiket masih ditutup, akan dibuka tanggal ' . Carbon::parse($flight->last_check_in)->subDays(7)->format('d F Y H:i:s'));
        }

        if ($flight->last_check_in < now()) {
            throw new InvariantError('Pembelian Tiket sudah ditutup');
        }

        $seat = $flight->plane->seats()->find($request->input('plane_seat_id'));

        if (!$seat) {
            throw new NotFoundError('Kursi tidak ditemukan');
        }

        if ($flight->tickets()->where('plane_seat_id', $seat->id)->exists()) {
            throw new InvariantError('Kursi sudah terisi');
        }

        if ($user->balance < $flight->price) {
            throw new InvariantError('Saldo tidak cukup');
        }

        $code = 'T' . now()->format('YmdHis') . $user->id . $flight->id . $seat->id;

        DB::transaction(function () use ($user, $flight, $seat, $code) {
            $user->update([
                'balance' => $user->balance - $flight->price,
            ]);

            $transaction = $user->transactions()->create([
                'type' => Constants::TRANSACTION_TYPE['OUT'],
                'amount' => $flight->price,
                'description' => "Pembelian Tiket Penerbangan Pesawat {$flight->plane->name} - {$flight->departure_airport} ke {$flight->arrival_airport}",
            ]);

            $flight->plane->airline->user->transactions()->create([
                'type' => Constants::TRANSACTION_TYPE['IN'],
                'amount' => $flight->price,
                'description' => "Pembelian Tiket Penerbangan Pesawat {$flight->plane->name} - {$flight->departure_airport} ke {$flight->arrival_airport}",
            ]);

            $flight->tickets()->create([
                'code' => $code,
                'transaction_id' => $transaction->id,
                'user_id' => $user->id,
                'plane_flight_id' => $flight->id,
                'plane_seat_id' => $seat->id,
            ]);
        });

        LogService::create("User membeli tiket penerbangan pesawat {$flight->plane->name} - $flight->departure_airport ke $flight->arrival_airport dengan kode $code");

        return response()->json([
            'code' => 200,
            'status' => 'success',
            'message' => "Pembelian Tiket $code Berhasil",
        ]);
    }

    public function cancel($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $ticket = $user->tickets()->with('transaction', 'seat', 'flight.plane.airline')->find($id);

        if (!$ticket) {
            throw new NotFoundError('Tiket tidak ditemukan');
        }

        if (Carbon::parse($ticket->flight->last_check_in)->subDays(2) < now()) {
            throw new InvariantError('Pembatalan Tiket sudah ditutup');
        }

        if ($ticket->flight->last_check_in < now()) {
            throw new InvariantError('Pembatalan Tiket sudah ditutup');
        }

        DB::transaction(function () use ($user, $ticket) {
            $user->update([
                'balance' => $user->balance + $ticket->flight->price,
            ]);

            $user->transactions()->create([
                'type' => Constants::TRANSACTION_TYPE['IN'],
                'amount' => $ticket->flight->price,
                'description' => "Pembatalan Tiket Penerbangan Pesawat {$ticket->flight->plane->name} - {$ticket->flight->departure_airport} ke {$ticket->flight->arrival_airport}",
            ]);

            $ticket->flight->plane->airline->user->transactions()->create([
                'type' => Constants::TRANSACTION_TYPE['OUT'],
                'amount' => $ticket->flight->price,
                'description' => "Pembatalan Tiket Penerbangan Pesawat {$ticket->flight->plane->name} - {$ticket->flight->departure_airport} ke {$ticket->flight->arrival_airport}",
            ]);

            $ticket->delete();
        });

        LogService::create("User membatalkan tiket penerbangan pesawat {$ticket->flight->plane->name} - {$ticket->flight->departure_airport} ke {$ticket->flight->arrival_airport} dengan kode {$ticket->code}");

        return response()->json([
            'code' => 200,
            'status' => 'success',
            'message' => "Pembatalan Tiket {$ticket->code} Berhasil",
        ]);
    }

    public function getTicketList()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $tickets = $user->tickets()->with('flight.plane.airline')->get();

        LogService::create("User melihat list tiket");

        return response()->json([
            'code' => 200,
            'status' => 'success',
            'data' => $tickets,
        ]);
    }

    public function showTicket($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $ticket = $user->tickets()->with('transaction', 'seat', 'flight.plane.airline')->find($id);

        if (!$ticket) {
            throw new NotFoundError('Tiket tidak ditemukan');
        }

        LogService::create("User melihat detail ticket dengan ids $id");

        return response()->json([
            'code' => 200,
            'status' => 'success',
            'data' => $ticket,
        ]);
    }
}
