<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\CommonUseCase\Reservation\BookingCoreController;

use Carbon\Carbon;
use App\Models\Reservation;

class BookingBaseController extends BookingCoreController
{

    public function __construct()
    {
        parent::__construct();
        $this->form_service = app()->make('FormSearchService');
        $this->kids_policy_service = app()->make('KidsPolicyService');
        $this->calc_plan_service = app()->make('CalcPlanAmountService');
        $this->calc_form_service = app()->make('CalcFormAmountService');
        $this->hard_item_service = app()->make('HardItemService');
        $this->reserve_change_service = app()->make('ReserveChangeService');
        $this->calc_cancel_policy_service = app()->make('CalcCancelPolicyService');
        $this->confirm_session_key = 'booking_confirm';
    }

    public function makeStayAbleRooms($roomNum, $stayAbleRoomTypeIdsPerRoom, $rc, $sessionBaseInfo, $stayAbleRoomTypeIds, $roomTypeRates)
    {
        $stayAbleRoom = $this->browse_reserve_service->getStayAbleRooms($stayAbleRoomTypeIdsPerRoom[$roomNum], $rc, $sessionBaseInfo['in_out_date']);
        $roomStockMap = $this->browse_reserve_service->makeRoomStockMap($stayAbleRoom);
        $stayAbleRoom =  $this->browse_reserve_service->makeDuplicateRoomUnique($stayAbleRoom);
        $stayAbleRoom = $this->browse_reserve_service->mergeRoomStocks($stayAbleRoom, $roomStockMap);
        $stayAbleRoomBeds = $this->browse_reserve_service->getRoomTypeBeds($stayAbleRoomTypeIds);
        $stayAbleRoomImages = $this->browse_reserve_service->getRoomTypeImage($stayAbleRoomTypeIds);
        $stayAbleRoomBeds = $this->browse_reserve_service->transformRoomTypeBedArr($stayAbleRoomBeds);
        // 部屋タイプの配列と、部屋タイプごとのベッド,画像, 金額の配列を合体させる
        // かつ、各日にちの一部屋の料金の内訳(amount_breakdown)を算出しマージする
        $stayAbleRoom = $this->browse_reserve_service->mergeRoomTypeArr($stayAbleRoom, $stayAbleRoomBeds, $stayAbleRoomImages, $roomTypeRates[$roomNum]);
        $stayAbleRoom = $this->browse_reserve_service->calcRoomBeds($stayAbleRoom);

        foreach ($stayAbleRoom as $roomTypeId => $room) {
            if (empty($room['images'])) {
                $stayAbleRoom[$roomTypeId]['images'][] = asset('static/common/images/no_image.png');
            }
        }

        return $stayAbleRoom;
    }
}   