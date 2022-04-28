<?php

namespace App\Services\Browser\User;

use App\Models\ReservationBlock;
use Carbon\Carbon;

class ReserveSessionService
{

    public function __construct()
    {
    }

    public function forgetSessionByKey($sessionKey)
    {
        session()->forget($sessionKey);
        return true;
    }

    public function getSessionByKey($sessionKey)
    {
        return session()->get($sessionKey);
    }

    public function putSessionByKey($sessionKey, $value): void
    {
        session()->put($sessionKey, $value);
    }

    public function putSearchParam($allPlanRooms, $ageNums, $inOutDate, $urlParam, $stayType, $nights, $hotelId, $planTokens, $checkinDateTime = NULL, $checkoutDateTime = NULL)
    {
        $usePlans = [];
        session()->put('booking.stay_type', $stayType);
        session()->put('booking.base_info.url_param', $urlParam);
        session()->put('booking.base_info.in_out_date', $inOutDate);
        session()->put('booking.base_info.age_nums', $ageNums);
        session()->put('booking.base_info.nights', $nights);
        session()->put('booking.base_info.hotel_id', $hotelId);
        if (!empty($checkinDateTime)) {
            session()->put('booking.base_info.checkin_date_time', $checkinDateTime);
        }
        if (!empty($checkoutDateTime)) {
            session()->put('booking.base_info.checkout_date_time', $checkoutDateTime);
        }

        foreach ($allPlanRooms as $roomNum => $planRooms) {
            foreach ($planRooms as $planId => $planRoom) {
                if (!empty($planRoom)) {
                    $planToken = $planTokens[$planId];
                    $allPlanRooms[$roomNum][$planId]['plan_token'] = $planToken;
                    session()->put('booking.searched_plan.' . $roomNum . '.' . $planToken . '.stayable_room_type_ids', $planRoom["stayable_room_type_ids"]);
                    session()->put('booking.searched_plan.' . $roomNum . '.' . $planToken . '.plan_id', $planId);
                    if (empty($usePlans[$planId])) {
                        $usePlans[$planId]['plan_name'] = $allPlanRooms[$roomNum][$planId]['name'];
                        $usePlans[$planId]['plan_token'] = $planToken;
                        $usePlans[$planId]['cover_image'] = $allPlanRooms[$roomNum][$planId]['cover_image'];
                    }
                }
            }
        }

        return $usePlans;
    }

    public function getUrlParam(string $prefix = 'booking')
    {
        $urlParam = session()->get($prefix . '.base_info.url_param', NULL);
        return $urlParam;
    }

    public function getPlanInfo($planToken, $roomNum)
    {
        $plan = session()->get('booking.searched_plan.' . $roomNum . '.' . $planToken, NULL);
        return $plan;
    }

    public function getBaseInfo()
    {
        $baseInfo = session()->get('booking.base_info', NULL);

        return $baseInfo;
    }

    public function putRoomDetails($planId, $stayAbleRooms)
    {
        session()->put('booking.searched_rooms.plan_id', $planId);
        foreach ($stayAbleRooms as $roomNum => $rooms) {
            foreach ($rooms as $key => $room) {
                if (!empty($room)) {
                    $roomToken = 'room__' . uniqid() . mt_rand();
                    $stayAbleRooms[$roomNum][$key]['room_token'] = $roomToken;
                    session()->put('booking.searched_rooms.' . $roomToken . '.room_num', $roomNum);
                    session()->put('booking.searched_rooms.' . $roomToken . '.room_detail', $room);
                }
            }
        }

        return $stayAbleRooms;
    }

    public function getRoomDetailByToken($roomToken)
    {
        $roomDetailInfo = session()->get('booking.searched_rooms.' . $roomToken, NULL);

        return $roomDetailInfo;
    }

    public function getRoomPlanIdByToken($roomToken)
    {
        $planId = session()->get('booking.searched_rooms.plan_id', NULL);

        return $planId;
    }

    public function forgetSelectedRoomSessionByRoomNum($roomNum)
    {
        $selectedRoom = session()->get('booking.selected_rooms', NULL);
        if (empty($selectedRoom)) {
            return;
        }
        foreach ($selectedRoom as $key => $value) {
            if ($key != 'plan_id') {
                foreach ($value as $k => $v) {
                    if ($k == 'room_num' && $v == $roomNum) {
                        session()->forget('booking.selected_rooms.' . $key);
                    }
                }
            }
        }

        return true;
    }

