<?php

namespace App\Models\Airlane;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlaneType extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function airlane()
    {
        return $this->belongsTo(Airlane::class);
    }
}
