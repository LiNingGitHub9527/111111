<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReservationRepeatGroup extends BaseModel
{
    protected $table = 'reservation_repeat_groups';

    use SoftDeletes;

    protected $fillable = [
        'hotel_id',
        'room_type_id',
        'start_hour',
        'start_minute',
        'end_hour',
        'end_minute',
        'repeat_interval_type',
        'repeat_start_date',
        'repeat_end_date',
    ];

    public function reservationBlocks(): HasMany
    {
        return $this->hasMany('\App\Models\ReservationBlock');
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo('\App\Models\Hotel');
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo('\App\Models\HotelRoomType');
    }
}
