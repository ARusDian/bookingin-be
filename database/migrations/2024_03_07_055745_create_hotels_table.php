<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hotels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('name');
            $table->string('address');
            $table->string('description');
            $table->timestamps();
        });

        Schema::create('room_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel')->constrained('hotels');
            $table->string('name');
            $table->string('description');
            $table->integer('price');
            $table->timestamps();
        });

        Schema::create('room_facilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel')->constrained('hotels');
            $table->string('name');
            $table->string('description');
            $table->timestamps();
        });

        Schema::create('room_type_facilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_type_id')->constrained('room_types');
            $table->foreignId('room_facilities_id')->constrained('room_facilities');
            $table->timestamps();
        });

        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('hotels');
            $table->foreignId('room_type_id')->constrained('room_types');
            $table->string('name');
            $table->string('description');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotels');
        Schema::dropIfExists('room_types');
        Schema::dropIfExists('room_facilities');
        Schema::dropIfExists('room_type_facilities');
        Schema::dropIfExists('rooms');
    }
};
