<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BaseCustomerItemValue extends BaseModel
{
    protected $table = 'base_customer_item_values';

    use SoftDeletes;

    protected $fillable = [
        'reservation_id',
        'base_customer_item_id',
        'name',
        'data_type',
        'value',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo('\App\Models\Reservation', 'reservation_id', 'id');
    }

}
