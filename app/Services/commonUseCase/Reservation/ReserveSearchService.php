<?php
namespace App\Services\commonUseCase\Reservation;

use Carbon\Carbon;
use App\Models\HotelRoomType;
use App\Models\Reservation;
use App\Models\HotelRoomTypeBed;
use App\Models\HotelRoomTypeImage;
use App\Models\Plan;
use App\Models\Lp;
use App\Models\HotelKidsPolicy;
use App\Models\PlanRoomTypeRate;


class ReserveSearchService
{

    public function __construct()
    {
        $this->convert_cancel_service = app()->make('ConvertCancelPolicyService');
    }

    // lpのlp_paramから、lpを取得する
    public function getLPfromParam($lpParam)
    {
        $lp = Lp::where('url_param', $lpParam)->first();
        return $lp;
    }

    // lpのurl_paramから、formのidを取得する
    public function getFormFromLpParam($lpParam)
    {
        $lpQuery = Lp::query();
        $lpQuery = $this->selectParam($lpQuery, 'form_id');
        $lpQuery = $this->selectParam($lpQuery, 'hotel_id');
        $lpQuery = $this->selectParam($lpQuery, 'title');
        $lpQuery->where('url_param', $lpParam);
        $targetLp = $lpQuery->first();
        $param = [];
        if (!empty($targetLp)) {
            $param = $targetLp->toArray();
        }

        return $param;
    }

    private function selectParam($query, $column)
    {
        return $query->addSelect($column);
    }

    public function getStayAblePlans($planIds, $nights, $stayType, $inOutDate=[])
    {
        if (is_null($stayType)) {
            $stayAblePlanQuery = Plan::with('cancelPolicy')
                                ->whereIn('plans.id', $planIds)
                                ->where('public_status', 1);
        } else {
            $stayAblePlanQuery = Plan::with('cancelPolicy')
                                ->whereIn('plans.id', $planIds)
                                ->where('stay_type', $stayType)
                                ->where('public_status', 1);
        }

        $stayAblePlan = $stayAblePlanQuery
                        ->get()
                        // 最低宿泊日数を満たすかチェック
                        ->reject(function($plan) use($nights, $inOutDate) {
                            if ($plan->is_new_plan && !empty($inOutDate)) {
                                if(empty($plan->rates)) return true;

                                // ratesのリレーションで対象の日付のレコードが全て揃っていなかったreject
                                $rateCheck = $this->isNullDate($plan, $inOutDate);
                                if ($rateCheck) return true;
                            } elseif ($plan->is_new_plan == 0) {
                                $basePlan = Plan::find($plan->existing_plan_id);
                                $rateCheck = $this->isNullDate($basePlan, $inOutDate);
                                if ($rateCheck) return true;
                            }

                            if ($nights) return ($plan->is_min_stay_days && $nights < $plan->min_stay_days);
                        })
                        ->reject(function($plan) use($nights) {
                            if ($nights) return ($plan->is_max_stay_days && $nights > $plan->max_stay_days);
                        })
                        ->transform(function($plan){
                            unset($plan->rates);
                            if (empty($plan->cover_image)) {
                                $plan->cover_image = asset('static/common/images/no_image.png');
                            }
                            return $plan;
                        })
                        ->sortBy('sort_num')
                        ->toArray();

        # Todo:
        # ✔最低宿泊日数の制限
        # ✔︎キャンセルポリシーのjoin
        # ✔︎sort_numによる並び順
        # ✔︎planのカバー画像

        return $stayAblePlan;
    }

    public function isNullDate($plan, $inOutDate)
    {
        foreach ($inOutDate as $date) {
            $date .= ' 00:00:00';
            $check = $plan->rates->where('date', $date)->isEmpty();
            if ($check) return true;
        }

        return false;
    }

