<?php

namespace App\Models;

class PlanRoomTypeRate extends BaseModel
{
    protected $table = 'plan_room_type_rates';

    protected $fillable = [
        'client_id', 'hotel_id', 'room_type_id', 'plan_id', 'date', 'date_sale_condition', 'updated_at'
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function perClasses()
    {
        return $this->hasMany('\App\Models\PlanRoomTypeRatePerClass');
    }

    public function plan()
    {
        return $this->belongsTo('\App\Models\Plan');
    }
}
