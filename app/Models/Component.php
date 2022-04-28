<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Component extends BaseModel
{
    use SoftDeletes;
    
    const TYPE_NAMES = ['', 'normal', 'popup', 'search-panel'];

    protected $table = 'components';

    protected $fillable = [
        'name', 'html', 'type', 'data', 'public_status', 'business_types', 'sort_num', 'is_limit_hotel', 'hotel_ids'
    ];

    protected $casts = [
        'data' => 'array',
        'business_types' => 'array',
        'hotel_ids' => 'array',
    ];

    const BUSINESS_TYPE_LIST = [
        1 => 'ホテル',
        2 => '塗装業',
        3 => '美容業界',
        4 => 'サウナ・温浴施設',
        5 => '不動産業界'
    ];

    public function layouts()
    {
        return $this->hasMany('\App\Models\Layout', 'component_id', 'id');
    }

    public static function options($format = true)
    {
        $data = self::where('public_status', 1)->pluck('name', 'id')->toArray();
        if ($format) {
            $options = [];
            $options[] = ['text' => '選択してください', 'value' => ''];
            foreach ($data as $id => $name) {
                $options[] = ['text' => $name, 'value' => $id];
            }

            return $options;
        }

        return $data;
    }

    public function typeName()
    {
        return self::TYPE_NAMES[$this->type] ?? '';
    }

    public function popupSize()
    {
        if (empty($this->data)) {
            return null;
        }
        return json_encode($this->data);
    }

    public static function beUsed($id)
    {
        $beUsed = false;
        $layout = Layout::where("component_id", $id)->first();
        if (!empty($layout)) {
            $beUsed = true;
        }

        $lpLayout = LpLayout::where("component_id", $id)->first();
        if (!empty($lpLayout)) {
            $beUsed = true;
        }

        $originalLpLayout = OriginalLpLayout::where("component_id", $id)->first();
        if (!empty($originalLpLayout)) {
            $beUsed = true;
        }
        return $beUsed;
    }

    public function statusDisplayName()
    {   
        $business_types = [];
        foreach ($this->business_types as $business_type) {
            $business_types[] = self::BUSINESS_TYPE_LIST[$business_type] ?? '';
        }
        return implode(',', $business_types);
    }

    public function filterData()
    {
        if ($this->is_limit_hotel == 0) {
            $this->hotel_ids = [];
        }
    }



}
