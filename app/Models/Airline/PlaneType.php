<?php

namespace App\Models\Airline;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlaneType extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function airline()
    {
        return $this->belongsTo(Airline::class);
    }

    public function planes()
    {
        return $this->hasMany(Plane::class);
    }
}
