<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Requests\Api\Client\PlanRequest;
use App\Models\Hotel;
use App\Models\HotelRoomType;
use App\Models\Plan;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PlanController extends ApiBaseController
{

    public function __construct()
    {
        $this->plan_service = app()->make('ApiPlanService');
    }

    public function list($id, Request $request)
    {
        $hotel = Hotel::where('id', $id)->where('client_id', $this->user()->id)->first();
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }

        $query = Plan::query();
        $planId = $request->get('plan_id');
        if (!empty($planId)) {
            $query->where('id', $planId);
        }

        $list = $query->where('hotel_id', $id)->orderBy('sort_num', 'Asc')->paginate(20);
        $records = [];
        foreach ($list as $item) {
            $hotelRoomTypes = [];
            if ($item->room_type_ids) {
                $hotelRoomTypeList = HotelRoomType::find($item->room_type_ids);
                foreach ($hotelRoomTypeList as $hotelRoomType) {
                    $hotelRoomTypes[] = $hotelRoomType;
                }
            }
            $mealTypes = [];
            if ($item->is_meal == 1) {
                foreach ([1 => '朝食', 2 => '昼食', 3 => '夕食'] as $key => $value) {
                    if (in_array($key, $item->meal_types)) {
                        $mealTypes[] = $value;
                    }
                }
            }
            $cancelPolicyName = null;
            $cancelPolicy = $item->cancelPolicy;
            if (!empty($cancelPolicy)) {
                $cancelPolicyName = $cancelPolicy->name;
            }
            $row = [
                'id' => $item->id,
                'name' => $item->name,
                'cancel_policy_name' => $cancelPolicyName,
                'public_status' => $item->public_status,
                'created_at' => Carbon::parse($item->created_at)->format('Y/m/d H:i:s'),
                'hotelRoomTypes' => $hotelRoomTypes,
                'is_meal' => $item->is_meal,
                'mealTypes' => $mealTypes,
                'is_min_stay_days' => $item->is_min_stay_days,
                'min_stay_days' => $item->min_stay_days,
                'is_max_stay_days' => $item->is_max_stay_days,
                'max_stay_days' => $item->max_stay_days,
                'sort_num' => $item->sort_num,
                'showDetailState' => false
            ];
            $records[] = $row;
        }
        $data = [
            'records' => $records,
            'total' => $list->total(),
            'page' => $list->currentPage(),
            'pages' => $list->lastPage(),
            'hotel' => [
                'id' => $hotel->id,
                'name' => $hotel->name,
            ]
        ];
        return $this->success($data);
    }

    public function search(Request $request)
    {
        $records = [];
        $q = $request->get('q');
        $hotelId = $request->get('hotel_id');
        if (!empty($q) && !empty($hotelId)) {
            $hotelId = $request->get('hotel_id');
            $query = Plan::query();
            $list = $query->where('hotel_id', $hotelId)->where('name', 'like', '%' . $q . '%')->limit(10)->get();
            if (!empty($list)) {
                foreach ($list as $item) {
                    $records[] = [
                        'id' => $item->id,
                        'title' => $item->name,
                    ];
                }
            }
        }
        $data = [
            'records' => $records,
        ];
        return $this->success($data);
    }

    public function init($id)
    {
        $hotel = Hotel::where('id', $id)->where('client_id', $this->user()->id)->first();
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }
        return $this->success($this->getRelatedData($hotel));
    }

    public function detail($id, Request $request)
    {
        $hotelId = $request->get('hotel_id');
        $hotel = Hotel::where('id', $hotelId)->where('client_id', $this->user()->id)->first();
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }

        $plan = $hotel->plans->find($id);
        if (empty($plan)) {
            return $this->error('データが存在しません', 404);
        }
        $data = $this->getRelatedData($plan->hotel);
        $data['detail'] = [
            'id' => $plan->id,
            'name' => $plan->name,
            'hotel_id' => $plan->hotel_id,
            'description' => $plan->description,
            'stay_type' => $plan->stay_type,
            'checkin_start_time' => $plan->checkin_start_time,
            'last_checkin_time' => $plan->last_checkin_time,
            'last_checkout_time' => $plan->last_checkout_time,
            'min_stay_time' => $plan->min_stay_time,
            'cancel_policy_id' => $plan->cancel_policy_id,
            'sort_num' => $plan->sort_num,
            'is_meal' => $plan->is_meal,
            'meal_types' => $plan->meal_types,
            'is_min_stay_days' => $plan->is_min_stay_days,
            'min_stay_days' => $plan->min_stay_days,
            'is_max_stay_days' => $plan->is_max_stay_days,
            'max_stay_days' => $plan->max_stay_days,
            'is_day_ago' => $plan->is_day_ago,
            'day_ago' => $plan->day_ago,
            'is_new_plan' => $plan->is_new_plan,
            'existing_plan_id' => $plan->existing_plan_id,
            'up_or_down' => $plan->up_or_down ?? 1,
            'calculate_method' => $plan->calculate_method ?? 0,
            'calculate_num' => $plan->calculate_num,
            'room_type_ids' => $plan->room_type_ids,
            'prepay' => $plan->prepay,
            'public_status' => $plan->public_status,
            'cover_image' => $plan->imageSrc()
        ];
        return $this->success($data);
    }

    function getRelatedData($hotel)
    {
        $cancelPolicies = $hotel->cancelPolicies;

        $roomTypeList = $hotel->hotelRoomTypes;
        $roomTypes = [];
        if (!empty($roomTypeList) && $roomTypeList->count() > 0) {
            foreach ($roomTypeList as $item) {
                $r = [
                    'id' => $item->id,
                    'name' => $item->name
                ];
                $roomTypes[] = $r;
            }
        }

        $newPlanOptions = $this->plan_service->getNewPlanOptions($hotel->id);

        return [
            'cancelPolicies' => $cancelPolicies,
            'roomTypes' => $roomTypes,
            'newPlanOptions' => $newPlanOptions,
            'hotel_name' => $hotel->name,
        ];
    }

    public function save(PlanRequest $request)
    {
        $hotelId = $request->get('hotel_id');
        $hotel = Hotel::where('id', $hotelId)->where('client_id', $this->user()->id)->first();
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }
        $data = $request->all();
        if ($data['is_new_plan'] == 1) {
            $data['existing_plan_id'] = null;
            $data['up_or_down'] = null;
            $data['calculate_method'] = null;
            $data['calculate_num'] = null;
        }
        $id = $request->get('id');
        if (empty($id)) {
            $plan = new Plan();
            $maxNum = Plan::where('hotel_id', $hotelId)->max('sort_num');
            $plan->sort_num = empty($maxNum) ? 0 : $maxNum + 1;
            $plan->is_day_ago = 0;
            $plan->day_ago = 0;
        } else {
            $plan = $hotel->plans->find($id);
            if (empty($plan)) {
                return $this->error('データが存在しません', 404);
            }
        }

        $plan->fill($data);
        $plan->save();
        return $this->success();
    }

    public function delete($id, Request $request)
    {
        $hotelId = $request->get('hotel_id');
        $hotel = Hotel::where('id', $hotelId)->where('client_id', $this->user()->id)->first();
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }

        $plan = $hotel->plans->find($id);
        if (empty($plan)) {
            return $this->error('データが存在しません', 404);
        }

        $beUsed = Plan::beUsed($id);
        if ($beUsed) {
            return $this->error('宿泊プランは利用されていますので、削除できません', 1006);
        }

        $plan->delete();
        return $this->success();
    }

    public function check($id)
    {
        return $this->success(Plan::beUsed($id));
    }

    public function sort(Request $request)
    {
        $ids = $request->get('ids');
        try {
            \DB::transaction(function () use ($ids) {
                foreach ($ids as $index => $id) {
                    Plan::find($id)->update(['sort_num' => $index]);
                }
            });
        } catch (\Exception $e) {
            Log::info('sort failed :' . $e);
            return $this->error();
        }

        return $this->success();
    }

    public function previewValidation(PlanRequest $request)
    {
        return $this->success(['url' => asset('booking/plan/preview')]);
    }

}
