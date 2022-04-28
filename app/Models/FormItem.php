<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FormItem extends BaseModel
{
    protected $table = 'form_items';

    use SoftDeletes;

    protected $casts = [
        'options' => 'array'
    ];

    protected $fillable = [
        'client_id','hotel_id', 'name', 'required', 'item_type', 'option_default',
        'options', 'sort_order'
    ];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo('\App\Models\Hotel');
    }
}
