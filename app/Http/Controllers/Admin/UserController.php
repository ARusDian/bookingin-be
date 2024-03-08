<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

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
            "role" => "required|string|in:admin,partner,user",
        ]);

        $user = User::findOrFail($id);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

        $user->syncRoles([$request->role]);

        return response()->json([
            "code" => 200,
            "status" => "success",
            "data" => "User berhasil diubah",
        ]);
    }
}
