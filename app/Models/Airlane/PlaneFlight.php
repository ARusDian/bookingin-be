<?php

namespace App\Models\Airlane;

use App\Models\User\FlightTicket;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlaneFlight extends Model
{
    use HasFactory;

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
