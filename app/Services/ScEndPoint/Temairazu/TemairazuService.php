<?php

namespace App\Services\ScEndPoint\Temairazu;

use App\Models\Plan;
use App\Models\RoomStock;
use App\Models\PlanRoomTypeRate;
use App\Models\PlanRoomTypeRatePerClass;
use App\Models\Reservation;
use App\Jobs\Temairazu\ReservationNotification;
use Carbon\Carbon;

class TemairazuService
{
    private static $instance = null;

    public static function instance(): TemairazuService
    {
        if (self::$instance !== null) {
            return self::$instance;
        }
        $instance = new self();
        self::$instance = $instance;
        return $instance;
    }

    public function getRooms($hotel)
    {
        $rooms = $hotel->hotelRoomTypes;
        $data = [];
        foreach ($rooms as $room) {
            $data[] = [
                'code' => $room->id,        //部屋コード
                'name' => $room->name,      //部屋名称
            ];
        }

        return $data;
    }

    public function getPlans($hotel, $room)
    {
        $plans = Plan::where('hotel_id', $hotel->id)
            ->whereJsonContains('room_type_ids', $room->id)
            ->get();
        $data = [];
        foreach ($plans as $plan) {
            $row = [
                'code' => $plan->id,                //プランコード
                'name' => $plan->name,              //プラン名称
                'offer_period' => '',               //提供期間
                'status' => $plan->temaStatus(),    //プランアクティブフラグ 0:休止中 1:アクティブ 2:非更新
                'rate_type' => 1,                   //料金体系 0:人数課金 1:部屋課金 RC
            ];
            $maxNum = min($room->adult_num, 6);
            for ($i = 1; $i <= $maxNum; $i++) {
                $row['rc' . $i] = 'RC'. $i;
            }

            $data[] = $row;
        }

        return $data;
    }

    public function getStocks($hotel, $room, $startDate, $endDate)
    {
        //当日が2019/05/20の時　2019/05/01～2019/05/31の在庫情報をリクエスト
        //2019/05/20～2019/05/31までの庫情報をレスポンスする
        $today = Carbon::today();
        if ($startDate->month == $today->month) {
            if ($endDate->lt($today)) {
                //2019/05/01～2019/05/19 -> empty
                return [];
            }
            if ($startDate->lt($today)) {
                //2019/05/01～2019/05/31 -> 2019/05/20～2019/05/31
                $startDate = $today;
            }
        }

        $start = $startDate->format('Y/m/d');
        $end = $endDate->format('Y/m/d');
        $stocks = RoomStock::where('hotel_id', $hotel->id)
            ->where('hotel_room_type_id', $room->id)
            ->whereBetween('date', [$start, $end])
            ->orderBy('date', 'ASC')
            ->get();
        $stocksByDate = [];
        foreach ($stocks as $stock) {
            $date = $stock->date->format('Y-m-d');
            $stocksByDate[$date] = $stock;
        }
        $data = [];
        $row = [
            'room_code' => $room->id,                   //部屋コード
            'start' => $start,                          //取得対象開始日
            'end' => $end,                              //取得対象終了日
            'room_status' => $room->sale_condition,     //部屋状態 0:販売中 1:停止中
        ];
        $date = $startDate->copy();
        $i = 1;
        while ($date->lte($endDate)) {
            $stock = $stocksByDate[$date->format('Y-m-d')] ?? null;
            if ($stock) {
                $row['status_' . $i] = $stock->date_sale_condition == 1 ? 'CL' : 'OP';  //dd 日状態
                $row['stock_num_' . $i] = $stock->date_stock_num;                       //dd 日在庫数
                $row['reserve_num_' . $i] = $stock->date_reserve_num;                   //dd 日予約数
            } else {
                $row['status_' . $i] = 'OP';
                $row['stock_num_' . $i] = 0;
                $row['reserve_num_' . $i] = 0;
            }
            $date->addDay();
            $i++;
        }
        $data[] = $row;

        return $data;
    }