    public function getStayAbleRooms($roomTypeIds, $roomTypeRcs, $inOutDate)
    {
        $roomQuery = HotelRoomType::query();
        $roomQuery = $roomQuery->select('name', 'sort_num', 'hotel_room_type_id as room_type_id', 'date', 'adult_num', 'child_num', 'room_size', 'room_stocks.date_stock_num')
                               ->join('room_stocks', 'room_stocks.hotel_room_type_id', '=', 'hotel_room_types.id')
                               ->whereIn('hotel_room_types.id', $roomTypeIds)
                               ->where('room_stocks.date_stock_num', '>',  0)
                               ->where('room_stocks.date_sale_condition', 0);
        $roomQuery = $this->searchRoomQueryPerDay($roomQuery, $inOutDate, 'room_stocks', 'date');
        $stayAbleRooms = $roomQuery->get()->toArray();

        $stayAbleRooms = collect($stayAbleRooms)
                         ->reject(function($room) use ($roomTypeRcs) {
                             if (empty($roomTypeRcs[$room['room_type_id']])) {
                                 return;
                             } else {
                                 return ( $roomTypeRcs[$room['room_type_id']]['adult_num'] > $room['adult_num'] ) || ( $roomTypeRcs[$room['room_type_id']]['child_num'] > $room['child_num'] );
                             }
                         })->toArray();

        return $stayAbleRooms;
    }

    public function convertInOutDate($checkinDate, $checkoutDate)
    {
        $currentDate = $checkinDate;
        $resDateArr = [];
        while(strtotime($currentDate) <= strtotime($checkoutDate)) {
            array_push($resDateArr, $currentDate);
            $currentDate = Carbon::parse($currentDate)->modify('+1 day')->format('Y-m-d');
        }
        //チェックアウト日は宿泊しないので削除する
        array_pop($resDateArr);

        return $resDateArr;
    }

    public function searchRoomQueryPerDay($query, array $dates, $table, $column)
    {
        $searchTarget = $table . '.'  . $column;
        if (count($dates) > 1) {
            $query->whereBetween($searchTarget, [current($dates), end($dates)]);
        } else {
            $query->where($searchTarget, $dates[0]);
        }

        return $query;
    }

    public function transformStayAbleRooms($stayAbleRooms)
    {
        $satyAbleRooms = collect($stayAbleRooms)
                         ->groupBy('room_type_id')
                         ->transform(function($rooms){
                            return $rooms->keyBy('date');
                         })
                         ->toArray();

        return $satyAbleRooms;
    }

    // 取得された部屋でチェックイン日〜チェックアウト日の全日程宿泊可能ではないものを削除する
    public function checkNights($stayAbleRooms, $nights)
    {
        $stayAbleRooms = collect($stayAbleRooms)
                         ->reject(function($rooms) use($nights) {
                             return ($nights > count($rooms));
                         })
                         ->toArray();

        return $stayAbleRooms;
    }

    // 渡された宿泊可能なプランの配列のキャンセルポリシーを、テキストに変換する
    public function convertCancelPolicy($planRooms)
    {
        $planRooms = collect($planRooms)
                     ->transform(function($planRoom) {
                         $cancelService = app()->make('ConvertCancelPolicyService');
                         $cancelTx = $cancelService->cancelConvert($planRoom['cancel_policy']['is_free_cancel'], $planRoom['cancel_policy']['free_day'], $planRoom['cancel_policy']['free_time'], $planRoom['cancel_policy']['cancel_charge_rate']);
                         $noShowTx = $cancelService->noShowConvert($planRoom['cancel_policy']['no_show_charge_rate']);
                         $planRoom['cancel_description'] = $cancelTx;
                         $planRoom['no_show_description']  = $noShowTx;
                         return $planRoom;
                     })
                     ->toArray();

        return $planRooms;
    }

    // 渡されたroom_typeのidそれぞれの、大人定員数・子供定員数を取得する
    public function getRoomTypePersonCapacity($roomTypeIds)
    {
        $roomTypeCapas = HotelRoomType::select('id as room_type_id', 'adult_num', 'child_num')
                                        ->whereIn('id', $roomTypeIds)
                                        ->get()
                                        ->keyBy('room_type_id')
                                        ->toArray();

        return $roomTypeCapas;
    }