    public function putSelectedRoom($planId, $roomToken, $roomNum, $roomDetail)
    {
        session()->put('booking.selected_rooms.plan_id', $planId);
        session()->put('booking.selected_rooms.' . $roomToken . '.room_num', $roomNum);
        session()->put('booking.selected_rooms.' . $roomToken . '.room_detail', $roomDetail);

        $selectedRooms = session()->get('booking.selected_rooms', []);
        unset($selectedRooms['plan_id']);
        $selectedCount = count($selectedRooms);
        $isAllSelected = $this->checkIsAllRoomSelected($selectedCount);
        return $isAllSelected;
    }

    public function checkIsAllRoomSelected($selectedCount)
    {
        $ageNums = session()->get('booking.base_info.age_nums', []);
        if (count($ageNums) == $selectedCount) {
            return true;
        } else {
            return false;
        }
    }

    public function forgetSelectedRoom($roomToken, $roomNum)
    {
        $targetSelectedRoom = session()->get('booking.selected_rooms.' . $roomToken, []);
        if (
            !empty($targetSelectedRoom) &&
            $targetSelectedRoom['room_num'] == $roomNum
        ) {

            session()->forget('booking.selected_rooms.' . $roomToken);
        }

        return true;
    }

    public function putBookingFees($roomFees, $roomAmount)
    {
        session()->put('booking.room_fees', $roomFees);
        session()->put('booking.room_amount', $roomAmount);

        return true;
    }

    public function makePlanTokens($planRooms)
    {
        $planTokens = [];
        $onlyPlanIds = [];
        foreach ($planRooms as $roomNum => $room) {
            $planIds = collect($room)->keys()->toArray();
            foreach ($planIds as $planId) {
                array_push($onlyPlanIds, $planId);
            }
        }
        $onlyPlanIds = collect($onlyPlanIds)->unique()->toArray();
        foreach ($onlyPlanIds as $planId) {
            $planTokens[$planId] = 'plan__' . uniqid() . mt_rand();
        }

        return $planTokens;
    }

    public function putBookingConfirmInfo($confirmKey, $plan, $reservation)
    {
        $plan = collect($plan)->toArray();
        session()->put($confirmKey . '.reservation', $reservation->toArray());
        session()->put($confirmKey . '.plan', $plan);
        if (!empty($reservation['lp_url_param'])) {
            session()->put($confirmKey . '.reservation.lp_url_param', $reservation['lp_url_param']);
        }

        return true;
    }

    public function putCancelInfo($cancelFee, $isFreeCancel)
    {
        session()->put('booking_confirm.cancel_info.cancel_fee', $cancelFee);
        session()->put('booking_confirm.cancel_info.is_free_cancel', $isFreeCancel);
        // 0: 無料キャンセル可能, 1: 無料キャンセル不可
        $nowDateTime = Carbon::now()->format('Y-m-d H:i');
        session()->put('booking_confirm.cancel_info.session_time', $nowDateTime);

        return true;
    }

    public function putChangeInfo($reserveId, $changeConfirmStatus)
    {
        session()->put('booking_confirm.change_info.confirm_status', $changeConfirmStatus);
        session()->put('booking_confirm.change_info.reservation_id', $reserveId);

        return true;
    }

    public function reduceSessionStockNum($roomDetail, $reduceNum, $roomTokens)
    {
        $roomStocks = [];
        foreach ($roomDetail['date_stock_nums'] as $date => $num) {
            $roomStocks[$date] = $num - $reduceNum;
        }

        foreach ($roomTokens as $roomToken) {
            $sessionKey = 'booking.searched_rooms.' . $roomToken . '.room_detail.date_stock_nums';
            session()->put($sessionKey, $roomStocks);
        }
    }

    public function increaseSessionStockNum($roomDetail, $increaseNum, $roomTokens)
    {
        $roomStocks = [];
        foreach ($roomDetail['date_stock_nums'] as $date => $num) {
            $roomStocks[$date] = $num + $increaseNum;
        }

        foreach ($roomTokens as $roomToken) {
            $sessionKey = 'booking.searched_rooms.' . $roomToken . '.room_detail.date_stock_nums';
            session()->put($sessionKey, $roomStocks);
        }
    }

