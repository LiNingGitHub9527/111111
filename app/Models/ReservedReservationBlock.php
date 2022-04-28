<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReservedReservationBlock extends BaseModel
{
    protected $table = 'reserved_reservation_blocks';

    use SoftDeletes;

    protected $fillable = [
        'reservation_id',
        'reservation_block_id',
        'customer_id',
        'line_user_id',
        'person_num',
        'price',
        'date',
        'start_hour',
        'start_minute',
        'end_hour',
        'end_minute',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo('\App\Models\Reservation');
    }

    public function reservationBlock(): BelongsTo
    {
        return $this->belongsTo('\App\Models\ReservationBlock');
    }

}
