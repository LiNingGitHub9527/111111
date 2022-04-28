<?php

namespace App\Services\Browser\User;

use App\Services\commonUseCase\Reservation\ReserveSearchService;

use Carbon\Carbon;
use App\Models\HotelRoomType;
use App\Models\HotelKidsPolicy;
use App\Models\Hotel;
use DB;

class BrowserReserveService extends ReserveSearchService
{

    public function __construct()
    {
        $this->reserve_service = app()->make('ReserveService');
        $this->kids_policy_service = app()->make('KidsPolicyService');
    }

    public function getMaxChildAge($kidsPolicies)
    {
        $childMaxAge = collect($kidsPolicies)->max('age_end');
        return $childMaxAge;
    }

    public function sortKidspolicyByAge($kidsPolicies)
    {
        return collect($kidsPolicies)->sortByDesc('age_start')->values()->toArray();
    }

    public function getMaxAdulttNum($roomTypeIds)
    {
        $maxAdultNum = HotelRoomType::select('adult_num')
            ->whereIn('id', $roomTypeIds)
            ->get();
        $maxAdultNum = $maxAdultNum->max('child_num');
        return $maxAdultNum;
    }

    public function checkIncludeStayType($stayAblePlans, $stayType)
    {
        $stayTypes = collect($stayAblePlans)->pluck('stay_type')->toArray();
        $check = in_array($stayType, $stayTypes);

        return $check;
    }

    public function convertMealTypes($mealTypes)
    {
        $mealTypeKana = '';
        if (in_array(1, $mealTypes)) {
            $mealTypeKana .= '朝・';
        }
        if (in_array(2, $mealTypes)) {
            $mealTypeKana .= '昼・';
        }
        if (in_array(3, $mealTypes)) {
            $mealTypeKana .= '夕・';
        }

        $mealTypeKana = rtrim($mealTypeKana, '・');
        return $mealTypeKana;
    }

    public function convertPostAges($post)
    {
        $age = [];
        foreach ($post as $key => $value) {
            if (strpos($key, 'kidstart') !== false) {
                $ageStartArr = explode('_', $key);
                $ageStart = $ageStartArr[1];
                $targetKidsPolicy = HotelKidsPolicy::select('age_start', 'age_end')->where('age_start', $ageStart)->first()->toArray();
                // 本来は部屋数目ごとに作成する必要あり！
                $kidsSum = collect($value)->sum();
                $targetKidsPolicy['num'] = $kidsSum;
                array_push($age, $targetKidsPolicy);
            }
        }

        return $age;
    }

    public function convertPostAgesPerRoom($post, $hotelId)
    {
        $age = [];
        foreach ($post as $key => $value) {
            if ($key === 'adult_num') {
                foreach ($value as $k => $v) {
                    $age[$k]['adult_num'] = $v;
                }
            }
            if (strpos($key, 'kidstart') !== false) {
                $ageStartArr = explode('_', $key);
                $ageStart = $ageStartArr[1];
                $targetKidsPolicy = HotelKidsPolicy::select('age_start', 'age_end')->where('age_start', $ageStart)->where('hotel_id', $hotelId)->first()->toArray();
                foreach ($value as $roomNum => $ageStartNum) {
                    // dd($post, $ageStart, $value, $roomNum, $ageStartNum);
                    $targetKidsPolicy['num'] = $ageStartNum;
                    if ($ageStartNum > 0) {
                        $age[$roomNum]['child_num'][] = $targetKidsPolicy;
                    }
                }
            }
        }

        return $age;
    }

    public function convertAgeNumPerRoom($ageNums)
    {
        $ageNumsKana = [];
        foreach ($ageNums as $roomNum => $ageNum) {
            $displayTx = '';
            $adultNum = $ageNum['adult_num'];
            $displayTx .= '大人' . $adultNum . '人';
            if (!empty($ageNum['child_num'])) {
                foreach ($ageNum['child_num'] as $childNum) {
                    $displayTx .= '、' . $childNum['age_end'] . '歳以下' . $childNum['num'] . '人';
                }
            }
            $ageNumKana[$roomNum] = $displayTx;
        }
        return $ageNumKana;
    }

