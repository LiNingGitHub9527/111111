<?php

namespace App\Http\Controllers\Api\Client;

use Illuminate\Http\Request;
use Carbon\Carbon;

class RoomRateController extends ApiBaseController
{
    public function __construct()
    {
        $this->roomRateService = app()->make('CommonRoomRateService');
    }

    public function list(Request $request)
    {
        $hotelId = $request->get('hotel_id');
        $date = $request->get('date', '');
        if (empty($date)) {
            $date = Carbon::now()->format('Y-m');
        }

        list($roomRates, $roomTypes) = $this->roomRateService->getByDate($hotelId, $date);
        $roomRates = $this->roomRateService->mergeRateToRoom($roomRates, $roomTypes);
        
        $allDates = makeAllDateByMonth($date);
        #TODO: ない日付分を埋める処理
        // $roomRates = $this->roomRateService->makeRateListData($roomRates, $allDates);

        $allDates = array_map('dateFormatNj', $allDates);
        $data = [
            'records' => $roomRates,
            'dates' => $allDates
        ];

        return $this->success($data);
    }
}