    // ルームタイプごとの大人定員数・子供定員数と、postされた大人人数・子供人数を比較し、部屋タイプごとの料金計算に合わせた大人人数・子供人数に変換する
    public function convertRCClassPerRoomType($roomTypeCapas, $postAdultNum, $postChildNum, $ageNums=NULL, $hotelId=NULL)
    {
        $kidsService = app()->make('KidsPolicyService');

        $roomTypeRCs = collect($roomTypeCapas)
                        ->transform(function($capa) use ($postAdultNum, $postChildNum, $ageNums, $hotelId, $kidsService){
                            // ここでchildSumを計算する
                            // キッズポリシー適用可能な年齢の子供の数の合計（実際に適用されるのは、room_typeのchild_numの数分だけ）
                            $kidsPolicies = $kidsService->getKidsPolicy(json_decode(json_encode($ageNums)), $hotelId);
                            $childCounts = $kidsService->calcChildSum($capa, $kidsPolicies, $ageNums);

                            $childNums = $this->calcChildAsAdult($capa['child_num'], $childCounts['child_sum']);

                            $capa['adult_num'] = $postAdultNum + $childNums['child_as_adult'] + $childCounts['child_as_adult'];
                            $capa['child_num'] = $childNums['child_as_child'];
                            // キッズポリシー適用外の子供人数
                            $capa['child_as_adult'] = $childNums['child_as_adult'];
                            // キッズポリシーを適用できる子供人数
                            $capa['child_as_child'] = $childNums['child_as_child'];
                            return $capa;
                        })
                        ->toArray();

        return $roomTypeRCs;
    }

    public function calcChildAsAdult($capaChildNum, $postChildNum)
    {
        if ($postChildNum >= $capaChildNum) {
            $childAsAdult = $postChildNum - $capaChildNum;
            $childAsChild = $postChildNum - $childAsAdult;
        } else {
            $childAsAdult = 0;
            $childAsChild = $postChildNum;
        }

        $res = [
            'child_as_adult' => $childAsAdult,
            'child_as_child'  => $childAsChild,
        ];

        return $res;
    }

    // 指定された日付、room_typeのid、planのidで料金を取得する
    public function getPlanRoomRates($planId, $roomTypeIds, $inOutDate)
    {
        $planRoomRCQuery = PlanRoomTypeRate::select('room_type_id', 'date', 'class_type', 'class_person_num', 'class_amount')
                                         ->join('plan_room_type_rates_per_class', 'plan_room_type_rates_per_class.plan_room_type_rate_id', '=', 'plan_room_type_rates.id')
                                         ->where('plan_id', $planId)
                                         ->whereIn('room_type_id', $roomTypeIds);
                                        //  ->where('plan_room_type_rates_per_class.class_amount', '>', 0);
        $planRoomRCQuery = $this->searchRoomQueryPerDay($planRoomRCQuery, $inOutDate, 'plan_room_type_rates', 'date');
        $planRoomRCs = $planRoomRCQuery->get()->toArray();

        return $planRoomRCs;
    }

    public function convertPlanRoomRates($planRoomRates)
    {
        $planRoomRates = collect($planRoomRates)
                         ->groupBy('room_type_id')
                         ->transform(function($rate){
                             $rate = $rate->groupBy('class_person_num');
                             $rate = $rate->transform(function($r){
                                 return $r->keyBy('date');
                             });
                             return $rate;
                         })
                         ->toArray();

        return $planRoomRates;
    }

    public function getRCAmounts($roomTypeRCs, $planRoomRates, $inOutDate, $ages, $postChildSum, $planId)
    {
        $planRoomRates = collect($roomTypeRCs)
                         ->transform(function($rc, $roomTypeId) use ($planRoomRates, $ages, $postChildSum, $planId){
                             $kidsService = app()->make('KidsPolicyService');
                             // 該当する年齢のキッズポリシーのroom_type_idsに含まれなければ子供も大人として数える
                             $rc['adult_num'] = $rc['adult_num'] > 6 ? 6 : $rc['adult_num'];
                             if (!empty($planRoomRates[$rc['room_type_id']][$rc['adult_num']])) {
                                $targetAmounts = $planRoomRates[$rc['room_type_id']][$rc['adult_num']];
                                $calcAmounts = $kidsService->calcChildAmount($targetAmounts, $ages, $rc, $postChildSum, $planId);
                                $allDateAmount = collect($calcAmounts)->pluck('all_amount')->sum();
                                $calcAmounts['amount'] = ceil($allDateAmount);

                                return $calcAmounts;
                             }
                         })
                         ->toArray();

        return $planRoomRates;
    }

