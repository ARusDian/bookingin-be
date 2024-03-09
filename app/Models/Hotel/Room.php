<?php

namespace App\Models\Hotel;

use App\Models\User\Reservation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function type()
    {
        return $this->belongsTo(RoomType::class, 'room_type_id', 'id');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}
