<?php

namespace App\Models\Airlane;

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

    public function airlane()
    {
        return $this->belongsTo(Airlane::class);
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
