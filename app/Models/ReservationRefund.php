<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationRefund extends BaseModel
{
    protected $table = 'reservation_refund';

    protected $fillable = [
        'reservation_id',
        'type',
        'status',
        'refund_information',
        'reservation_amount',
        'refund_amount',
        'stripe_payment_id',
        'refund_id',
        'handle_date'
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo('\App\Models\Reservation');
    }
}
