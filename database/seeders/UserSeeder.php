<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        collect([
            [
                "name" => "admin",
                "email" => "admin@gmail.com",
                "password" => bcrypt("1234567890"),
                "phone" => "081234567890",
            ],
            [
                "name" => "partner",
                "email" => "partner@gmail.com",
                "password" => bcrypt("1234567890"),
                "phone" => "081234567891",
            ],
            [
                "name" => "user",
                "email" => "user@gmail.com",
                "password" => bcrypt("1234567890"),
                "phone" => "081234567892",
            ],
        ])->each(function ($user) {
            User::create($user);
        });

        User::where("name", "admin")->first()->assignRole("ADMIN");
        User::where("name", "partner")->first()->assignRole("PARTNER");
        User::where("name", "user")->first()->assignRole("USER");
    }
}