    public function getPlanRates($hotel, $room, $plan, $startDate, $endDate)
    {
        //当日が2019/05/20の時　2019/05/01～2019/05/31の料金情報をリクエスト
        //2019/05/20～2019/05/31までの料金情報をレスポンスする
        $today = Carbon::today();
        if ($startDate->month == $today->month) {
            if ($endDate->lt($today)) {
                //2019/05/01～2019/05/19 -> empty
                return [];
            }
            if ($startDate->lt($today)) {
                //2019/05/01～2019/05/31 -> 2019/05/20～2019/05/31
                $startDate = $today;
            }
        }

        $start = $startDate->format('Y/m/d');
        $end = $endDate->format('Y/m/d');
        $planRates = PlanRoomTypeRate::with(['perClasses' => function($q) {
            $q->orderBy('class_person_num', 'ASC');
        }])->where('hotel_id', $hotel->id)
            ->where('room_type_id', $room->id)
            ->where('plan_id', $plan->id)
            ->whereBetween('date', [$start, $end])
            ->orderBy('date', 'ASC')
            ->get();
        $planRatesByDate = [];
        foreach ($planRates as $rate) {
            $date = $rate->date->format('Y-m-d');
            $planRatesByDate[$date] = $rate;
        }
        $data = [];
        $row = [
            'room_code' => $room->id,                    //部屋コード
            'plan_code' => $plan->id,                    //プランコード
            'start' => $start,                           //取得対象開始日
            'end' => $end,                               //取得対象終了日
            'plan_status' => $plan->public_status,       //プラン状態 0:販売中 1:停止中
            'rate_type' => 1,                            //料金体系 0:人数課金 1:部屋課金 RC
        ];
        $date = $startDate->copy();
        $i = 1;
        while ($date->lte($endDate)) {
            $dt = $date->format('Y-m-d');
            $rate = $planRatesByDate[$dt] ?? null;
            if ($rate) {
                if ($rate->perClasses->isEmpty()) {
                    throw new TemairazuDataException('[404]' . $dt . '日料金取得');
                }
                $row['status_' . $i] = $rate->date_sale_condition == 1 ? 'CL' : 'OP';   //dd 日状態
                foreach ($rate->perClasses as $key => $class) {
                    $row['person_num_' . $i . '_' . $key] = 'RC' . $class->class_person_num;   //dd 日区分 X(名利用)
                    $row['amount_' . $i . '_' . $key] = $class->class_amount;           //dd 日料金
                }
            } else {
                $row['status_' . $i] = 'OP';
                $row['person_num_' . $i . '_0'] = 0;
                $row['amount_' . $i . '_0'] = 0;
            }
            $date->addDay();
            $i++;
        }
        $data[] = $row;

        return $data;
    }

