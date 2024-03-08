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
        Schema::create('airlines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('name');
            $table->string('address');
            $table->string('description');
            $table->timestamps();
        });

        Schema::create('plane_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('airline_id')->constrained('airlines');
            $table->string('name');
            $table->string('description');
            $table->timestamps();
        });

        Schema::create('planes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('airline_id')->constrained('airlines');
            $table->foreignId('plane_type_id')->constrained('plane_types');
            $table->string('name');
            $table->string('description');
            $table->timestamps();
        });

        Schema::create('plane_seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plane_id')->constrained('planes');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('plane_flights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plane_id')->constrained('planes');
            $table->datetime('departure_time');
            $table->datetime('arrival_time');
            $table->string('departure_airport');
            $table->string('arrival_airport');
            $table->integer('price');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('airlines');
        Schema::dropIfExists('plane_types');
        Schema::dropIfExists('planes');
        Schema::dropIfExists('plane_seats');
        Schema::dropIfExists('plane_flights');
    }
};
