<?php

namespace App\Models;

class LpLayoutCondition extends BaseModel
{
    protected $table = 'lp_layout_conditions';

    protected $fillable = [
        'lp_layout_id', 'start_point_type', 'default_start_point_seconds', 'default_start_point_scroll'
    ];
}
