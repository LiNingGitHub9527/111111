<?php

namespace App\Models;

class OriginalLpLayoutCondition extends BaseModel
{
    protected $table = 'original_lp_layout_conditions';

    protected $fillable = [
        'original_lp_layout_id', 'start_point_type', 'default_start_point_seconds', 'default_start_point_scroll'
    ];
}
