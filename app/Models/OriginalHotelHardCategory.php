<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OriginalHotelHardCategory extends BaseModel
{
    protected $table = 'original_hotel_hard_categories';

    use SoftDeletes;

    protected $fillable = [
        'name',
    ];

    public function originalHotelHardItems(): HasMany
    {
        return $this->hasMany('App\Models\OriginalHotelHardItem','hard_category_id','id');
    }
}
