<?php
namespace App\Services\PmsApi\User;

use App\Services\commonUseCase\Reservation\ReserveSearchService;

use Carbon\Carbon;
use DB;
use App\Models\HotelRoomType;
use App\Models\Lp;
use App\Models\Form;
use App\Models\HotelKidsPolicy;

// PMSのLINE APIで予約完結するためのビジネスロジック
class LineReserveService extends ReserveSearchService 
{

    public function __construct()
    {
        $this->session_service = app()->make('ReserveSessionService');
        $this->booking_session_key = 'booking';
    }

    ##############################################################################
    # step00
    # 予約開始時に、下記３つの情報をPMS側にレスポンスし、PMS側はテーブル保存する
    public function convertBaseInfoData($lpId, $clientId, $hotelId)
    {
        $response = [
            'lp_id' => $lpId,
            'client_id' => $clientId,
            'hotel_id' => $hotelId,
        ];

        return $response;
    }

    ##############################################################################
    # step01
    # 大人人数を選択させるメッセージ送信に必要な、キッズポリシーの上限・部屋タイプの最大定員数を取得する

    # 渡されたroom_typeのidの中から最大のadult_numを取得して返す
    public function getMaxAdultNum($roomTypeIds)
    {
        $maxAdultNum = HotelRoomType::select('adult_num')
                                             ->whereIn('id', $roomTypeIds)
                                             ->get()
                                             ->max('adult_num');
        
        return $maxAdultNum;
    }

    public function convertAdultNumData($maxAdultNum, $kidsPolicies)
    {
        $response = [
            'max_adult_num' => $maxAdultNum,
            'kids_ages' => $kidsPolicies,
        ];

        return $response;
    }

    ##############################################################################
    # step02
    # 子供人数を選択させるメッセージ送信に必要な、キッズポリシーの上限・部屋タイプの最大の子供定員数を取得する 

    # step03
    # 入力された条件に基づいて宿泊プランを取得して返す
    
    ##############################################################################
    # step04
    # 選択された宿泊プランの部屋タイプを返す

    // 部屋ごとのキッズポリシー該当人数を整形する
    public function convertRoomAges($ages)
    {
        $ages = json_decode($ages);
        $ages = collect($ages)
                ->transform(function($age){
                    foreach ($age as $key => $a) {
                        if (!empty($a->room_type_id)) {
                            $roomTypeId = $a->room_type_id;
                            unset($age[$key]);
                        }
                    }
                    $roomTypeGroup[$roomTypeId] = $age;
                    return $roomTypeGroup;
                })->toArray();

        return $ages;
    }


    ##############################################################################
    // プラン詳細を整形する
    public function convertPlanDetail($cancelDesc, $noShowDesc, $payMethod, $planName, $planDesc)
    {
        $res = [
            'cancel_description' => $cancelDesc,
            'no_show_description' => $noShowDesc,
            'pay_method' => $payMethod,
            'plan_name' => $planName,
            'plan_description' => $planDesc,
        ];

        return $res;
    }

    public function unificatePlanRooms($planRooms) 
    {
        $resPlanRooms = [];
        foreach ($planRooms as $roomNum => $planRoom) {
            foreach ($planRoom as $planId => $plan) {
                if (empty($resPlanRooms[$planId])) {
                    $resPlanRooms[$planId] = $plan;
                    $resPlanRooms[$planId]['stayable_room_type_ids'] = [];
                    unset($resPlanRooms[$planId]['room_types']);
                }
                $resPlanRooms[$planId]['stayable_room_type_ids'][$roomNum] = $plan['stayable_room_type_ids'];
            }
        }
        return $resPlanRooms;
    }


    ##############################################################################
    # step05
    # CRM側のreservation_in_progressを取得し、情報入力画面をレンダリングする

    public function putBaseInfoByBookData($bookingData)
    {
        session()->put($this->booking_session_key . '.stay_type', $bookingData['inProgress']['stay_type']);

        $baseInfo = $this->makeSessionBaseInfo($bookingData);
        if ($bookingData['inProgress']['stay_type'] == 2) {
            $baseInfo['checkin_date_time'] = Carbon::parse($bookingData['inProgress']['checkin_time'])->format('Y-m-d H:i');
            $baseInfo['checkout_date_time'] = Carbon::parse($bookingData['inProgress']['checkout_time'])->format('Y-m-d H:i');
        }

        session()->put($this->booking_session_key . '.base_info', $baseInfo);

        return true;
    }

