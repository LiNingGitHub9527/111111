<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationKidsPolicy extends BaseModel
{
    protected $table = 'reservation_kids_policies';

    protected $fillable = [
        'reservation_plan_id', 'kids_policy_id', 'child_num', 'amount'
    ];

    public function reservationPlan(): BelongsTo
    {
        return $this->belongsTo('\App\Models\ReservationPlan', 'reservation_plan_id', 'id');
    }

    public function kidsPolicy(): BelongsTo
    {
        return $this->belongsTo('\App\Models\HotelKidsPolicy', 'kids_policy_id', 'id');
    }
}
