<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class OriginalHotelHardItem extends BaseModel
{
    protected $table = 'original_hotel_hard_items';

    use SoftDeletes;

    protected $fillable = [
        'hard_category_id',
        'name',
    ];
}
