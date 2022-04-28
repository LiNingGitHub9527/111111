<?php
namespace App\Services\Api\Client\Plan;

use Carbon\Carbon;
use DB;
use App\Models\Plan;

class PlanService
{

    public function __construct(Plan $plan)
    {
        $this->plan_table = $plan;
    }

    public function getNewPlanOptions($hotelId)
    {
        $select = ['id', 'name'];
        $where = ['is_new_plan' => 1, 'public_status' => 1, 'hotel_id' => $hotelId];
        $newPlans = $this->plan_table->getPlansByParams($select, $where);

        $newPlanOptions = $this->transformNewPlanOptions($newPlans->toArray());
        return $newPlanOptions;
    }

    public function transformNewPlanOptions(array $newPlans)
    {
        $newPlanOptions[] = ['text' => '選択してください', 'value' => ''];
        $planOptions = collect($newPlans)
                          ->transform(function($plan, $index){
                              $option['key'] = $index;
                              $option['text'] = $plan['name'];
                              $option['value'] = $plan['id'];

                              return $option;
                          })
                          ->toArray();

        $newPlanOptions = collect($newPlanOptions)->merge($planOptions)->toArray();

        return $newPlanOptions;
    }
}