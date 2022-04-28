<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReservationPlan extends BaseModel
{
    use SoftDeletes;

    protected $table = 'reservation_plans';

    protected $casts = [
        'date' => 'datetime',
    ];


    public function kidsPolicies(): HasMany
    {
        return $this->hasMany('\App\Models\ReservationKidsPolicy', 'reservation_plan_id', 'id');
    }

    public function branches()
    {
        return $this->hasOne('\App\Models\ReservationBranch', 'id', 'reservation_branch_id');
    }

    public function reservationBranch(): BelongsTo
    {
        return $this->belongsTo('\App\Models\ReservationBranch');
    }

    // public function branch(): BelongsTo
    // {
    //     return $this->belongsTo('\App\Models\ReservationBranch');
    // }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo('\App\Models\Reservation');
    }
}


