<?php

namespace App\Models\Airline;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plane extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function type()
    {
        return $this->belongsTo(PlaneType::class, 'plane_type_id', 'id');
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
