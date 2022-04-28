<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReservationBranch extends BaseModel
{
    use SoftDeletes;

    protected $table = 'reservation_branches';

    protected $casts = [
        'reservation_date_time' => 'datetime',
        'cancel_date_time' => 'datetime',
        'change_date_time' => 'datetime'
    ];

    public function roomType()
    {
        return $this->belongsTo('\App\Models\HotelRoomType', 'room_type_id', 'id');
    }

    public function plan()
    {
        return $this->belongsTo('\App\Models\Plan', 'plan_id', 'id');
    }

    public function reservationPlan()
    {
        return $this->hasMany('\App\Models\ReservationPlan');
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo('\App\Models\Reservation', 'reservation_id', 'id');
    }


}


