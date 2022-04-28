<?php

namespace App\Services\Api\Client\Form;

use App\Models\Plan;
use App\Models\HotelRoomType;

class HotelFormService
{
    public function __construct()
    {
    }

    public function getRelatedData(object $hotel): array
    {
        $plans = $this->_getRelatedPlans($hotel);
        $hotelRoomTypes = $this->_getRelatedRoomTypes($hotel);
        $formItems = $this->_getRelatedFormItems($hotel);
        
        return [
            'plans' => $plans,
            'hotelRoomTypes' => $hotelRoomTypes,
            'formItems' => $formItems,
            'hotel_name' => $hotel->name,
        ];
    }

    private function _getRelatedPlans(object $hotel): array
    {
        $planList = $hotel->plans->where('public_status', 1);
        $plans = [];
        if (!empty($planList) && $planList->count() > 0) {
            foreach ($planList as $item) {
                $hotelRoomTypeList = HotelRoomType::find($item->room_type_ids);
                $hotelRoomTypes = [];
                foreach ($hotelRoomTypeList as $hotelRoomType) {
                    $h = [
                        'id' => $hotelRoomType->id,
                        'name' => $hotelRoomType->name
                    ];
                    $hotelRoomTypes[] = $h;
                }
                $p = [
                    'id' => $item->id,
                    'name' => $item->name,
                    'hotelRoomTypes' => $hotelRoomTypes
                ];
                $plans[] = $p;
            }
        }

        return $plans;
    }

    private function _getRelatedRoomTypes(object $hotel): array
    {
        $hotelRoomTypeList = $hotel->hotelRoomTypes;
        $hotelRoomTypes = [];
        if (!empty($hotelRoomTypeList) && $hotelRoomTypeList->count() > 0) {
            foreach ($hotelRoomTypeList as $item) {
                $planRoomTypeId = Plan::where('hotel_id', $hotel->id)->whereRaw("JSON_CONTAINS(room_type_ids, '[" . $item->id . "]')")->first();
                if (!empty($planRoomTypeId)) {
                    $h = [
                        'id' => $item->id,
                        'name' => $item->name
                    ];
                    $hotelRoomTypes[] = $h;
                }
            }
        }

        return $hotelRoomTypes; 
    }

    private function _getRelatedFormItems(object $hotel): array
    {
        $formItemList = $hotel->formItems;
        $formItems = [];
        if (!empty($formItemList) && $formItemList->count() > 0) {
            foreach ($formItemList as $item) {
                $f = [
                    'id' => $item->id,
                    'name' => $item->name
                ];
                $formItems[] = $f;
            }
        }

        return $formItems;
    }
}