    // 全ての宿泊日数の、各部屋タイプを格納する配列を作成する
    public function makeRoomStockMap($stayAbleRooms)
    {
        $deleteKeys = ['sort_num', 'name', 'adult_num', 'child_num', 'room_size', 'room_type_id'];

        $stayAbleRooms = collect($stayAbleRooms)
                         ->groupBy('room_type_id')
                         ->transform(function($stayAbleRoom) use($deleteKeys){
                             $stayAbleRoom = $stayAbleRoom->toArray();
                             $stayAbleRoom = arrayWalkDelete($stayAbleRoom, $deleteKeys);
                             return collect($stayAbleRoom)->keyBy('date');
                         })
                         ->toArray();

        return $stayAbleRooms;
    }

    // 部屋タイプの重複を削除する
    public function makeDuplicateRoomUnique($rooms)
    {
        return collect($rooms)->unique('room_type_id')->keyBy('room_type_id')->toArray();
    }

    // 渡された宿泊可能なroom_typeのidから、ベッドを取得する
    public function getRoomTypeBeds($roomTypeIds)
    {
        $roomTypeBeds = HotelRoomTypeBed::select('hotel_room_type_beds.room_type_id', 'bed_size', 'bed_num')
                                          ->whereIn('hotel_room_type_beds.room_type_id', $roomTypeIds)
                                          ->get()
                                          ->toArray();
        return $roomTypeBeds;
    }

    // 渡されたroom_type_bedsの配列を部屋タイプごとに整形する
    public function transformRoomTypeBedArr($roomBeds)
    {
        $roomBeds = collect($roomBeds)
                    ->groupBy('room_type_id')
                    ->transform(function($roomBed){
                        $roomBed = $roomBed->toArray();
                        foreach ($roomBed as $key => $bed) {
                            $bedType = config('bed.bed_types')[$bed['bed_size']];
                            $roomBed[$key]['bed_type'] = $bedType;
                        }
                        return $roomBed;
                    })
                    ->toArray();

        return $roomBeds;
    }

    //渡されたroom_typeの配列とroom_type_bedの配列をマージする
    public function mergeRoomTypeArr($roomTypes, $roomTypeBeds, $roomTypeImages, $roomTypeRates)
    {
        $roomTypes = collect($roomTypes)
                     ->transform(function($room) use ($roomTypeBeds, $roomTypeImages, $roomTypeRates){
                         $s3Service = app()->make('S3Service');
                         $roomTypeId = $room['room_type_id'];
                         if (!empty($roomTypeBeds[$roomTypeId])) {
                            $room['beds'] = $roomTypeBeds[$roomTypeId];
                         } else {
                            $room['beds'] = [];
                         }

                         if (!empty($roomTypeImages[$roomTypeId])) {
                             foreach ($roomTypeImages[$roomTypeId] as $image) {
                                 $room['images'][] = $s3Service->getS3Image($image['image']);
                             }
                         } else {
                            $room['images'][] = asset('static/common/images/no_image.png');
                         }

                         if (!empty($roomTypeRates[$room['room_type_id']])) {
                             $room['amount'] = intval($roomTypeRates[$room['room_type_id']]['amount']);
                             $room['amount_breakdown'] = $roomTypeRates[$room['room_type_id']];
                         } else {
                            $room['amount'] = NULL;
                            $room['amount_breakdown'] = [];
                         }

                         return $room;
                     })
                     ->reject(function($room){
                         return (empty($room['amount']) || $room['amount'] <= 0);
                     })
                     ->toArray();

        return $roomTypes;
    }

    public function getRoomTypeImage($roomTypeIds)
    {
        $roomImages = HotelRoomTypeImage::select('image', 'room_type_id')
                                          ->whereIn('room_type_id', $roomTypeIds)
                                          ->get()
                                          ->groupBy('room_type_id')
                                          ->toArray();

        return $roomImages;
    }