    public function calcRoomBeds($stayAbleRoom)
    {
        $stayAbleRoom = collect($stayAbleRoom)
            ->transform(function ($room) {
                if (!empty($room['beds'])) {
                    $bedSum = $this->calcBedSum($room['beds']);
                } else {
                    $bedSum = 0;
                }
                $room['bed_sum'] = $bedSum;

                return $room;
            })
            ->toArray();

        return $stayAbleRoom;
    }

    public function calcBedSum($beds)
    {
        $bedSum = 0;
        foreach ($beds as $bed) {
            $bedSum += $bed['bed_num'];
        }

        return $bedSum;
    }

    public function convertBedTypes($beds)
    {
        $bedsKana = '';
        foreach ($beds as $bed) {
            $kana = $bed['bed_type'] . $bed['bed_num'] . '台/';
            $bedsKana .= $kana;
        }
        $bedsKana = rtrim($bedsKana, '/');

        return $bedsKana;
    }

    public function makeStayBookingDateKana($bookingData, $hotel)
    {
        $checkinStartDate = Carbon::parse($bookingData['base_info']['in_out_date'][0])->format('Y/n/j');
        $checkinStartTime = Carbon::parse($hotel->checkin_start)->format('H:i');
        $checkinEndTime = Carbon::parse($hotel->checkin_end)->format('H:i');
        $bookingData['checkin_start_end'] = $checkinStartDate . ' ' . $checkinStartTime . ' - ' . $checkinEndTime;

        $checkoutDate = Carbon::parse(end($bookingData['base_info']['in_out_date']))->modify('+1 day')->format('Y/n/j');
        $checkoutEndTime =  Carbon::parse($hotel->checkout_end)->format('H:i');
        $bookingData['checkout_end'] = $checkoutDate . ' ' . $checkoutEndTime . ' まで';

        return $bookingData;
    }

    public function transformStayFeePerRoom($selectedRooms)
    {
        $roomFees = collect($selectedRooms)
            ->transform(function ($room, $key) {
                if ($key != 'plan_id') {
                    $roomName = $room['room_detail']['name'];
                    $roomAmount = $room['room_detail']['amount'];
                    $feeData['room_name'] = $roomName;
                    $feeData['amount'] = $roomAmount;
                    return $feeData;
                }
            })
            ->forget('plan_id')
            ->toArray();

        return $roomFees;
    }

    public function calcSumAmount($roomFees)
    {
        $sum = 0;
        foreach ($roomFees as $fee) {
            $sum += $fee['amount'];
        }

        return $sum;
    }

    public function calcTax($roomAmount, $isTax)
    {
        $taxAmount = floor($roomAmount['sum'] * 0.1);
        if ($isTax == 1) {
            $roomAmount['sum'] += $taxAmount;
            $roomAmount['tax'] = $taxAmount;
        } else {
            $roomAmount['tax'] = 0;
        }

        return $roomAmount;
    }

    public function calcCheckinScTimes($hotel, $checkinDate)
    {
        $hotelCheckinStart = $checkinDate . ' ' . Carbon::parse($hotel->checkin_start)->format('H:i');
        $hotelCheckinEnd = $checkinDate . ' ' . Carbon::parse($hotel->checkin_end)->format('H:i');
        if (strtotime($hotelCheckinStart) >= strtotime($hotelCheckinEnd)) {
            $hotelCheckinEnd = Carbon::parse($hotelCheckinEnd)->modify('+1 day')->format('Y-m-d H:i');
        }

        $scTimesArr = [];
        $scTime = $hotelCheckinStart;
        $i = 0;
        while (strtotime($scTime) <= strtotime($hotelCheckinEnd)) {
            $scTimesArr[$i] = Carbon::parse($scTime)->format('H:i');
            $i++;
            $scTime = Carbon::parse($scTime)->modify('+1 hour')->format('Y-m-d H:i');
        }

        return $scTimesArr;
    }

