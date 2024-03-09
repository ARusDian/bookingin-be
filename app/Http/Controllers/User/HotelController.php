<?php

namespace App\Http\Controllers\User;

use App\Exceptions\InvariantError;
use App\Exceptions\NotFoundError;
use App\Http\Controllers\Controller;
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
                'description' => "Pemesanan Kamar Hotel {$room->hotel->name} - {$room->type->name} dari {$checkIn->format('d/m/Y')} sampai {$checkOut->format('d/m/Y')}",
            ]);

            $room->hotel->user->transactions()->create([
                'type' => Constants::TRANSACTION_TYPE['IN'],
                'amount' => $totalPrice,
                'description' => "Pemesanan Kamar Hotel {$room->hotel->name} - {$room->type->name} dari {$checkIn->format('d/m/Y')} sampai {$checkOut->format('d/m/Y')}",
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

        return response()->json([
            'code' => 200,
            'status' => 'success',
            'message' => "Pemesanan Kamar {$room->name} berhasil dengan kode $code",
        ]);
    }
}