    # 宿泊可能な宿泊プランと部屋タイプの配列をマージして整形する
    public function convertPlanRoomArr(array $stayAblePlans, array $stayAbleRooms, $formId=NULL, $roomNum=NULL)
    {
        $planRooms = collect($stayAblePlans)
                     ->transform(function($plan) use($stayAbleRooms, $formId) {
                        $stayAbleRoomArr = [];
                        foreach ($plan['room_type_ids'] as $roomTypeId) {
                            if (!empty($stayAbleRooms[$roomTypeId])) {
                                $plan['room_types'][$roomTypeId] = $stayAbleRooms[$roomTypeId];
                                array_push($stayAbleRoomArr, $roomTypeId);
                            }
                        }
                        $plan['stayable_room_type_ids'] = $stayAbleRoomArr;
                        if (!empty($formId)) {
                            $plan['form_id'] = $formId;
                        }
                        return $plan;
                     })
                     ->keyBy('id')
                     ->toArray();

        return $planRooms;
    }

    # 指定された部屋数を確保できるプランかどうかをチェックする
    public function rejectUnachievedRoomNum(array $planRooms, int $roomNum)
    {
        // 部屋タイプの種類が、$roomNum以上のものは$roomNum分の部屋は確保できている
        // 部屋タイプの種類が、$roomNumより少ないものだけチェックすればいい
        $planRooms = collect($planRooms)
                     ->reject(function($planRoom, $roomTypeId) use($roomNum) {
                         if (empty($planRoom)){ return true; }
                         $check = $this->checkOverPostRoomNum($planRoom['room_types'], $roomNum);
                         return !$check;
                     })
                     ->toArray();

        return $planRooms;
    }

    public function checkOverPostRoomNum($planRoomTypes, $roomNum)
    {
        if (count($planRoomTypes) < $roomNum) {
            $stayAbleRoomSum = 0;
            foreach ($planRoomTypes as $roomTypeId => $roomType) {
                $stayAbleRoomNum = collect($roomType)->pluck('date_stock_num')->min();
                $stayAbleRoomSum += $stayAbleRoomNum;
                if ($stayAbleRoomSum >= $roomNum) {
                    return true;
                }
            }
            return false;
        } else {
            return true;
        }
    }

    // 渡された部屋ごとの料金の配列から、金額が負の値になっているものを弾く
    public function rejectNegativeAmount($roomTypeRates)
    {
        $planRoomRates = collect($roomTypeRates)
                         ->reject(function($rate){
                             if (empty($rate)) {
                                 return true;
                             }

                             if ($rate['amount'] <= 0) {
                                 return true;
                             }

                             foreach ($rate as $r) {
                                 if ($r <= 0) {
                                     return true;
                                 }
                             }
                         })
                         ->toArray();

        return $planRoomRates;
    }

    public function getPostClassPersonNums($roomTypeRCs)
    {
        return collect($roomTypeRCs)->pluck('adult_num')->unique()->toArray();
    }

    public function getNGPlanRoomRates($roomTypeIds, $planIds, $inOutDate, $classPersons)
    {
         // 使用サイトコントローラが手間いらずの場合$classPersonsの中で6以上のものを全て6にする
         $classPersons = $this->convert6moreTo6($classPersons);

         $existingPlanIds = Plan::whereIn('id', $planIds)->where('is_new_plan', 0)->get()->pluck('existing_plan_id')->toArray();

         $planRoomNGsQuery = PlanRoomTypeRate::select('room_type_id', 'plan_id', 'date',
                                                 'date_sale_condition', 'plan_room_type_rates_per_class.class_person_num',
                                                 'plan_room_type_rates_per_class.class_amount')
                                          ->join('plan_room_type_rates_per_class', 'plan_room_type_rates_per_class.plan_room_type_rate_id', '=', 'plan_room_type_rates.id')
                                          ->whereIn('room_type_id', $roomTypeIds)
                                          ->where(function($q) use($existingPlanIds, $planIds){
                                              $q->whereIn('plan_id', $planIds);
                                              if (!empty($existingPlanIds)){
                                                  $q->orWhereIn('plan_id', $existingPlanIds);
                                              }
                                          })
                                          ->whereIn('plan_room_type_rates_per_class.class_person_num', $classPersons);

        $planRoomNGsQuery = $this->searchRoomQueryPerDay($planRoomNGsQuery, $inOutDate, 'plan_room_type_rates', 'date')->get();

         // 該当の人数区分の料金データがそもそも登録されていない場合は、早期リターン
         if (empty($planRoomNGsQuery->toArray())) {
            return ['res' => false];
         }

         $planRoomNGs =  $planRoomNGsQuery->reject(function($rate){
                                  return ($rate->date_sale_condition == 0 && $rate->class_amount > 0);
                               })
                               ->toArray();

        $planRoomNGs = collect($planRoomNGs)
                               ->groupBy('date')
                               ->transform(function($ng){
                                    return $ng->groupBy('plan_id')
                                              ->transform(function($n){
                                                  return $n->groupBy('room_type_id')
                                                           ->transform(function($v){
                                                                return $v->keyBy('class_person_num');
                                                            });
                                              });
                                })
                                ->toArray();

        return $planRoomNGs;
    }

