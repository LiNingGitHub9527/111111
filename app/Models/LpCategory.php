<?php

namespace App\Models;

class LpCategory extends BaseModel
{
    protected $table = 'lp_categories';

    protected $fillable = [
        'name', 'is_effective'
    ];

    public static function options($withEmpty = false, $format = true)
    {
        $data = self::where('is_effective', 1)->pluck('name', 'id')->toArray();
        if ($format) {
            $options = [];
            if ($withEmpty) {
                $options[] = ['text' => '選択してください', 'value' => 0];
            }
            foreach ($data as $id => $name) {
                $options[] = ['text' => $name, 'value' => $id];
            }

            return $options;
        }

        return $data;
    }
}
