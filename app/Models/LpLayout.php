<?php

namespace App\Models;

class LpLayout extends BaseModel
{
    protected $table = 'lp_layouts';

    public function component()
    {
        return $this->belongsTo('\App\Models\Component', 'component_id', 'id');
    }

    protected $fillable = [
        'client_id', 'hotel_id', 'lp_id', 'layout_id', 'component_id', 'render_html', 'layout_order', 'unique_key'
    ];

    public function layout()
    {
        return $this->belongsTo('\App\Models\Layout', 'layout_id', 'id');
    }

    public function condition()
    {
        return $this->hasOne('\App\Models\LpLayoutCondition');
    }

    public function addCondition($conditionData)
    {
        $lpLayoutCondition = new LpLayoutCondition;
        $conditionData['lp_layout_id'] = $this->id;
        $lpLayoutCondition->fill($conditionData);
        $lpLayoutCondition->save();
    }

    public function updateCondition($conditionData)
    {
        $lpLayoutCondition = LpLayoutCondition::where('lp_layout_id', $this->id)->first();
        if (!empty($lpLayoutCondition)) {
            $lpLayoutCondition->fill($conditionData);
            $lpLayoutCondition->save();
        } else {
            $this->addCondition($conditionData);
        }
    }

}