    public function getReservations($hotel, $filterType, $params = [])
    {
        if ($filterType == 0) {
            //予約番号
            $reservationCode = $params['reservationCode'];
            $reservations = Reservation::with(['reservationBranches' => function($q) {
                $q->orderBy('reservation_id', 'ASC')
                    ->orderBy('reservation_branch_num', 'ASC')
                    ->orderBy('id', 'ASC');
            }])->where('hotel_id', $hotel->id)
                ->where('reservation_code', $reservationCode)
                ->get();
        } elseif ($filterType == 1) {
            //宿泊日
            //※チェックアウト日は「開始(DayStart)」「終了(DayEnd)」の検索期間に含めない
            $startDate = $params['startDate'];
            $endDate = $params['endDate'];
            $start = $startDate->format('Y/m/d');
            $end = $endDate->format('Y/m/d');
            $reservations = Reservation::with(['reservationBranches' => function($q) {
                $q->orderBy('reservation_id', 'ASC')
                    ->orderBy('reservation_branch_num', 'ASC')
                    ->orderBy('id', 'ASC');
            }])->where('hotel_id', $hotel->id)
                ->whereHas('reservationPlans', function($q) use ($start, $end) {
                    $q->whereBetween('date', [$start, $end]);
                })
                ->orderBy('id', 'ASC')
                ->get();
        } elseif ($filterType == 2) {
            //受付日
            //新規:      予約日時       [branches]reservation_date_time(dateTime)
            //変更:      変更日時       [branches]change_date_time(dateTime)
            //キャンセル: キャンセル日時  [branches]cancel_date_time(dateTime)
            $startDate = $params['startDate'];
            $endDate = $params['endDate'];
            $start = $startDate->format('Y/m/d 00:00:00');
            $end = $endDate->format('Y/m/d 23:59:59');
            $reservations = Reservation::with(['reservationBranches' => function($q) {
                $q->orderBy('reservation_id', 'ASC')
                    ->orderBy('reservation_branch_num', 'ASC')
                    ->orderBy('id', 'ASC');
            }])->where('hotel_id', $hotel->id)
                ->whereHas('reservationBranches', function($q) use ($start, $end) {
                    $q->where(function($q) use ($start, $end) {
                        //キャンセル
                        $q->whereIn('reservation_status', [1, 2])
                            ->whereBetween('cancel_date_time', [$start, $end]);
                    })->orWhere(function($q) use ($start, $end) {
                        //変更
                        $q->where('reservation_status', 0)
                            ->where('tema_reservation_type', 1)
                            ->whereBetween('change_date_time', [$start, $end]);
                    })->orWhere(function($q) use ($start, $end) {
                        //新規
                        $q->where('reservation_status', 0)
                            ->whereBetween('reservation_date_time', [$start, $end]);
                    });
                })
                ->orderBy('id', 'ASC')
                ->get();
        } else {
            return [];
        }

        $data = [];
        foreach ($reservations as $reservation) {
            
            $branches = $this->getReservationBranches($reservation);

            //$branchCount = count($branches);
            foreach ($branches as $branchNum => $branch) {
                $c00 = $reservation->reservation_code;
                //予約番号枝番, ALLWAYS(CHANGED 2021-06-30)
                $c00 .= '-' . $branchNum;
                $row = [
                    'c00' => $c00,                                                              //予約番号
                    'c01' => $branch['type'],                                                   //予約種別  B:予約 M:変更 C:キャンセル
                    'c02' => $reservation->name,                                                //宿泊者
                    'c03' => $reservation->name_kana,                                           //ふりがな
                    'c04' => safeDateFormat($reservation->checkin_time, 'Y/m/d'),               //チェックイン日
                    'c05' => safeDateFormat($reservation->checkout_time, 'Y/m/d'),              //チェックアウト日,
                    'c06' => $branch['room_num'],                                               //部屋数
                    'c07' => $branch['adult_num'],                                              //大人人数
                    'c08' => $branch['child_num'],                                              //子供人数
                    'c09' => $branch['price'],                                                  //宿泊料金
                    'c10' => '',                                                                //連絡先
                    'c11' => $reservation->email,                                               //メールアドレス
                    'c12' => $branch['roomTypeId'],                                             //部屋コード room code
                    'c13' => $branch['planId'],                                                 //プランコード plan code
                    'c14' => safeDateFormat($branch['reservationDateTime'], 'Y/m/d H:i:s'),     //予約日時
                    'c15' => safeDateFormat($branch['cancelDateTime'], 'Y/m/d H:i:s'),          //キャンセル日時
                    'c16' => $reservation->tel,                                                 //TEL
                    'c17' => $reservation->address,                                             //住所
                    'c18' => safeDateFormat($reservation->checkin_time, 'H:i'),                 //チェックイン時刻
                    'c19' => '',                                                                //宿泊者 Fax
                    'c20' => '',                                                                //宿泊者連絡先
                    'c21' => $branch['adult_num'],                                              //男人数
                    'c22' => 0,                                                                 //女人数
                    'c23' => $this->getReservationDetails($reservation, $branch),               //予約詳細 ※
                    'c24' => $reservation->special_request,                                     //備考
                    'c25' => '',                                                                //会員氏名
                    'c26' => '',                                                                //会員かな
                    'c27' => '',                                                                //会員住所
                    'c28' => '',                                                                //会員 TEL
                    'c29' => '',                                                                //会員 FAX
                    'c30' => '',                                                                //会員メール
                    'c31' => '',                                                                //会員年齢
                    'c32' => '',                                                                //会員性別
                    'c33' => '',                                                                //会員会社
                    'c34' => '',                                                                //会員会社住所
                    'c35' => '',                                                                //会員会社 TEL
                    'c36' => '',                                                                //会員会社 FAX
                    'c37' => '',                                                                //付与ポイント
                    'c38' => '',                                                                //使用ポイント
                    'c39' => $reservation->payment_method,                                      //決済区分, 0:現地決済 1:事前決済 2:事後決済
                    'c40' => $branch['price'],                                                  //請求料金
                    'c41' => $this->getResnRoomDetails($reservation, $branch),                  //部屋毎情報 ※
                    'c42' => 1,                                                                 //在庫連動フラグ 0:連動なし 1:連動あり
                ];

                $sortType = $branch['type'] == 'C' ? 1 : 2;
                if ($branch['type'] == 'C') {
                    $sortDateTime = safeDateFormat($branch['cancelDateTime'], 'Y/m/d H:i:s');       //キャンセル日時
                } elseif ($branch['type'] == 'M') {
                    $sortDateTime = safeDateFormat($branch['changeDateTime'], 'Y/m/d H:i:s');       //変更日時
                } else {
                    $sortDateTime = safeDateFormat($branch['reservationDateTime'], 'Y/m/d H:i:s');  //予約日時
                }
                
                $data[] =  [
                    'sortType' => $sortType,
                    'sortDateTime' => $sortDateTime,
                    'row' => $row
                ];
            }
        }

        //「キャンセル予約の発生順」＞「新規予約又は変更予約の発生順」
        // キャンセル日時|変更日時|予約日時（古い順）
        return collect($data)->sortBy('sortDateTime')->sortBy('sortType')->pluck('row')->toArray();
    }

