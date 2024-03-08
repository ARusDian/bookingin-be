<?php

namespace App\Models\Airline;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plane extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function type()
    {
        return $this->belongsTo(PlaneType::class);
    }

    public function airline()
    {
        return $this->belongsTo(Airline::class);
    }

    public function flights()
    {
        return $this->hasMany(PlaneFlight::class);
    }

    public function seats()
    {
        return $this->hasMany(PlaneSeat::class);
    }
}
