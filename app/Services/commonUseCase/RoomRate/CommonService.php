<?php

namespace App\Services\commonUseCase\RoomRate;

use App\Models\HotelRoomType;
use App\Models\Plan;
use App\Models\PlanRoomTypeRate;
use App\Models\PlanRoomTypeRatePerClass;
use Carbon\Carbon;

class CommonService
{
    public function __construct()
    {
    }

    public function getByDate($hotelId, $date)
    {
        $roomTypes = HotelRoomType::where('hotel_id', $hotelId)
                     ->get()
                     ->keyBy('id')
                     ->toArray();

        $roomRates = PlanRoomTypeRatePerClass::with('rates')
                     ->whereHas('rates', function ($query) use($hotelId, $date){
                         $query->where('hotel_id', $hotelId);
                         $query->where('date', 'LIKE', $date . '%');
                     })
                     ->get()
                     ->toArray();

        foreach ($roomRates as &$rate) {
            $r = $rate['rates'];
            $rate['plan_id'] = $r['plan_id'];
            $rate['date'] = $r['date'];
            $rate['room_type_id'] = $r['room_type_id'];
            unset($rate['rates']);
        }

        return [$roomRates, $roomTypes];
    }

    public function mergeRateToRoom($roomRates, $roomTypes)
    {
        $roomRates = collect($roomRates)
                     ->groupBy('room_type_id')
                     ->transform(function($rate){
                        //  $rate['date'] = Carbon::parse($rate['date'])->format('Y-m-d');
                         return $rate->groupBy('plan_id')
                                ->transform(function($r){
                                    return $r->groupBy('class_person_num');
                                });
                     })
                     ->toArray();

        foreach ($roomTypes as $roomTypeId => &$roomType) {
            if (empty($roomRates[$roomTypeId])) {
                $roomType['room_rates'] = [];
            } else {
                $roomRate = $roomRates[$roomTypeId];
                $roomType['room_rates'] = $roomRate;
            }
        }

        return $roomTypes;

    }

    #TODO: ない日付分を埋める処理
    // public function makeRateListData($rates, $allDates)
    // {
    //     $rates = collect($rates)->transform(function($rate) use($allDates) {
    //                  dd($rate);
    //                  $emptyDates = $allDates;
    //                  if (!empty($rate['room_rates'])) {
    //                      foreach ($rate['room_rates'] as &$r) {
    //                         $r['date'] = Carbon::parse($r['date'])->format('Y-m-d');
    //                      }
    //                      $rateDates = collect($rate['room_rates'])->pluck('date')->toArray();
    //                      $emptyDates = array_diff($allDates, $rateDates);
    //                  }

    //                  $maxClass = $rate['adult_num'];
    //                  foreach ($emptyDates as $eDate) {
    //                      $item = [
    //                          'date' => $eDate,
    //                          'date_sale_condition' => 1,
    //                          'per_classes' => [],
    //                      ];
    //                      for ($c=1; $c <= $maxClass; $c++) {
    //                          $class = [
    //                             'class_type' => 2,
    //                             'class_person_num' => $c,
    //                             'class_amount' => 0
    //                          ];
    //                          array_push($item['per_classes'], $class);
    //                      }
    //                  }

    //                  $rate['room_rates'] = collect($rate['room_rates'])->sortBy('date')->toArray();
    //                  unset($rate['roomRates']);
    //                  return $rate;
    //              });

    //              return $rates;
    // }
}