    public function updateStocks($hotel, $room, $data)
    {
        //指定年月 yyyy/mm
        $yearMonth = $data['Nengetsu'];
        $startDate = Carbon::parse($yearMonth . '/01');
        $endDate = $startDate->copy()->endOfMonth();
        $date = $startDate->copy();

        //在庫登録APIでも、当日以前のデータが更新されますが、更新されるのは当日以降だけ
        $today = Carbon::today();
        if ($endDate->lt($today)) {
            return;
        }
        if ($date->lt($today)) {
            $date = $today;
        }

        $insertOrUpdateData = [];
        $columns = [
            'client_id', 'hotel_id', 'hotel_room_type_id', 'date',
            'date_stock_num', 'date_sale_condition', 'created_at', 'updated_at',
        ];
        $updateGiveUp = [];
        $giveUpDates = [];
        while ($date->lte($endDate)) {
            $k = 'Zaiko' . $date->format('d');
            if (isset($data[$k])) {
                $dt = $date->format('Y-m-d');
                //  -999 first time will set 0, 
                // otherwise keep the original value
                if ($data[$k] == -999) {
                    $dateStockNum = 0;
                    $dateSaleCondition = 1;
                    $giveUpDates[] = "'" . $dt . "'";
                } else {
                    $dateStockNum = $data[$k];
                    $dateSaleCondition = 0;
                }
                $row = [
                    'client_id' => $hotel->client_id,
                    'hotel_id' => $hotel->id,
                    'hotel_room_type_id' => $room->id,
                    'date' => $dt,
                    'date_stock_num' => $dateStockNum,
                    'date_sale_condition' => $dateSaleCondition,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $insertOrUpdateData[] = $row;
            }
            $date->addDay();
        }

        if (!empty($giveUpDates)) {
            $dts = implode(',', $giveUpDates);
            $updateGiveUp['date_stock_num'] = "date IN ({$dts})";
        }

        $roomStock = new RoomStock();
        $roomStock->batchInsertOrUpdate($insertOrUpdateData, $columns, $updateGiveUp);
    }

    public function updatePlanRates($hotel, $room, $plan, $data)
    {
        $insertOrUpdateData = [];
        $columns = [
            'plan_room_type_rate_id', 'class_type', 'class_person_num', 'class_amount',
            'created_at', 'updated_at',
        ];

        //年月 yyyy/mm
        $yearMonth = $data['Mon'];
        $startDate = Carbon::parse($yearMonth . '/01');
        $endDate = $startDate->copy()->endOfMonth();
        $date = $startDate->copy();

        //料金登録APIでも、当日以前のデータが更新されますが、更新されるのは当日以降だけ
        $today = Carbon::today();
        if ($endDate->lt($today)) {
            return;
        }
        if ($date->lt($today)) {
            $date = $today;
        }

        $planRoomTypeRatePerClass = new PlanRoomTypeRatePerClass();

        while ($date->lte($endDate)) {
            $d = $date->format('d');
            $hasDate = false;
            for ($i = 1; $i <= 6; $i++) {
                $k = "Kubun{$i}_$d";
                $r = "Ryokin{$i}_$d";
                if (isset($data[$k]) && isset($data[$r])) {
                    $hasDate = true;
                    break;
                }
            }
            if ($hasDate) {
                $dateSaleCondition = 1;
                for ($i = 1; $i <= 6; $i++) {
                    $k = "Kubun{$i}_$d";
                    $r = "Ryokin{$i}_$d";
                    if (isset($data[$k]) && isset($data[$r])) {
                        if ($data[$r] != 0) {
                            $dateSaleCondition = 0;
                            break;
                        }
                    }
                }
                $planRoomTypeRate = PlanRoomTypeRate::updateOrCreate(
                    [
                        'client_id' => $hotel->client_id,
                        'hotel_id' => $hotel->id,
                        'room_type_id' => $room->id,
                        'plan_id' => $plan->id,
                        'date' => $date->format('Y-m-d'),
                    ],
                    [
                        'date_sale_condition' => $dateSaleCondition,
                        'updated_at' => now(),
                    ]
                );
                $insertOrUpdateData = [];
                for ($i = 1; $i <= 6; $i++) {
                    $k = "Kubun{$i}_$d";
                    $r = "Ryokin{$i}_$d";
                    if (isset($data[$k]) && isset($data[$r])) {
                        if ($data[$k] == 'RC') {

                        } else {
                            //$data[$k] = 1,2,3 or RC1,RC2,RC3
                            $row = [
                                'plan_room_type_rate_id' => $planRoomTypeRate->id,
                                'class_type' => 2,
                                'class_person_num' => str_ireplace('RC', '', $data[$k]),
                                'class_amount' => $data[$r],
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                            $insertOrUpdateData[] = $row;
                        }
                    }
                }
                if (!empty($insertOrUpdateData)) {
                    $planRoomTypeRatePerClass->batchInsertOrUpdate($insertOrUpdateData, $columns);
                }
            }

            $date->addDay();
        }
    }

    public function sendReservationNotification($clientId, $reservationId)
    {
        dispatch(new ReservationNotification($clientId, $reservationId))->onQueue('temairazu-notification');
    }

    protected function getReservationBranches($reservation)
    {
        $data = [];
        foreach ($reservation->reservationBranches as $item) {
            $branchNum = $item->reservation_branch_num;
            if (!isset($data[$branchNum])) {
                $type = 'B';
                if ($item->reservation_status == 1 || $item->reservation_status == 2) {
                    $type = 'C';
                } else {
                    if ($item->tema_reservation_type == 1) {
                        $type = 'M';
                    }
                }
                $data[$branchNum] = [
                    'branchNum' => $branchNum,
                    'planId' => $item->plan_id,
                    'roomTypeId' => $item->room_type_id,
                    'type' => $type,
                    'price' => $item->accommodation_price ?? 0,
                    'reservationDateTime' => $item->reservation_date_time,
                    'changeDateTime' => $item->change_date_time,
                    'cancelDateTime' => $item->cancel_date_time,
                    'items' => [],
                    'room_num' => $item->room_num,
                    'adult_num' => 0,
                    'child_num' => 0,
                    'data' => $item,
                ];
            }

            $dtData = [];
            foreach ($item->reservationPlan as $row) {
                $dt = $row->date->format('Y/m/d');
                if (!isset($data[$branchNum]['items'][$dt])) {
                    $data[$branchNum]['items'][$dt] = [];
                }
                $data[$branchNum]['items'][$dt][] = $row;

                if (!isset($dtData[$dt])) {
                    $dtData[$dt] = [
                        'adult_num' => $row->adult_num,
                        'child_num' => $row->child_num,
                    ];
                } else {
                    $dtData[$dt]['adult_num'] += $row->adult_num;
                    $dtData[$dt]['child_num'] += $row->child_num;
                }
            }

            foreach ($dtData as $dtNums) {
                $data[$branchNum]['adult_num'] = max($data[$branchNum]['adult_num'], $dtNums['adult_num']);
                $data[$branchNum]['child_num'] = max($data[$branchNum]['child_num'], $dtNums['child_num']);
            }
        }

        // foreach ($data as $branchNum => $branch) {
        //     $roomNum = 0;
        //     foreach ($branch['items'] as $dt => $items) {
        //         $roomNum = max($roomNum, count($items));
        //     }
        //     $data[$branchNum]['room_num'] = $roomNum;
        // }

        return $data;
    }

    //予約詳細 ※
    protected function getReservationDetails($reservation, $branch)
    {
        $data = [];

        $data[] = '【料金内訳】';
        $index = 1;
        $roomNos = [];
        foreach ($branch['items'] as $dt => $items) {
            $data[] = "{$dt}[{$index}泊目]";
            $dayAmount = 0;
            //室目/部屋番号
            $roomNo = 1;
            foreach ($items as $k => $item) {
                $childOtherNum = collect($item->kidsPolicies)->sum('child_num');
                $childANum = $item->child_num - $childOtherNum;
                $data[] = "{$roomNo}室目";
                $data[] = "大人{$item->adult_num}名（男性{$item->adult_num}名 女性0名）×0円";
                $data[] = "子供A{$childANum}名×0円";
                $data[] = "子供B0名×0円";
                $data[] = "子供C0名×0円";
                $data[] = "子供D0名×0円";
                $data[] = "子供その他{$childOtherNum}名×0円";
                $data[] = "小計{$item->amount}円";
                $dayAmount += $item->amount;
                if (!isset($roomNos[$roomNo])) {
                    $roomNos[$roomNo] = $roomNo;
                }
                $roomNo++;
            }
            $data[] = "合計{$dayAmount}円";
            $index++;
        }

        $data[] = '【部屋別代表者】';
        $body = [];
        foreach ($roomNos as $roomNo) {
            $data[] = "{$roomNo}室目 {$reservation->name}（{$reservation->name_kana}）";
        }

        return $data;
    }

    //部屋毎情報 ※
    protected function getResnRoomDetails($reservation, $branch)
    {
        $data = [];

        $index = 1;
        foreach ($branch['items'] as $dt => $items) {
            //室目/部屋番号
            $roomNo = 1;
            foreach ($items as $k => $item) {
                $childOtherNum = collect($item->kidsPolicies)->sum('child_num');
                $childANum = $item->child_num - $childOtherNum;
                $row = [];
                $row[] = $dt;                       //利用年月日 [yyyy/mm/dd 形式]
                $row[] = $roomNo;                   //部屋番号
                $row[] = 1;                         //料金体系
                $row[] = $item->adult_num;          //利用人数
                $row[] = $item->adult_num;          //大人人員（男性）
                $row[] = 0;                         //大人一人料金（男性）
                $row[] = 0;                         //大人人員（女性）
                $row[] = 0;                         //大人一人料金（女性）
                $row[] = $childANum;                //子供 A 人数
                $row[] = 0;                         //子供 A 一人料金
                $row[] = 0;                         //子供 A2 人数
                $row[] = 0;                         //子供 A2 一人料金
                $row[] = 0;                         //子供 B 人数
                $row[] = 0;                         //子供 B 一人料金
                $row[] = 0;                         //子供 B2 人数
                $row[] = 0;                         //子供 B2 一人料金
                $row[] = 0;                         //子供 C 人数
                $row[] = 0;                         //子供 C 一人料金
                $row[] = 0;                         //子供 D 人数
                $row[] = 0;                         //子供 D 一人料金
                $row[] = $childOtherNum;            //子供その他人数
                $row[] = 0;                         //子供その他一人料金
                $row[] = $item->amount;             //X 室あたり宿泊料金合計
                $roomNo++;

                $data[] = implode(',', $row);
            }
            $index++;
        }

        return $data;
    }
}