    /**
     * 部屋タイプのワンタイムトークンを作成して返却する
     *
     * @param array $roomTypes 部屋タイプ一覧
     * @return array
     */
    public function makeRoomTypeTokens(array $roomTypes): array
    {
        $roomTypeTokens = [];
        $onlyRoomTypeIds = [];
        foreach ($roomTypes as $roomNum => $room) {
            $roomTypeIds = collect($room)->keys()->toArray();
            foreach ($roomTypeIds as $roomTypeId) {
                array_push($onlyRoomTypeIds, $roomTypeId);
            }
        }
        $onlyRoomTypeIds = collect($onlyRoomTypeIds)->unique()->toArray();
        foreach ($onlyRoomTypeIds as $roomTypeId) {
            $roomTypeTokens[$roomTypeId] = 'room_type__' . uniqid() . mt_rand();
        }

        return $roomTypeTokens;
    }


    /**
     * idをワンタイムトークンに置き換えた部屋タイプの一覧を返却する
     *
     * @param array $allRoomTypes 部屋タイプ一覧
     * @param string $urlParam URLパラメータ
     * @param integer $hotelId 施設ID
     * @param array $roomTypeTokens ワンタイムトークン
     * @return array
     */
    public function putOtherRoomTypes(array $allRoomTypes, string $urlParam, int $hotelId, array $roomTypeTokens): array
    {
        $useRoomTypes = [];
        session()->put('booking_other.base_info.url_param', $urlParam);
        session()->put('booking_other.base_info.hotel_id', $hotelId);

        foreach ($allRoomTypes as $roomNum => $roomTypes) {
            foreach ($roomTypes as $roomTypeId => $roomTypeRoom) {
                if (!empty($roomTypeRoom)) {
                    $roomTypeToken = $roomTypeTokens[$roomTypeId];
                    $allRoomTypes[$roomNum][$roomTypeId]['room_type_token'] = $roomTypeToken;
                    session()->put('booking_other.room_type.' . $roomTypeToken . '.room_type_id', $roomTypeId);
                    if (empty($useRoomTypes[$roomTypeId])) {
                        $useRoomTypes[$roomTypeId]['room_type_name'] = $allRoomTypes[$roomNum][$roomTypeId]['name'];
                        $useRoomTypes[$roomTypeId]['room_type_images'] = $allRoomTypes[$roomNum][$roomTypeId]['images'];
                        $useRoomTypes[$roomTypeId]['room_type_token'] = $roomTypeToken;
                    }
                }
            }
        }

        return $useRoomTypes;
    }

    /**
     * 指定されたroom_type_tokenの部屋情報を取得して取得する
     *
     * @param string $token ワンタイムトークン
     * @return array
     */
    public function getOtherRoomTypeInfo(string $token): array
    {
        $roomType = session()->get('booking_other.room_type.' . $token, []);
        return $roomType;
    }

    /**
     * 予約枠のワンタイムトークンを作成して返却する
     *
     * @param array $reservationBlocks 予約枠一覧
     * @return array
     */
    public function makeReservationBlockTokens(array $reservationBlocks): array
    {
        $tokens = [];
        $onlyIds = collect($reservationBlocks)->pluck('id')->unique()->toArray();
        foreach ($onlyIds as $id) {
            $tokens[$id] = 'reservation_block__' . uniqid() . mt_rand();
        }
        return $tokens;
    }

    /**
     * idをワンタイムトークンに置き換えた予約枠一覧を取得して返却する
     * また、予約完了時にチェックするセッション時刻を保存する
     *
     * @param array $reserveBlocks 予約枠一覧
     * @param array $tokens ワンタイムトークン
     * @return array
     */
    public function putReservationBlocks(array $reserveBlocks, array $tokens): array
    {
        $ret = [];
        $createdTokens = session()->get('booking_other.reservation_block.created_tokens', []);
        foreach ($reserveBlocks as $idx => $block) {
            $reservationBlockId = $block['id'];
            if (array_key_exists($reservationBlockId, $createdTokens)) {
                // 既にトークンが存在する場合は、その値を返却する
                $token = $createdTokens[$reservationBlockId];
                $ret[] = $block + ['reservation_block_token' => $token];
            } else {
                // トークンが存在しない場合は、セッションに保存して値を返却する
                $token = $tokens[$reservationBlockId];
                $ret[] = $block + ['reservation_block_token' => $token];
                session()->put('booking_other.reservation_block.' . $token . '.reservation_block_id', $reservationBlockId);
                session()->put('booking_other.reservation_block.created_tokens.' . $reservationBlockId, $token);
            }
        }
        $nowDateTime = Carbon::now()->format('Y-m-d H:i');
        session()->put('booking_other.reservation_info.session_time', $nowDateTime);
        return $ret;
    }