    public function calcChildSum($ageNums)
    {
        $childSum = 0;
        foreach ($ageNums as $ages) {
            if (!empty($ages['child_num'])) {
                foreach ($ages['child_num'] as $childNum) {
                    $childSum += $childNum['num'];
                }
            }
        }
        return $childSum;
    }

    public function makePlanRoomsFromSessionData($bookingData)
    {
        $planRooms = [];
        $roomNumber = 1;
        foreach ($bookingData['selected_rooms'] as $key => $selectedRoom) {
            if ($key != 'plan_id') {
                $planId = $bookingData['selected_rooms']['plan_id'];
                $roomNum = $selectedRoom['room_num'];
                $ageNums = $bookingData['base_info']['age_nums'][$roomNum];
                $ageNums = $this->replaceChildNumKey($ageNums);
                $planRooms = $this->makePlanRoom($planRooms, $roomNumber, $ageNums, $planId, $selectedRoom['room_detail']);
                $roomNumber += 1;
            }
        }

        return json_decode(json_encode($planRooms));
    }

    public function replaceChildNumKey($ageNums)
    {
        $ageNums = collect($ageNums)
            ->transform(function ($age, $key) {
                if ($key == 'child_num') {
                    $age = collect($age)->transform(function ($a) {
                        $a['child_num'] = $a['num'];
                        unset($a['num']);
                        return $a;
                    })
                        ->toArray();

                    return $age;
                } else {
                    return $age;
                }
            })
            ->toArray();

        return $ageNums;
    }

    public function makePlanRoom($planRooms, $roomNumber, $ageNums, $planId, $roomDetail)
    {
        $planRoom = [];
        $planRoom['plan_id'] = $planId;
        $planRoom['room_type_id'] = $roomDetail['room_type_id'];
        $planRoom['room_number'] = $roomNumber;
        $planRoom['adult_num'] = $ageNums['adult_num'];
        $planRoom['child'] = !empty($ageNums['child_num']) ? $ageNums['child_num'] : [];
        unset($roomDetail['amount_breakdown']['amount']);
        $planRoom['amount_breakdown'] = $roomDetail['amount_breakdown'];
        $planRoom['amount'] = $roomDetail['amount'];

        array_push($planRooms, $planRoom);

        return $planRooms;
    }

    public function calcCheckoutDateTime($checkinDateTime, $stayTime)
    {
        $checkoutDateTime = Carbon::parse($checkinDateTime)->addHours($stayTime)->format('Y/m/d H:i');
        return $checkoutDateTime;
    }

    public function makeSavePostData($post, $bookingData, $hotel)
    {
        $hotelInStartTime = Carbon::parse($hotel->checkin_start)->format('H:i');
        $hotelInEndTime = Carbon::parse($hotel->checkin_end)->format('H:i');
        $hotelOutEndTime = Carbon::parse($hotel->checkout_end)->format('H:i');

        $selectedRooms = $bookingData['selected_rooms'];
        unset($selectedRooms['plan_id']);

        $reserveCode = $this->reserve_service->makeReserveCode();

        $post['lp_url_param'] = $bookingData['base_info']['url_param'];
        $post['room_num'] = count($selectedRooms);
        $post['payment_commission_rate'] = config('commission.payment_rate');
        $post['accommodation_price'] = $bookingData['room_amount']['sum'];
        $post['payment_commission_price'] =  $this->reserve_service->calcCommission($post['accommodation_price'], $post['payment_commission_rate']);
        $post['commission_rate'] = config('commission.reserve_rate');
        $post['commission_price'] = $this->reserve_service->calcCommission($post['accommodation_price'], $post['commission_rate']);
        $post['reservation_code'] = $reserveCode;
        $post['checkin_date'] = $bookingData['base_info']['in_out_date'][0];
        $post['checkin_start'] = $post['checkin_date'] . ' ' . $hotelInStartTime;
        $post['checkin_end'] =  $post['checkin_date'] . ' ' . $hotelInEndTime;
        $post['checkout_date'] = Carbon::parse(end($bookingData['base_info']['in_out_date']))->modify('+1 day')->format('Y-m-d');
        $post['checkout_end'] = $post['checkout_date'] . ' ' . $hotelOutEndTime;
        $post['reservation_status'] = 0;
        $post['adult_num'] = collect($bookingData['base_info']['age_nums'])->sum('adult_num');
        $post['child_num'] = $this->calcChildSum($bookingData['base_info']['age_nums']);
        $post['client_id'] = $hotel->client_id;
        $post['hotel_id'] = $bookingData['base_info']['hotel_id'];
        // $post['payment_method'] = !empty($bookingData['payment_method']) ? 1 : 0;
        $post['name'] = $post['first_name'] . $post['last_name'];

        return $post;
    }

