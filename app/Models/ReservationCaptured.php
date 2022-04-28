<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ReservationCaptured extends BaseModel
{
    protected $table = 'reservation_captured';

    protected $fillable = ['reservation_id', 'payment_status', 'payment_information', 'captured_status', 'payment_method', 'amount_captured', 'stripe_payment_id', 'handle_date'];
}
