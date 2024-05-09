<?php

namespace App\Http\Controllers\User;

use App\Exceptions\InvariantError;
use App\Exceptions\NotFoundError;
use App\Http\Controllers\Controller;
use App\Http\Services\LogService;
use App\Models\Hotel\Room;
use App\Utils\Constants;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HotelController extends Controller
{
    public function reservation(Request $request)
    {
        $request->validate([
            'room_id' => 'required|integer',
            'check_in' => 'required|date',
            'check_out' => 'required|date',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $room = Room::find($request->input('room_id'));

        if (!$room) {
            throw new NotFoundError('Kamar tidak ditemukan');
        }

        $checkIn = Carbon::parse($request->input('check_in'));
        $checkOut = Carbon::parse($request->input('check_out'));

        if ($checkIn->isPast()) {
            throw new InvariantError('Tanggal check in tidak valid');
        }

        if ($checkOut->isPast()) {
            throw new InvariantError('Tanggal check out tidak valid');
        }

        if (now()->diffInDays($checkIn) > 7) {
            throw new InvariantError('Maksimal pemesanan 7 hari sebelum check in');
        }

        if ($checkOut->diffInDays($checkIn) < 1) {
            throw new InvariantError('Minimal pemesanan 1 hari');
        }

        if ($room->reservations()->where('check_out', '>', $checkIn)->where('check_in', '<', $checkOut)->exists()) {
            throw new InvariantError('Kamar sudah dipesan');
        }

        $totalPrice = $room->type->price * $checkOut->diffInDays($checkIn);

        if ($user->balance < $totalPrice) {
            throw new InvariantError('Saldo tidak cukup');
        }

        $code = 'R' . now()->format('YmdHis') . $user->id . $room->id;

        DB::transaction(function () use ($user, $room, $checkIn, $checkOut, $totalPrice, $code) {
            $user->update([
                'balance' => $user->balance - $totalPrice,
            ]);

            $transaction = $user->transactions()->create([
                'type' => Constants::TRANSACTION_TYPE['OUT'],
                'amount' => $totalPrice,
                'description' => "Pemesanan Kamar {$room->name} Hotel {$room->hotel->name} - {$room->type->name} dari {$checkIn->format('d/m/Y')} sampai {$checkOut->format('d/m/Y')}",
            ]);

            $room->hotel->user->transactions()->create([
                'type' => Constants::TRANSACTION_TYPE['IN'],
                'amount' => $totalPrice,
                'description' => "Pemesanan Kamar {$room->name} Hotel {$room->hotel->name} - {$room->type->name} dari {$checkIn->format('d/m/Y')} sampai {$checkOut->format('d/m/Y')}",
            ]);

            $room->reservations()->create([
                'code' => $code,
                'user_id' => $user->id,
                'hotel_id' => $room->hotel->id,
                'transaction_id' => $transaction->id,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
            ]);
        });

        LogService::create("User melakukan pemesanan kamar hotel {$room->hotel->name}");

        return response()->json([
            'code' => 200,
            'status' => 'success',
            'message' => "Pemesanan Kamar {$room->name} berhasil dengan kode $code",
        ]);
    }

    public function cancel($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $ticket = $user->reservation()->with('transaction', 'hotel', 'room')->find($id);

        if (!$ticket) {
            throw new NotFoundError('Pemesanan tidak ditemukan');
        }

        if ($ticket->check_in->isPast()) {
            throw new InvariantError('Pemesanan sudah berlangsung');
        }

        if (Carbon::parse($ticket->check_in)->subDays(2) < now()) {
            throw new InvariantError('Pembatalan Pemasanan sudah ditutup');
        }

        DB::transaction(function () use ($user, $ticket) {
            $user->update([
                'balance' => $user->balance + $ticket->transaction->amount,
            ]);

            $user->transactions()->create([
                'type' => Constants::TRANSACTION_TYPE['IN'],
                'amount' => $ticket->transaction->amount,
                'description' => "Pembatalan Pemesanan Kamar {$ticket->room->name} Hotel {$ticket->hotel->name} dari {$ticket->check_in->format('d/m/Y')} sampai {$ticket->check_out->format('d/m/Y')}",
            ]);

            $ticket->hotel->user->transactions()->create([
                'type' => Constants::TRANSACTION_TYPE['OUT'],
                'amount' => $ticket->transaction->amount,
                'description' => "Pembatalan Pemesanan Kamar {$ticket->room->name} Hotel {$ticket->hotel->name} dari {$ticket->check_in->format('d/m/Y')} sampai {$ticket->check_out->format('d/m/Y')}",
            ]);

            $ticket->transaction->delete();
            $ticket->delete();
        });

        LogService::create("User membatalkan pemesanan kamar hotel {$ticket->hotel->name}");

        return response()->json([
            'code' => 200,
            'status' => 'success',
            'message' => "Pembatalan Pemesanan Kamar {$ticket->room->name} berhasil",
        ]);
    }

    public function getReservations()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $tickets = $user->reservation()->with('hotel', 'room')->get();

        LogService::create("User melihat list pemesanan");

        return response()->json([
            'code' => 200,
            'status' => 'success',
            'data' => $tickets,
        ]);
    }

    public function showReservation($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $ticket = $user->reservation()->with('transaction', 'hotel', 'room.type.facilities')->find($id);

        if (!$ticket) {
            throw new NotFoundError('Pemesanan tidak ditemukan');
        }

        LogService::create("User melihat detail pemesanan dengan ids $id");

        return response()->json([
            'code' => 200,
            'status' => 'success',
            'data' => $ticket,
        ]);
    }
}
