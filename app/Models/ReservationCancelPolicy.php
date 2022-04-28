<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationCancelPolicy extends BaseModel
{
    protected $table = 'reservation_cancel_policy';

    protected $fillable = ['is_free_cancel', 'free_day', 'free_time','cancel_charge_rate', 'no_show_charge_rate'];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo('\App\Models\Reservation', 'reservation_id', 'id');
    }

    public function cancelPolicy(): BelongsTo
    {
        return $this->belongsTo('\App\Models\CancelPolicy', 'cancel_policy_id', 'id');
    }
}
