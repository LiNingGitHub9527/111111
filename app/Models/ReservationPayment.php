<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationPayment extends BaseModel
{
    protected $table = 'reservation_payment';

    protected $fillable = [
        'reservation_id',
        'type',
        'status',
        'message',
        'amount',
        'stripe_payment_id',
        'refund_id',
        'handle_time'
    ];
}