    private function makeSessionBaseInfo($bookingData)
    {
        $inOutDate = $this->convertInOutDate($bookingData['inProgress']['checkin_date'], $bookingData['inProgress']['checkout_date']);
        if ($bookingData['inProgress']['stay_type'] == 2) {
            $inOutDate[] = $bookingData['inProgress']['checkin_date'];
        }
        $nights = count($inOutDate);
        $hotelId = $bookingData['inProgress']['hotel_id'];
        $ageNums = $this->makeAgeNums($bookingData['roomsInProgress'], $bookingData['childInProgress']);

        $baseInfo['url_param'] = $bookingData['inProgress']['lp_param'];
        $baseInfo['age_nums'] = $ageNums;
        $baseInfo['nights'] = $nights;
        $baseInfo['hotel_id'] = $hotelId;
        $baseInfo['in_out_date'] = $inOutDate;

        return $baseInfo;
    }

    public function putGuestInfoByBookingData($bookingData)
    {
        #過去のゲストデータがない場合は早期return
        if (empty($bookingData['guest'])) {
            return false;
        }

        $guest = $bookingData['guest'];
        if (!empty($bookingData['reservation'])) {
            $reservation = $bookingData['reservation'];
        }

        $name = $guest['name'];
        $email = $guest['email'];
        $address = $guest['address'];
        $tel = $guest['tel'];
        $nameKana = '';
        if ($guest['is_reservation_person'] == 1 && !empty($reservation)) {
            $name = $reservation['user_name'] ?? $guest['name'];
            $email = $reservation['user_mail_addr'] ?? $guest['email'];
            $tel = $reservation['user_tel'] ?? $guest['tel'];
            $address = $reservation['user_addr'] ?? $guest['address'];
            $nameKana = $reservation['user_kana'] ?? '';
        }

        $guestInfo = [
            'name' => $name,
            'email' => $email,
            'address' => $address,
            'tel' => $tel,
            'nameKana' => $nameKana
        ];

        session()->put($this->booking_session_key . '.guest_from_line', $guestInfo);

        return true;
    }

    private function makeAgeNums($roomDetails, $childAges)
    {
        $ageNums = [];
        foreach ($roomDetails as $roomNum => $roomDetail) {
            $ageNums[$roomNum]['adult_num'] = $roomDetail['adult_num'];

            if ($roomDetail['child_num'] == 0) continue;

            $childAgeNums = collect($childAges)
                            ->where('reservation_rooms_in_progress_id', $roomDetail['reservation_rooms_in_progress_id'])
                            ->transform(function($childAgeNum){
                                $childAgeNum['num'] = $childAgeNum['child_num'];
                                unset($childAgeNum['reservation_rooms_in_progress_id']);
                                unset($childAgeNum['child_num']);

                                return $childAgeNum;
                            })
                            ->values()
                            ->toArray();

            $ageNums[$roomNum]['child_num'] = $childAgeNums;
        }

        return $ageNums;
    }

    public function putSelectedRoomByBookData($bookingData)
    {
        $planId = $bookingData['roomsInProgress'][0]['plan_id'];
        foreach ($bookingData['roomsInProgress'] as $roomNum => $roomDetail) {
            unset($roomDetail['reservation_rooms_in_progress_id']);
            unset($roomDetail['adult_num']);
            unset($roomDetail['child_num']);
            unset($roomDetail['plan_id']);

            $roomDetail['name'] = HotelRoomType::find($roomDetail['room_type_id'])->name;
            $roomDetail['amount_breakdown'] = str_replace(' 00:00:00', '', $roomDetail['amount_breakdown']);
            $roomDetail['amount_breakdown'] = json_decode($roomDetail['amount_breakdown'], true);

            $this->session_service->putSelectedRoom($planId, $roomNum+1, $roomNum, $roomDetail);
        }

        return true;
    }
}   