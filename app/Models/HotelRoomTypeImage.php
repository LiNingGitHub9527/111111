<?php

namespace App\Models;

class HotelRoomTypeImage extends BaseModel
{
    protected $table = 'hotel_room_type_images';

    protected $fillable = [
        'room_type_id',
        'image',
    ];

    public function imageSrc()
    {
        return $this->image ? photoUrl($this->image) : '';
    }
}
