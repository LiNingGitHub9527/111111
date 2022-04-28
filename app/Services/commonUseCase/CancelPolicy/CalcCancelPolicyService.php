<?php
namespace App\Services\commonUseCase\CancelPolicy;

use Carbon\Carbon;
use App\Models\CancelPolicy;

class CalcCancelPolicyService
{

    public function __construct()
    {
        $this->canPoli_service = app()->make('CancelPolicyService');
    }

    public function getCancelFee($cancelPolicy, $checkinDate, $nowDateTime, $amount)
    {
        $freeLimitDate = Carbon::parse($checkinDate)->subDays($cancelPolicy->free_day)->format('Y-m-d');
        $freeLimitDateTime = $freeLimitDate . ' ' . $cancelPolicy->free_time . ':00';

        $isFreeCancel = $this->canPoli_service->checkFreeCancelByNow(NULL, $checkinDate, json_decode(json_encode($cancelPolicy)));
        if ($isFreeCancel) {
            $cancelFeeData['cancel_fee'] = 0;
            $cancelFeeData['is_free_cancel'] = $isFreeCancel;
            return $cancelFeeData;
        }

        if ($cancelPolicy->cancel_charge_rate != 0)  {
            if (strtotime($nowDateTime) >= $freeLimitDateTime) {
                $cancelFee = $this->calcCancelFee($cancelPolicy->cancel_charge_rate, $amount);
            } else {
                $cancelFee = 0;
            }
        } else {
            $cancelFee = 0;
        }

        $cancelFeeData['cancel_fee'] = $cancelFee;
        $cancelFeeData['is_free_cancel'] = $isFreeCancel;
        return $cancelFeeData;
    }

    public function calcCancelFee($rate, $amount)
    {
        return floor($amount * ($rate * 0.01));
    }
}