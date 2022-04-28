<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lp extends BaseModel
{
    protected $table = 'lps';

    use SoftDeletes;

    protected $fillable = [
        'client_id', 'hotel_id', 'original_lp_id', 'title', 'cover_image', 'form_id', 'public_status'
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo('App\Models\Form');
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo('App\Models\Hotel');
    }

    public function layouts(): HasMany
    {
        return $this->hasMany('\App\Models\LpLayout')->with(['component', 'condition']);
    }

    public function addLayout($layoutData, $conditionData = [])
    {
        $lpLayout = new LpLayout;
        $layoutData['lp_id'] = $this->id;
        $lpLayout->fill($layoutData);
        if ($lpLayout->save()) {
            if (!empty($conditionData)) {
                $lpLayout->addCondition($conditionData);
            }
        }
    }

    public function updateLayout($id, $layoutData, $conditionData = [])
    {
        $lpLayout = LpLayout::find($id);
        if (!empty($lpLayout)) {
            $lpLayout->fill($layoutData);
            if ($lpLayout->save()) {
                if (!empty($conditionData)) {
                    $lpLayout->updateCondition($conditionData);
                }
            }
        }
    }

    public function imageSrc()
    {
        return $this->cover_image ? photoUrl($this->cover_image) : '';
    }
}