    public function makeDayuseSavePostData($post, $bookingData, $reservation = NULL)
    {
        $post['lp_url_param'] = $bookingData['base_info']['url_param'];
        $post['checkin_date_time'] = $bookingData['base_info']['checkin_date_time'];
        $post['checkout_date_time'] = $bookingData['base_info']['checkout_date_time'];
        $post['payment_commission_rate'] = config('commission.payment_rate');
        $post['accommodation_price'] = $bookingData['room_amount']['sum'];
        $post['payment_commission_price'] =  $this->reserve_service->calcCommission($post['accommodation_price'], $post['payment_commission_rate']);
        $post['commission_rate'] = config('commission.reserve_rate');
        $post['commission_price'] = $this->reserve_service->calcCommission($post['accommodation_price'], $post['commission_rate']);
        $post['adult_num'] = collect($bookingData['base_info']['age_nums'])->sum('adult_num');
        $post['child_num'] = 0;
        $post['reservation_status'] = 0;
        $post['name'] = $post['first_name'] . $post['last_name'];
        foreach ($bookingData['base_info']['age_nums'] as $ageNums) {
            $childNum = 0;
            if (!empty($ageNums['child_num'])) {
                $childNum = collect($ageNums['child_num'])->sum('num');
            }
            $post['child_num'] += $childNum;
        }
        $post['reservation_status'] = 0;
        if (empty($reservation)) {
            $reserveCode = $this->reserve_service->makeReserveCode();
            $post['reservation_code'] = $reserveCode;
            $hotel = Hotel::find($bookingData['base_info']['hotel_id']);
            $post['hotel_id'] = $hotel->id;
            $post['client_id'] = $hotel->client_id;
        } else {
            $post['reservation_code'] = $reservation->reservation_code;
            $post['hotel_id'] = $reservation->id;
            $post['client_id'] = $reservation->client_id;
            $post['payment_method'] = $reservation->payment_method;
        }

        return $post;
    }

    // $planRoomsをreservation_branch_numごとにグループにする
    public function makeGroupBranchData($planRooms)
    {
        $planRoomPerBranch = collect($planRooms)
            ->groupBy('reservation_branch_num')
            ->toArray();

        return $planRoomPerBranch;
    }

    // $planRoomPerBranchをreservation_branchesレコードに整形する
    public function makeInsertBranchData($planRoomPerBranches, $reserveId, $planId, $priceDetail)
    {
        $insertBranchData = [];
        foreach ($planRoomPerBranches as $branchNum => $planRoomPerBranch) {
            // reservation_statusとtema_reservation_typeは、既存のreservation_branchesと比較して挿入する
            // 新規予約の際には、上記2つは、デフォルト値の0が入るようにする
            $insertBranchData[$branchNum]['reservation_id'] = $reserveId;
            $insertBranchData[$branchNum]['plan_id'] = $planId;
            $insertBranchData[$branchNum]['reservation_branch_num'] = $branchNum;
            $insertBranchData[$branchNum]['room_type_id'] = $planRoomPerBranch[0]->room_type_id;
            $insertBranchData[$branchNum]['accommodation_price'] = $priceDetail->$branchNum;
            $insertBranchData[$branchNum]['room_num'] = count($planRoomPerBranch);
            $insertBranchData[$branchNum]['reservation_date_time'] = now();
            $insertBranchData[$branchNum]['created_at'] = now();
        }

        return $insertBranchData;
    }

