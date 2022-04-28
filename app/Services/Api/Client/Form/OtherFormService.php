<?php

namespace App\Services\Api\Client\Form;

class OtherFormService 
{
    public function __construct()
    {
    }

    public function getRelatedData(
        \App\Models\Hotel $hotel
    ): array {
        $roomTypes = $this->_getRelatedRoomTypes($hotel);
        $cancelPolicies = $this->_getRelatedCancelPolicies($hotel);

        return [
            "plans" => [],
            "hotelRoomTypes" => $roomTypes,
            "formItems" => [],
            "hotel_name" => $hotel->name,
            "cancelPolicies" => $cancelPolicies
        ];
    }

    private function _getRelatedRoomTypes(
        \App\Models\Hotel $hotel
    ): array {
        $roomTypeList = $hotel->hotelRoomTypes;
        $roomTypes = [];
        if ($roomTypeList->isNotEmpty()) {
            foreach ($roomTypeList as $roomType) {
                $room = [
                    "id" => $roomType->id,
                    "name" => $roomType->name,
                ];
                $roomTypes[] = $room;
            }
        }

        return $roomTypes;
    }

    private function _getRelatedCancelPolicies(
        \App\Models\Hotel $hotel
    ): array {
        $cancelPolicies = [];
        $cancelPolicyList = $hotel->cancelPolicies;
        if ($cancelPolicyList->isNotEmpty()) {
            foreach ($cancelPolicyList as $cancelPolicy) {
                $cp = [
                    'id' => $cancelPolicy->id,
                    'name' => $cancelPolicy->name
                ];
                $cancelPolicies[] = $cp;
            }
        }

        return $cancelPolicies;
    }
}