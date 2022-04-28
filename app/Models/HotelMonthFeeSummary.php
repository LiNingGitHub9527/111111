<?php

namespace App\Models;

class HotelMonthFeeSummary extends BaseModel
{
    protected $table = 'hotel_month_fee_summary';

    public function hotel()
    {
        return $this->belongsTo('\App\Models\Hotel', 'hotel_id', 'id');
    }

    public function client()
    {
        return $this->belongsTo('\App\Models\Client', 'client_id', 'id');
    }
}