    // reservation_branchesレコードを保存する
    public function saveBranchData($planRoomPerBranches)
    {
        $branchNumIdMap = [];
        foreach ($planRoomPerBranches as $branchNum => $planRoomPerBranch) {
            DB::table('reservation_branches')->insert($planRoomPerBranch);
            $branchId = DB::getPdo()->lastInsertId();
            $branchNumIdMap[$branchNum]['branch_num'] = $branchNum;
            $branchNumIdMap[$branchNum]['branch_id'] = $branchId;
        }

        return $branchNumIdMap;
    }

    public function makeInsertPlanRooms($planRooms, $reserveId)
    {
        $insertPlanRooms = [];
        foreach ($planRooms as $planRoom) {
            $insertPlanRoomData = $this->reserve_service->makeInsertPlanRoomData($planRoom, $reserveId);
            $insertPlanRoomData = collect($insertPlanRoomData)
                ->transform(function ($reservePlan) use ($planRoom) {
                    $reservePlan['reservation_branch_num'] = $planRoom->reservation_branch_num;
                    return $reservePlan;
                })->toArray();
            $insertPlanRooms[] = $insertPlanRoomData;
        }

        return $insertPlanRooms;
    }

    public function savePlanRooms($planRooms, $reserveId, $hotel, $branchNumIdMap)
    {
        $reduceRoomStockData = [];
        foreach ($planRooms as $key => $planRoom) {
            // branchNumIdMapに該当する枝番号がない場合、そのbranchesは変更がないためスルーする
            if (empty($branchNumIdMap[$planRoom->reservation_branch_num]['branch_id'])) continue;

            $branchId = $branchNumIdMap[$planRoom->reservation_branch_num]['branch_id'];
            $insertPlanRoomData = $this->reserve_service->makeInsertPlanRoomData($planRoom, $reserveId, $branchId);
            $reservePlanIds = $this->reserve_service->insertPlanRoomData($insertPlanRoomData);

            $child = $planRoom->child;
            $child = collect($child)->transform(function ($c) {
                $c->num = $c->child_num;
                return $c;
            });
            $ages = $this->kids_policy_service->getKidsPolicy($child, $hotel->id);
            $kidsAmounts = $this->kids_policy_service->calcKidsAmount($ages, $planRoom->adult_1_amount, $planRoom->room_type_id, $planRoom->adult_num);
            $roomTypeChildNum = HotelRoomType::find($planRoom->room_type_id)->child_num;

            //下記を$reservePlanIdの数分回す
            foreach ($reservePlanIds as $reservePlanId) {
                $insertKidsPolicyData = $this->kids_policy_service->makeReserveKidsPolicyData($kidsAmounts, $roomTypeChildNum, $reservePlanId);
                $res = $this->reserve_service->insertReserveKidsPolicy($insertKidsPolicyData);
            }
            // 在庫を減らすデータ
            $reduceRoomStockData = $this->reserve_service->makeReduceRoomStockData($reduceRoomStockData, $planRoom->amount_breakdown);
        }
        // 実際に在庫を減らす
        $result = $this->reserve_service->ReserveReduceRoomStock($reduceRoomStockData, $hotel->client_id, $hotel->id);

        return $result;
    }

    public function mergeRoomStocks($stayAbleRooms, $roomStockMap)
    {
        foreach ($stayAbleRooms as &$stayAbleRoom) {
            $stockData = $roomStockMap[$stayAbleRoom['room_type_id']];
            $insertStockData = [];
            foreach ($stockData as $date => $data) {
                $insertStockData[$date] = $data['date_stock_num'];
            }
            $stayAbleRoom['date_stock_nums'] = $insertStockData;

            unset(
                $stayAbleRoom['date_stock_num'],
                $stayAbleRoom['date']
            );
        }

        return $stayAbleRooms;
    }

