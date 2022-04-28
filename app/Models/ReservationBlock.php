<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReservationBlock extends BaseModel
{
    protected $table = 'reservation_blocks';

    use SoftDeletes;

    protected $fillable = [
        'hotel_id',
        'room_type_id',
        'reservation_repeat_group_id',
        'is_available',
        'reserved_num',
        'room_num',
        'person_capacity',
        'price',
        'date',
        'start_hour',
        'start_minute',
        'end_hour',
        'end_minute',
        'is_updated',
        'is_closed',
    ];

    public function reservedReservationBlocks(): HasMany
    {
        return $this->hasMany('\App\Models\ReservedReservationBlock');
    }

    public function reservationRepeatGroup(): BelongsTo
    {
        return $this->belongsTo('\App\Models\ReservationRepeatGroup');
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo('\App\Models\Hotel');
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo('\App\Models\HotelRoomType');
    }

    public function getStartTime(): string
    {
        return sprintf('%02d:%02d:00', $this->start_hour, $this->start_minute);
    }

    public function getEndTime(): string
    {
        return sprintf('%02d:%02d:00', $this->end_hour, $this->end_minute);
    }

    public function cancel(): bool
    {
        $currentIsAvailable = $this->is_available;
        $this->decrement('reserved_num');

        $reservedNum = $this->reserved_num;
        $roomNum = $this->room_num;

        $updateIsAvailable = ($roomNum > $reservedNum) ? 1 : 0;
        if ($currentIsAvailable != $updateIsAvailable) {
            $this->fill(['is_available' => $updateIsAvailable])->save();
        }

        return true;
    }
}
