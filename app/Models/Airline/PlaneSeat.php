<?php

namespace App\Models\Airline;

use App\Models\User\FlightTicket;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlaneSeat extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function plane()
    {
        return $this->belongsTo(Plane::class);
    }

    public function tickets()
    {
        return $this->hasMany(FlightTicket::class);
    }
}
