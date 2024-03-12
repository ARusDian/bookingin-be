<?php

namespace App\Models\Airline;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlaneType extends Model
{
    use HasFactory, SoftDeletes;

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
