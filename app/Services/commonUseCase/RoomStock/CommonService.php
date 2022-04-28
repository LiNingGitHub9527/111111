<?php

namespace App\Services\commonUseCase\RoomStock;

use App\Models\RoomStock;
use App\Models\HotelRoomType;
use Carbon\Carbon;

class CommonService 
{
    public function __construct()
    {
    }

    public function getByDate($hotelId, $date)
    {
        $roomStocks = HotelRoomType::with(['roomStocks' => function ($query) use($date){
                          $query->where('date', 'LIKE', $date . '%');
                      }])
                      ->where('hotel_id', $hotelId)
                      ->get();


        return $roomStocks;
    }

    public function makeStockListData($roomStocks, $allDates)
    {
        $roomStocks = $roomStocks->transform(function($roomStock) use($allDates){
            $roomStock = $roomStock->toArray();
            $emptyDates = $allDates;
            $stocks = [];
            if (!empty($roomStock['room_stocks'])) {
                foreach ($roomStock['room_stocks'] as &$s) {
                    $s['date'] = Carbon::parse($s['date'])->format('Y-m-d');
                }
                $stockDates = collect($roomStock['room_stocks'])->pluck('date')->toArray();
                $emptyDates = array_diff($allDates, $stockDates);
            }
            foreach ($emptyDates as $eDate) {
                $item = [
                    'hotel_room_type_id' => $roomStock['id'],
                    'date' => $eDate,
                    'date_sale_condition' => '在庫連動なし',
                    'date_stock_num' => 0,
                    'date_reserve_num' => 0
                ];
                array_push($roomStock['room_stocks'], $item);
            }

            $roomStock['room_stocks'] = collect($roomStock['room_stocks'])->sortBy('date')->toArray();

            return $roomStock;
        });

        return $roomStocks;
    }
}