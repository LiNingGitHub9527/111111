<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class HotelNote extends BaseModel
{
    protected $table = 'hotel_notes';

    use SoftDeletes;

    protected $fillable = [
        'hotel_id', 'title', 'content'
    ];
}
