<?php

namespace App\Models;

class RoomStock extends BaseModel
{
    protected $table = 'room_stocks';

    protected $fillable = [
        'client_id', 'hotel_id', 'hotel_room_type_id',
        'date', 'date_stock_num', 'date_reserve_num'
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function roomType()
    {
        return $this->belongsTo('App\Models\HotelRoomType', 'hotel_room_type_id');
    }
}
