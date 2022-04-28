<?php
namespace App\Services\commonUseCase\Reservation;

use Carbon\Carbon;
use App\Models\HotelRoomType;
use App\Models\Plan;
use App\Models\Form;

class FormSearchService
{

    public function __construct()
    {

    }

    // formのidから、formを取得する
    public function findForm($formId)
    {
        $form = Form::find($formId);
        return $form;
    }

    // formのidから、formに紐づくroom_typeのidを取得する
    public function getFormRoomTypeIds($form, $hotelId)
    {
        if (empty($form->is_room_type)) {
            $roomTypeIds = $this->getRoomTypeIds($hotelId, 0); 
        } else {
            $roomTypeIds = $form->room_type_ids; 
        }
        return $roomTypeIds; 
    }

    public function getRoomTypeIds(int $hotelId, int $condition)
    {
        $roomTypeIds = HotelRoomType::select('id')
                                      ->where('hotel_id', $hotelId)
                                      ->where('sale_condition', $condition)
                                      ->get()
                                      ->pluck('id')
                                      ->toArray();
                    
        return $roomTypeIds;
    }

    // formのidから、formに紐づく、planのidを取得する
    public function getFormPlanIds($form, $hotelId)
    {
        if ($form->is_plan) {
            $planIds = $form->plan_ids;
        } else {
            $planIds = $this->getAllPlanIds($hotelId);
        }

        return $planIds;
    }

    public function getAllPlanIds($hotelId)
    {
        $planIds = Plan::select('id')
                         ->where('hotel_id', $hotelId)
                         ->where('public_status', 1)
                         ->get()
                         ->pluck('id')
                         ->toArray();

        return $planIds;
    }
}