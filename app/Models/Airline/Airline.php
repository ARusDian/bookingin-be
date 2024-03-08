<?php

namespace App\Models\Airline;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Airline extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function types()
    {
        return $this->hasMany(PlaneType::class);
    }

    public function planes()
    {
        return $this->hasMany(Plane::class);
    }
}
