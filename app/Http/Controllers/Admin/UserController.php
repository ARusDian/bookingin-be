<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\NotFoundError;
use App\Http\Controllers\Controller;
use App\Http\Services\LogService;
use App\Models\User;
use App\Utils\Constants;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function get(Request $request)
    {
        $request->validate([
            "page" => "nullable|integer|min:1",
            "item" => "nullable|integer|min:1",
            "search" => "nullable|string",
            "role" => "nullable|string|in:admin,partner,user",
        ]);

        $page = $request->input("page", 1);
        $item = $request->input("item", 10);

        $users = User::query();

        if ($request->has("search")) {
            $users->where("name", "LIKE", "%{$request->input("search")}%");
        }

        if ($request->has("role")) {
            $users->whereHas("roles", function ($query) use ($request) {
                $query->where("name", $request->role);
            });
        }

        $data = $users->paginate($item, ["*"], "page", $page);

        LogService::create("User melakukan pencarian user");

        return response()->json([
            "code" => 200,
            "status" => "success",
            "data" => collect($data->items())->map(function ($user) {
                return array_merge($user->toArray(), [
                    'role' => $user->roles->first()->name,
                ]);
            }),
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
        $user = User::with('transactions')->find($id);

        if (!$user) {
            throw new NotFoundError('User tidak ditemukan');
        }

        LogService::create("User melihat detail user dengan id $id");

        return response()->json([
            "code" => 200,
            "status" => "success",
            "data" => array_merge($user->toArray(), [
                'role' => $user->roles->first()->name,
            ]),
        ]);
    }

    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required',
            'phone' => 'required|unique:users,phone',
            "role" => "required|string|in:admin,partner,user",
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'phone' => $request->phone,
        ]);

        $user->assignRole($request->role);

        LogService::create("User membuat user baru");

        return response()->json([
            "code" => 201,
            "status" => "success",
            "data" => "User berhasil dibuat",
        ], 201);
    }

    public function edit(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $id,
            'phone' => 'required|unique:users,phone,' . $id,
            'password' => 'nullable',
            "role" => "required|string|in:admin,partner,user",
        ]);

        $user = User::find($id);

        if (!$user) {
            throw new NotFoundError('User tidak ditemukan');
        }

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

        if ($request->has("password")) {
            $user->update([
                'password' => bcrypt($request->password),
            ]);
        }

        $user->syncRoles([$request->role]);

        LogService::create("User mengubah user dengan id $id");

        return response()->json([
            "code" => 200,
            "status" => "success",
            "data" => "User berhasil diubah",
        ]);
    }

    public function delete($id)
    {
        $user = User::find($id);

        if (!$user) {
            throw new NotFoundError('User tidak ditemukan');
        }

        if ($user->getRoleNames()->first() === "ADMIN") {
            throw new NotFoundError('Tidak dapat menghapus admin!');
        }

        $user->delete();

        LogService::create("User menghapus user dengan id $id");

        return response()->json([
            "code" => 200,
            "status" => "success",
            "data" => "User berhasil dihapus",
        ]);
    }

    public function topup(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|integer|min:1',
        ]);

        $user = User::find($id);

        if (!$user) {
            throw new NotFoundError('User tidak ditemukan');
        }

        DB::transaction(function () use ($user, $request) {
            $user->update([
                'balance' => $user->balance + $request->amount,
            ]);

            $user->transactions()->create([
                'type' => Constants::TRANSACTION_TYPE['IN'],
                'amount' => $request->amount,
                'description' => "Topup saldo " . Carbon::now()->format("d/m/Y H:i:s"),
            ]);
        });

        LogService::create("User melakukan topup saldo sebesar Rp. " . number_format($request->amount, 0, ",", "."));

        return response()->json([
            "code" => 200,
            "status" => "success",
            "data" => "Saldo berhasil ditambahkan sebesar Rp. " . number_format($request->amount, 0, ",", "."),
        ]);
    }

    public function withdraw(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|integer|min:1',
        ]);

        $user = User::find($id);

        if (!$user) {
            throw new NotFoundError('User tidak ditemukan');
        }

        if ($user->balance < $request->amount) {
            throw new NotFoundError('Saldo tidak cukup');
        }

        DB::transaction(function () use ($user, $request) {
            $user->update([
                'balance' => $user->balance - $request->amount,
            ]);

            $user->transactions()->create([
                'type' => Constants::TRANSACTION_TYPE['OUT'],
                'amount' => $request->amount,
                'description' => "Penarikan saldo " . Carbon::now()->format("d/m/Y H:i:s"),
            ]);
        });

        LogService::create("User melakukan penarikan saldo sebesar Rp. " . number_format($request->amount, 0, ",", "."));

        return response()->json([
            "code" => 200,
            "status" => "success",
            "data" => "Saldo berhasil ditarik sebesar Rp. " . number_format($request->amount, 0, ",", "."),
        ]);
    }

    public function getTransaction(Request $request, $id)
    {
        $request->validate([
            "page" => "nullable|integer|min:1",
            "item" => "nullable|integer|min:1",
        ]);

        $page = $request->input("page", 1);
        $item = $request->input("item", 10);

        $user = User::find($id);

        if (!$user) {
            throw new NotFoundError('User tidak ditemukan');
        }

        $transactions = $user->transactions()->paginate($item, ["*"], "page", $page);

        LogService::create("User melihat riwayat transaksi saldo");

        return response()->json([
            "code" => 200,
            "status" => "success",
            "data" => $transactions->items(),
            "meta" => [
                "currentPage" => $page,
                "item" => $item,
                "totalItems" => $transactions->total(),
                "totalPages" => $transactions->lastPage(),
            ],
        ]);
    }
}
