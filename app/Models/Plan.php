<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends BaseModel
{
    protected $table = 'plans';

    use SoftDeletes;

    public function cancelPolicy(): BelongsTo
    {
        return $this->belongsTo('App\Models\CancelPolicy', 'cancel_policy_id', 'id');
    }

    protected $fillable = [
        'name',
        'hotel_id',
        'description',
        'cancel_policy_id',
        'sort_num',
        'is_meal',
        'meal_types',
        'is_min_stay_days',
        'min_stay_days',
        'is_max_stay_days',
        'max_stay_days',
        'is_day_ago',
        'day_ago',
        'is_new_plan',
        'existing_plan_id',
        'up_or_down',
        'calculate_method',
        'calculate_num',
        'room_type_ids',
        'prepay',
        'public_status',
        'stay_type',
        'checkin_start_time',
        'last_checkin_time',
        'last_checkout_time',
        'min_stay_time',
        'cover_image'
    ];

    protected $casts = [
        'room_type_ids' => 'array',
        'meal_types' => 'array',
        'created_at' => 'datetime'
    ];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo('App\Models\Hotel');
    }

    public function rates()
    {
        return $this->hasMany('\App\Models\PlanRoomTypeRate', 'plan_id', 'id');
    }

    public static function getNames($planIds, $withRoomTypeNames = false, $roomTypeIds = [])
    {
        if (empty($planIds) || count($planIds) == 0) {
            return '';
        }
        $planList = Plan::find($planIds);
        if (!$withRoomTypeNames) {
            $names = $planList->pluck('name')->toArray();
            return implode('、', $names);
        }
        $plans = [];
        foreach ($planList as $plan) {
            $prtIds = collect($plan->room_type_ids)->filter(function ($value) use ($roomTypeIds) {
                return in_array($value, $roomTypeIds);
            });
            $roomTypeNames = HotelRoomType::find($prtIds)->pluck('name')->toArray();
            $p = [
                'name' => $plan->name,
                'roomTypeNames' => implode('、', $roomTypeNames)
            ];
            $plans[] = $p;
        }
        return $plans;
    }

    public static function getPlansByParams(array $select, array $where)
    {
        $planQuery = Plan::query();
        if (!empty($select)) {
            $planQuery = $planQuery->select($select);
        }
        if (!empty($where)) {
            $planQuery = $planQuery->where($where);
        }
        $plans = $planQuery->get();
        return $plans;
    }

    public static function options($format = true)
    {
        $data = self::where('is_new_plan', 1)->pluck('name', 'id')->toArray();
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

    public function temaStatus()
    {
        if ($this->is_new_plan == 0) {
            //非更新
            return 2;
        }
        if ($this->public_status == 0) {
            //停止中
            return 0;
        }
        //販売中
        return 1;
    }

    public static function beUsed($id)
    {
        $beUsed = false;
        $plan = Plan::where("existing_plan_id", $id)->first();
        if (!empty($plan)) {
            $beUsed = true;
        }

        $form = Form::whereRaw("JSON_CONTAINS(plan_ids,'[" . $id . "]')")->first();
        if (!empty($form)) {
            $beUsed = true;
        }

        $planRoomTypeRate = PlanRoomTypeRate::where("plan_id", $id)->first();
        if (!empty($planRoomTypeRate)) {
            $beUsed = true;
        }

        $reservationBranch = ReservationBranch::where('plan_id', $id)->first();
        if (!empty($reservationBranch)) {
            $beUsed = true;
        }

        return $beUsed;
    }

    public function imageSrc()
    {
        return $this->cover_image ? photoUrl($this->cover_image) : '';
    }
}