    public function checkSelectedRoomStock($stockData, $reduceNum)
    {
        $isInStock = collect($stockData)
            ->map(function ($stockNum) use ($reduceNum) {
                return $stockNum - $reduceNum;
            })
            ->reject(function ($stockNum) {
                return $stockNum > 0;
            })
            ->isEmpty();

        # true: 在庫がまだある, false: 在庫がもうない
        return $isInStock;
    }

    public function checkCancelRoomStock($stockData, $increaseNum)
    {
        $isInStock = collect($stockData)
            ->map(function ($stockNum) use ($increaseNum) {
                return $stockNum + $increaseNum;
            })
            ->reject(function ($stockNum) {
                return $stockNum <= 0;
            })
            ->isEmpty();

        # true: 在庫がまだある, false: 在庫がもうない
        return $isInStock;
    }

    public function makeSwitchRoomTokens($searchedRooms, $currentRoomNum, $roomTypeId)
    {
        unset($searchedRooms['plan_id']);

        $roomTokens = [];
        foreach ($searchedRooms as $roomToken => $room) {
            if (
                $currentRoomNum != $room['room_num'] &&
                $room['room_detail']['room_type_id'] == $roomTypeId
            ) {

                $roomTokens[] = $roomToken;
            }
        }

        return $roomTokens;
    }

    public function makeSelectedRoomNums($selectedRooms)
    {
        unset($selectedRooms['plan_id']);

        $roomNums = [];
        foreach ($selectedRooms as $roomToken => $room) {
            $roomNums[] = 'roomList__' . $room['room_num'];
        }

        return $roomNums;
    }

