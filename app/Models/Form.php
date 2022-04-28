<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Form extends BaseModel
{
    protected $table = 'forms';

    use SoftDeletes;

    protected $casts = [
        'form_parts_ids' => 'array',
        'deadline_start' => 'datetime',
        'deadline_end' => 'datetime',
        'sale_period_start' => 'datetime',
        'sale_period_end' => 'datetime',
        'plan_ids' => 'array',
        'room_type_ids' => 'array',
        'hand_input_room_prices' => 'array',
        'all_plan_price' => 'array',
        'all_room_type_price' => 'array',
        'special_plan_prices' => 'array',
        'custom_form_item_ids' => 'array',
        'all_special_plan_prices' => 'array',
        'all_room_price_setting' => 'array',
        'special_room_price_settings' => 'array'
    ];

    protected $fillable = [
        'client_id', 'hotel_id', 'name', 'form_parts_ids', 'is_deadline',
        'deadline_start', 'deadline_end', 'is_sale_period', 'sale_period_start', 'sale_period_end', 'is_plan',
        'plan_ids', 'is_room_type', 'room_type_ids', 'is_special_price', 'is_hand_input', 'hand_input_room_prices',
        'is_all_plan', 'all_plan_price', 'special_plan_prices', 'custom_form_item_ids', 'public_status', 'all_room_type_price', 'all_special_plan_prices',
        'cancel_policy_id', 'is_all_room_price_setting', 'all_room_price_setting', 'special_room_price_settings', 'prepay', 'is_request_reservation'
    ];

    public function lp(): HasMany
    {
        return $this->hasMany('\App\Models\Lp');
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo('\App\Models\Hotel');
    }

    public static function options($hotel, $format = true)
    {
        $data = self::where('client_id', $hotel->client_id)->where('hotel_id', $hotel->id)->where('public_status', 1)->pluck('name', 'id')->toArray();
        if ($format) {
            $options = [];
            $options[] = ['text' => '選択してください', 'value' => 0];
            foreach ($data as $id => $name) {
                $options[] = ['text' => $name, 'value' => $id];
            }

            return $options;
        }

        return $data;
    }

    public function reservationPeriodDate($format = 'Y/m/d')
    {
        if ($this->is_deadline != 1) {
            return '';
        }
        $deadlineStart = $this->deadline_start;
        $deadlineEnd = $this->deadline_end;
        return Carbon::parse($deadlineStart)->format($format) . ' 〜 ' . Carbon::parse($deadlineEnd)->format($format);
    }

    public function filterData()
    {
        $this->form_parts_ids = [];
        if ($this->is_deadline == 0) {
            $this->deadline_start = null;
            $this->deadline_end = null;
        }

        if ($this->is_sale_period == 0) {
            $this->sale_period_start = null;
            $this->sale_period_end = null;
        }

        if ($this->is_plan == 0) {
            $this->plan_ids = [];
            $this->special_plan_prices = [];
        } else {
            $this->all_special_plan_prices = [];
        }

        if ($this->is_room_type == 0) {
            $this->room_type_ids = [];
        }

        if ($this->is_special_price == 0) {
            $this->is_hand_input = 0;
            $this->special_plan_prices = [];
            $this->is_all_plan = 0;
        }

        if ($this->is_hand_input == 0) {
            $this->hand_input_room_prices = [];
        } else {
            $this->special_plan_prices = [];
            $this->is_all_plan = 0;
        }

        if ($this->is_all_plan == 0) {
            $this->all_plan_price = new \stdClass();
        } else {
            $this->special_plan_prices = [];
        }

        if ($this->is_all_room_price_setting == 0) {
            $this->all_room_type_price = new \stdClass();
            $this->all_room_price_setting = new \stdClass();
        } else {
            $this->special_room_price_settings = [];
        }

        $this->custom_form_item_ids = [];
    }

    public static function beUsed($id)
    {
        $beUsed = false;
        $lp = Lp::where("form_id", $id)->first();
        if (!empty($lp)) {
            $beUsed = true;
        }
        return $beUsed;
    }

    public function cancelPolicy(): BelongsTo
    {
        return $this->belongsTo('App\Models\CancelPolicy', 'cancel_policy_id', 'id');
    }

    public function isAdminForm():bool{
        return $this->name == 'Admin' && $this->client_id == 0;

    }

}