    public function convert6moreTo6($classPersons)
    {
        $classPersons = collect($classPersons)
                        ->transform(function($classPerson){
                            $classPerson = $classPerson > 6 ? 6 : $classPerson;
                            return $classPerson;
                        })
                        ->unique()
                        ->toArray();

        return $classPersons;
    }

    public function rejectNGPlanRoom($planRooms, $planRoomNGs, $roomTypeRCs)
    {
        $planRooms = collect($planRooms)
                     ->transform(function($planRoom, $planId) use($planRoomNGs, $roomTypeRCs){
                         if (empty($planRoom['room_types'])) { return; }
                         $planRoom['room_types'] = $this->rejectNGClass($planRoom['room_types'], $planRoomNGs, $planId, $roomTypeRCs);
                         if ( !empty($planRoom['room_types']) ){
                             return $planRoom;
                         }
                     })
                     ->toArray();

        return $planRooms;
    }

    public function rejectNGClass($roomTypes, $classNGs, $planId, $roomTypeRCs)
    {
        $roomTypes = collect($roomTypes)
                     ->reject(function($roomType) use($classNGs, $planId, $roomTypeRCs) {
                         foreach ($roomType as $date => $room) {
                             $classNum = $roomTypeRCs[$room['room_type_id']]['adult_num'];
                             if (!empty( $classNGs[$date][$planId][$room['room_type_id']][$classNum] )) {
                                 $targetNG = $classNGs[$date][$planId][$room['room_type_id']][$classNum];
                                 if ($targetNG['date_sale_condition'] == 1) {
                                     return true;
                                 }

                                 if ($targetNG['class_amount'] <= 0) {
                                    return true;
                                }
                             }
                         }
                     })
                     ->toArray();

        return $roomTypes;
    }

    # 渡されたroom_typeのidの中から最大のchild_numを取得して返す
    public function getMaxChildtNum($roomTypeIds)
    {
        // キッズポリシーが存在しない場合はnullが返る
        $maxChildNum = HotelRoomType::select('child_num')
                                             ->whereIn('id', $roomTypeIds)
                                             ->get();
        $maxChildNum = $maxChildNum->max('child_num');
        return $maxChildNum;
    }

    # 渡されたroom_typeのidの中から最大のadult_numを取得して返す
    public function getMaxAdultNum($roomTypeIds)
    {
        $maxAdultNum = HotelRoomType::select('adult_num')
                                             ->whereIn('id', $roomTypeIds)
                                             ->get();
        $maxAdultNum = $maxAdultNum->max('adult_num');
        return $maxAdultNum;
    }

    # 渡されたhotelのidから、キッズポリシーを取得して返す
    public function getKidsPolicies($hotelId)
    {
        # キッズポリシーが存在しない場合はnullが返る
        $policies = HotelKidsPolicy::select('age_start', 'age_end', 'is_forbidden')
                                    ->where('hotel_id',$hotelId)
                                    ->get()
                                    ->toArray();
        return $policies;
    }

    public function convertChildNumData($maxChildNum, $kidsPolicies)
    {
        $response = [
            'max_child_num' => $maxChildNum,
            'kids_ages' => $kidsPolicies,
        ];

        return $response;
    }

