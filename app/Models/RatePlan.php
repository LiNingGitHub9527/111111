<?php

namespace App\Models;

class RatePlan extends BaseModel
{
    protected $table = 'rate_plans';

    protected $fillable = [
        'name', 'fee', 'is_effective'
    ];

    public static function options($format = true)
    {
    	$data = self::where('is_effective', 1)->pluck('name', 'id')->toArray();
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

    public static function items()
    {
        $data = self::where('is_effective', 1)->get();
        $items = [];
        foreach ($data as $item) {
            $items[$item->id] = ['name' => $item->name, 'fee' => $item->fee];
        }

        return $items;
    }

    public static function beUsed($id)
    {
        $beUsed = false;
        $hotel = Hotel::where("rate_plan_id", $id)->first();
        if (!empty($hotel)) {
            $beUsed = true;
        }

        $hotelMonthFeeSummary = HotelMonthFeeSummary::where("rate_plan_id", $id)->first();
        if (!empty($hotelMonthFeeSummary)) {
            $beUsed = true;
        }
        return $beUsed;
    }
}
