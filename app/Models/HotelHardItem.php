<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class HotelHardItem extends BaseModel
{
    protected $table = 'hotel_hard_items';

    use SoftDeletes;

    protected $fillable = [
        'hotel_id',
        'original_hotel_hard_item_id',
        'is_all_room',
        'room_type_ids',
    ];

    protected $casts = [
        'room_type_ids' => 'array',
    ];


}