    // 部屋数ごとに共通するプランだけを残す
    // プランは１つしか選択できないため、全ての部屋数目で宿泊できるプランのみを残す必要があるため
    public function leaveAllClearPlans($planRooms, $roomCount)
    {
        $onlyPlanIds = [];
        foreach ($planRooms as $roomNum => $room) {
            $planIds = collect($room)->keys()->toArray();
            foreach ($planIds as $planId) {
                array_push($onlyPlanIds, $planId);
            }
        }

        $planRooms = collect($planRooms)
                     ->transform(function($planRoom) use($onlyPlanIds, $roomCount) {
                         $planRoom = collect($planRoom)
                                  ->reject(function($plan, $planId) use($onlyPlanIds, $roomCount) {
                                      $count = collect($onlyPlanIds)->countBy(function($onlyPlanId) use($planId){
                                          if ($onlyPlanId == $planId){
                                              return $planId;
                                          }
                                      })->all();
                                      $count = $count[$planId];
                                      if ($count != $roomCount) {
                                          return true;
                                      }
                                  })
                                  ->toArray();
                        return $planRoom;
                     })
                     ->reject(function($planRoom){
                         return empty($planRoom);
                     })
                     ->toArray();

        return $planRooms;
    }

    public function rejectNonRateRoom($stayAbleRoom, $roomTypeRate)
    {
        $stayAbleRoom = collect($stayAbleRoom)
                        ->reject(function($room, $roomTypeId) use($roomTypeRate) {
                            if (empty($roomTypeRate[$roomTypeId])) {
                                return true;
                            }
                        })
                        ->toArray();

        return $stayAbleRoom;
    }

    public function get0RatesFromPlanRooms($amountBreakDown, $planId)
    {
        $amountBreakDown = arrayObjectVars($amountBreakDown);
        $roomTypeIds = collect($amountBreakDown)->pluck('room_type_id')->unique()->toArray();
        $dates = collect($amountBreakDown)
                 ->transform(function($breakDown){
                     $breakDown['date'] = Carbon::parse($breakDown['date'])->format('Y-m-d');
                     return $breakDown;
                 })
                 ->pluck('date')
                 ->toArray();
        $classPersons = collect($amountBreakDown)->pluck('class_person_num')->unique()->toArray();

        $amount = PlanRoomTypeRate::select('plan_room_type_rates_per_class.class_amount')
                                         ->join('plan_room_type_rates_per_class', 'plan_room_type_rates_per_class.plan_room_type_rate_id', '=', 'plan_room_type_rates.id')
                                         ->whereIn('room_type_id', $roomTypeIds)
                                         ->where('plan_id', $planId)
                                         ->whereIn('plan_room_type_rates_per_class.class_person_num', $classPersons)
                                         ->whereIn('date', $dates)
                                         ->get()
                                         ->pluck('class_amount')
                                         ->toArray();
        if (in_array(0, $amount)) {
            return false;
        }

        return true;
    }

    public function getRoomBedImage($roomTypeId)
    {
        $roomType = HotelRoomType::where('id', $roomTypeId)
                    ->with(['hotelRoomTypeBeds','hotelRoomTypeImages'])
                    ->get();

        return $roomType;
    }

    public function getImageFromPath($roomTypeImages)
    {
        $images = $roomTypeImages
                  ->transform(function($image){
                      return photoUrl($image->image);
                  });

        return $roomTypeImages;
    }

    #渡された料金データの中で該当する人数区分の料金に0があるroom_type_idを返す
    public function get0PriceRooms($planRoomRates, $roomTypeRCs)
    {
        $rejectRoomIds = [];
        foreach ($planRoomRates as $roomNum => $rate) {
            $rate0 = collect($rate)->where('class_amount', 0);
            foreach ($roomTypeRCs[$roomNum] as $roomTypeId => $rc) {
                $isRate0 = $rate0
                           ->where('room_type_id', $roomTypeId)
                           ->where('class_person_num', $rc['adult_num'])
                           ->isNotEmpty();
                if ($isRate0) {
                    $rejectRoomIds[$roomNum][] = $roomTypeId;
                }
            }
        }

        return $rejectRoomIds;
    }

    public function trim0PriceRoomTypes($stayAbleRoomTypeIdsPerRoom, $rejectRoomTypeIds)
    {
        $roomTypeIds = collect($stayAbleRoomTypeIdsPerRoom)
                       ->transform(function($ids, $roomNum) use($rejectRoomTypeIds){
                            if (!empty($rejectRoomTypeIds[$roomNum])) {
                                $targetRejects = $rejectRoomTypeIds[$roomNum];
                                $ids = array_diff($ids, $rejectRoomTypeIds[$roomNum]);
                            }

                            return array_values($ids);
                       })
                       ->toArray();

        return $roomTypeIds;
    }
}