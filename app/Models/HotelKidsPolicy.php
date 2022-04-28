<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class HotelKidsPolicy extends BaseModel
{
    protected $table = 'hotel_kids_policies';

    use SoftDeletes;

    protected $casts = [
        'room_type_ids' => 'array',
    ];

    public function fillData($data)
    {
        $this->age_start = $data['age_start'];
        $this->age_end = $data['age_end'];
        $rateType = $data['rate_type'];
        if ($rateType == 1) {
            $this->is_forbidden = 0;
            $this->is_rate = 1;
            $this->fixed_amount = $data['fixed_amount'];
            $this->rate = 0;
        } else if ($rateType == 2) {
            $this->is_forbidden = 0;
            $this->is_rate = 0;
            $this->fixed_amount = 0;
            $this->rate = $data['rate'];
        } else {
            $this->is_forbidden = 1;
            if (empty($this->is_rate)) {
                $this->is_rate = 0;
            }
            $this->fixed_amount = 0;
            $this->rate = 0;
        }

        if ($data['is_all_room'] == 1) {
            $this->is_all_room = 1;
            $this->room_type_ids = [];
        } else {
            $this->is_all_room = 0;
            $this->room_type_ids = $data['room_type_ids'];
        }
    }
}