    public function makeReservationSaveData(array $params, array $bookingData, Hotel $hotel, string $checkinDate, $form): array
    {
        $hotelClientId = $hotel->client_id;
        $hotelInStartTime = Carbon::parse($hotel->checkin_start)->format('H:i');

        // checkin_endが日付を跨いでいる場合の整形処理
        $ret = $this->formatStraddleTime($hotel->checkin_end);
        $day = $ret['day'];
        $hotelInEndTime = $ret['time'];
        $checkinEndDate = Carbon::parse($checkinDate)->addDays($day ?? 0)->format('Y-m-d');

        // checkout_endが日付を跨いでいる場合の整形処理
        $ret = $this->formatStraddleTime($hotel->checkout_end);
        $day = $ret['day'];
        $hotelOutEndTime = $ret['time'];
        $checkoutDate = Carbon::parse($checkinDate)->addDays($day ?? 0)->format('Y-m-d');

        // checkout_timeが日付を跨いでいる場合の整形処理
        list($date, $time) = explode(' ', $params['checkout_time']);
        $ret = $this->formatStraddleTime($time);
        $day = $ret['day'];
        $checkoutTime = $ret['time'];
        if ($day > 0) {
            $checkoutTimeDate = Carbon::parse($date)->addDays($day)->format('Y-m-d');
            $checkoutTime = sprintf('%s %s', $checkoutTimeDate, $checkoutTime);
        } else {
            $checkoutTime = $params['checkout_time'];
        }

        $selectedRooms = $bookingData['selected_rooms'];
        $paymentMethod = !empty($params['payment_method']) ? 1 : 0;
        $accommodationPrice = $bookingData['room_amount']['sum'];
        $commissionRate = config('commission.reserve_rate');
        $paymentCommissionRate = config('commission.payment_rate');
        $reservationDate = now()->format('Y-m-d H:i:s');
        $reserveCode = $this->reserve_service->makeReserveCode();
        return [
            // 予約コード
            'reservation_code' => $reserveCode,
            // 予約ステータス
            'reservation_status' => 0,
            // 施設のクライアントID
            'client_id' => $hotelClientId,
            // 施設ID
            'hotel_id' => $bookingData['base_info']['hotel_id'],
            // data_type=8の入力欄の値
            'name' => $params['full_name'] ?? '',
            // 予約日 + hotels.checkin_start
            'checkin_start' => $checkinDate . ' ' . $hotelInStartTime,
            // 予約日 + hotels.checkin_end
            'checkin_end' => $checkinEndDate . ' ' . $hotelInEndTime,
            // 予約日 + hotels.checkout_end
            'checkout_end' => $checkoutDate . ' ' . $hotelOutEndTime,
            // 予約日 + reservation_blocks.start_time
            'checkin_time' => $params['checkin_time'],
            // 予約日 + reservation_blocks.end_time
            'checkout_time' => $checkoutTime,
            // data_type=10の入力欄の値
            'email' => $params['email'] ?? '',
            // data_type=9の入力欄の値
            'tel' => $params['tel'] ?? '',
            // data_type=11の入力欄の値
            'address' => $params['address'] ?? '',
            // 部屋数
            'room_num' => count($selectedRooms),
            // 利用人数
            'adult_num' => array_sum(array_column($bookingData['selected_rooms'], 'person_num')),
            // 0: 現地決済, 1:事前決済
            'payment_method' => $paymentMethod,
            // 利用料金
            'accommodation_price' => $accommodationPrice,
            // 施設の売上手数料率
            'commission_rate' => $commissionRate,
            // 施設の売上手数料
            'commission_price' => $this->reserve_service->calcCommission($accommodationPrice, $commissionRate),
            // accommodation_priceに対する決済手数料率
            'payment_commission_rate' => $paymentCommissionRate,
            // accommodation_priceに対する決済手数料
            'payment_commission_price' => $this->reserve_service->calcCommission($accommodationPrice, $paymentCommissionRate),
            // 実際に予約された日時
            'reservation_date' => $reservationDate,
            // 予約のあったLPのurl_param
            'lp_url_param' => $bookingData['base_info']['url_param'],
            // Stripe決済処理時のcustomer_id
            'stripe_customer_id' => $paymentMethod == 1 ? '' : '',
            // Stripe決済処理時のcharge_id
            'stripe_payment_id' => $paymentMethod == 1 ? '' : '',
			'is_request' => $form->is_request_reservation,
		];
	}

    private function formatStraddleTime(?string $targetTime): array
    {
        $hourLimit = 24;
        $explodeTarget = explode(':', $targetTime ?? Carbon::now()->format('H:i'));
        $targetHour = intval($explodeTarget[0]);
        if ($targetHour >= $hourLimit) {
            $day = floor($targetHour / $hourLimit);
            $hour = $targetHour % $hourLimit;
            return [
                'day' => $day,
                'time' => sprintf('%02d:%02d', $hour, $explodeTarget[1])
            ];
        }
        return [
            'day' => 0,
            'time' => Carbon::parse($targetTime)->format('H:i')
        ];
    }

    public function formatCheckInOutTime(array $baseCustomerItems, array $params)
    {
        foreach ($baseCustomerItems as $idx => $item) {
            // 予約時の入力項目でない場合
            if ($item['is_reservation_item'] != 1) {
                continue;
            }
            switch ($item['data_type']) {
                case 14: // 予約開始時間
                case 15: // 予約終了時間
                    $itemId = 'item_' . $item['id'];
                    $hour = $params[$itemId . '_hour'] ?? '';
                    $minute = $params[$itemId . '_minute'] ?? '';
                    if (!empty($hour) && !empty($minute)) {
                        $params['item_' . $item['id']] = sprintf('%02d:%02d', $hour, $minute);
                    }
                    break;
            }
        }
        return $params;
    }

    public function createCarbonTimeOver24Hour(string $targetDate, string $targetTime): Carbon
    {
        $explodeTarget = explode(':', $targetTime);
        return Carbon::createFromFormat('Y-m-d H:i', "{$targetDate} {$explodeTarget[0]}:{$explodeTarget[1]}");
    }
}
