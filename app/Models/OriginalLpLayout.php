<?php

namespace App\Models;

class OriginalLpLayout extends BaseModel
{
    protected $table = 'original_lp_layouts';

    protected $fillable = [
        'original_lp_id', 'layout_id', 'component_id', 'render_html', 'layout_order', 'unique_key'
    ];

    public function component()
    {
        return $this->belongsTo('\App\Models\Component', 'component_id', 'id');
    }

    public function layout()
    {
        return $this->belongsTo('\App\Models\Layout', 'layout_id', 'id');
    }

    public function condition()
    {
        return $this->hasOne('\App\Models\OriginalLpLayoutCondition');
    }

    public function addCondition($conditionData)
    {
        $lpLayoutCondition = new OriginalLpLayoutCondition;
        $conditionData['original_lp_layout_id'] = $this->id;
        $lpLayoutCondition->fill($conditionData);
        $lpLayoutCondition->save();
    }

    public function updateCondition($conditionData)
    {
        $lpLayoutCondition = OriginalLpLayoutCondition::where('original_lp_layout_id', $this->id)->first();
        if (!empty($lpLayoutCondition)) {
            $lpLayoutCondition->fill($conditionData);
            $lpLayoutCondition->save();
        } else {
            $this->addCondition($conditionData);
        }
    }

}
