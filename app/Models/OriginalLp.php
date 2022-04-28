<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class OriginalLp extends BaseModel
{
    protected $table = 'original_lps';

    use SoftDeletes;

    protected $fillable = [
        'title', 'cover_image', 'category_ids', 'public_status', 'business_types', 'hotel_ids', 'is_limit_hotel'
    ];

    protected $casts = [
        'category_ids' => 'array',
        'business_types' => 'array',
        'hotel_ids' => 'array',
    ];

    public function layouts()
    {
        return $this->hasMany('\App\Models\OriginalLpLayout')->with(['component', 'condition']);
    }

    public function addLayout($layoutData, $conditionData = [])
    {
        $lpLayout = new OriginalLpLayout;
        $layoutData['original_lp_id'] = $this->id;
        $lpLayout->fill($layoutData);
        if ($lpLayout->save()) {
            if (!empty($conditionData)) {
                $lpLayout->addCondition($conditionData);
            }
        }
    }

    public function updateLayout($id, $layoutData, $conditionData = [])
    {
        $lpLayout = OriginalLpLayout::find($id);
        if (!empty($lpLayout)) {
            $lpLayout->fill($layoutData);
            if ($lpLayout->save()) {
                if (!empty($conditionData)) {
                    $lpLayout->updateCondition($conditionData);
                }
            }
        }
    }

    public function filterData()
    {
        if ($this->is_limit_hotel == 0) {
            $this->hotel_ids = [];
        }
    }

    public function imageSrc()
    {
        return $this->cover_image ? photoUrl($this->cover_image) : '';
    }

}
