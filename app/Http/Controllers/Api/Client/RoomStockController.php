<?php

namespace App\Http\Controllers\Api\Client;

use Illuminate\Http\Request;
use Carbon\Carbon;

class RoomStockController extends ApiBaseController
{
    public function __construct()
    {
        $this->roomStockService = app()->make('CommonRoomStockService');
    }

    public function list(Request $request)
    {
        $hotelId = $request->get('hotel_id');
        $date = $request->get('date', '');
        if (empty($date)) {
            $date = Carbon::now()->format('Y-m');
        }

        $allDates = makeAllDateByMonth($date);
        $roomStocks = $this->roomStockService->getByDate($hotelId, $date);
        $records = $this->roomStockService->makeStockListData($roomStocks, $allDates);
        $allDates = array_map('dateFormatNj', $allDates);
        $data = [
            'records' => $records->toArray(),
            'dates' => $allDates,
        ];

        return $this->success($data);
    }
}
