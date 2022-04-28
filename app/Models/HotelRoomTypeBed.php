<?php

namespace App\Models;

class HotelRoomTypeBed extends BaseModel
{
    protected $table = 'hotel_room_type_beds';

    protected $fillable = [
        'room_type_id',
        'bed_size',
        'bed_num',
    ];
}
