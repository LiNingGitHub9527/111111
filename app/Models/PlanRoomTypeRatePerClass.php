<?php

namespace App\Models;

class PlanRoomTypeRatePerClass extends BaseModel
{
    protected $table = 'plan_room_type_rates_per_class';

    protected $fillable = [
        'plan_room_type_rate_id', 'class_type', 'class_key_number', 'class_person_num', 'class_amount'
    ];

    public function rates()
    {
        return $this->belongsTo('App\Models\PlanRoomTypeRate', 'plan_room_type_rate_id');
    }
}
