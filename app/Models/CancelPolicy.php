<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CancelPolicy extends BaseModel
{
    protected $table = 'cancel_policies';

    use SoftDeletes;

    protected $fillable = [
        'name',
        'hotel_id',
        'is_free_cancel',
        'free_day',
        'free_time',
        'cancel_charge_rate',
        'no_show_charge_rate',
        'is_default'
    ];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo('App\Models\Hotel');
    }

    public static function beUsed($id)
    {
        $beUsed = false;
        $plan = Plan::where("cancel_policy_id", $id)->first();
        if (!empty($plan)) {
            $beUsed = true;
        }
        return $beUsed;
    }

}
