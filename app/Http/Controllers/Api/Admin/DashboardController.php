<?php

namespace App\Http\Controllers\Api\Admin;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Client;
use App\Models\Hotel;
use App\Models\HotelMonthFeeSummary;

class DashboardController extends ApiBaseController
{
    public function list()
    {
        $preMonth = now()->subMonth()->startOfMonth();
        $month = $preMonth->format('Y-m-d');
        $summary = HotelMonthFeeSummary::where('month', $month)->first([
                \DB::raw('SUM(reservation_fee) as reservation_fee'),
                \DB::raw('SUM(monthly_fee) as monthly_fee')
        ])->toArray();
        $sales = [
            'month' => $month,
            'reservation_fee' => $summary['reservation_fee'] ?? '0',
            'monthly_fee' => $summary['monthly_fee'] ?? '0',
        ];
        $clientNum = Client::count();
        $hotelNum = Hotel::count();
        
        $data = [
            'sales' => $sales,
            'client_num' => $clientNum,
            'hotel_num' => $hotelNum,
        ];
        return $this->success($data);
    }

    public function sales(Request $request)
    {
        $month = $request->get('month');
        $m = Carbon::parse($month)->startOfMonth();
        $month = $m->format('Y-m-d');
        $summary = HotelMonthFeeSummary::where('month', $month)->first([
                \DB::raw('SUM(reservation_fee) as reservation_fee'),
                \DB::raw('SUM(monthly_fee) as monthly_fee')
        ])->toArray();
        $sales = [
            'month' => $month,
            'reservation_fee' => $summary['reservation_fee'] ?? '0',
            'monthly_fee' => $summary['monthly_fee'] ?? '0',
        ];

        $data = [
            'sales' => $sales,
        ];
        return $this->success($data);
    }
}