    /**
     * 予約枠取得時に保存したセッション時刻をY-m-d H:i形式で取得して返却する
     *
     * @param string $prefix
     * @return string
     */
    public function getReservationInfoSessionTime(string $prefix = 'booking'): string
    {
        $sessionTime = session()->get(
            $prefix . '.reservation_info.session_time',
            Carbon::minValue()->format('Y-m-d H:i')
        );
        return $sessionTime;
    }

    /**
     * 予約キャンセル確認画面表示時に保存したセッション時刻をY-m-d H:i形式で取得して返却する
     *
     * @return string
     */
    public function getCancelInfoSessionTime(): string
    {
        $sessionTime = session()->get(
            'booking_confirm.cancel_info.session_time',
            Carbon::minValue()->format('Y-m-d H:i')
        );
        return $sessionTime;
    }

    /**
     * ワンタイムトークンに紐づく、予約枠一覧を取得して返却する
     *
     * @param string $token ワンタイムトークン
     * @return array
     */
    public function getReservationBlock(string $token): array
    {
        $roomType = session()->get('booking_other.reservation_block.' . $token, NULL);
        if (is_null($roomType)) {
            return [];
        }
        return $roomType;
    }

    /**
     * 予約前の選択済み部屋タイプと料金情報を保存する
     *
     * @param array $roomTypes 選択済みの部屋タイプ
     * @param array $roomAmount 料金情報
     * @return void
     */
    public function putBookingRoomInfos(array $roomTypes, array $roomAmount): void
    {
        session()->put('booking_other.selected_rooms', $roomTypes);
        session()->put('booking_other.room_amount', $roomAmount);
    }

    /**
     * 選択された部屋タイプの詳細データを取得/整形して返却する
     *
     * @param array $selectedBlocks 選択された部屋タイプ
     * @return array
     */
    public function makeRoomTypesFromSessionData(array $selectedBlocks): array
    {
        $reserveBlocks = [];
        foreach ($selectedBlocks as $idx => $selectedBlock) {
            $reserveBlockToken = $selectedBlock['reservation_block_token'];
            $sessionReserveBlock = $this->getReservationBlock($reserveBlockToken);
            if (!empty($sessionReserveBlock)) {
                $reserveBlocks[$sessionReserveBlock['reservation_block_id']] = $selectedBlock;
            }
        }

        if (empty($reserveBlocks)) {
            return [];
        }

        $blocks = ReservationBlock::with('roomType')->whereIn('id', array_keys($reserveBlocks))
            ->orderBy('date', 'asc')->orderBy('start_hour', 'asc')->orderBy('start_minute', 'asc')
            ->get();
        $roomTypes = [];
        $blocks->each(function ($value) use (&$roomTypes, $reserveBlocks) {
            $personNums = $reserveBlocks[$value->id]['person_num'];
            foreach($personNums as $personNum) {
                $roomTypes[] = [
                    'reservation_block_id' => $value->id,
                    'reservation_block_token' => $reserveBlocks[$value->id]['reservation_block_token'],
                    'room_type_id' => $value->roomType->id,
                    'room_name' => $value->roomType->name,
                    'date' => $value->date,
                    'person_num' => $personNum,
                    'price' => $value->price, // Form料金計算前
                    'amount' => $value->price, // Form料金計算後
                    'start_time' => $value->getStartTime(),
                    'end_time' => $value->getEndTime(),
                    'room_num' => $value->room_num,
                    'reserved_num' => $value->reserved_num,
                ];
            }
        })->toArray();
        return $roomTypes;
    }
}
