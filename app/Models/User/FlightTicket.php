<?php

namespace App\Models\User;

use App\Models\Airline\PlaneFlight;
use App\Models\Airline\PlaneSeat;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FlightTicket extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function flight()
    {
        return $this->belongsTo(PlaneFlight::class, 'plane_flight_id', 'id');
    }

    public function seat()
    {
        return $this->belongsTo(PlaneSeat::class, 'plane_seat_id', 'id');
    }
}
