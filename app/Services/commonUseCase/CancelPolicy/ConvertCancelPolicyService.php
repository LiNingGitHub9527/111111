<?php
namespace App\Services\commonUseCase\CancelPolicy;

use Carbon\Carbon;
use App\Models\CancelPolicy;

class ConvertCancelPolicyService
{

    public function __construct()
    {
        
    }

    public function cancelConvert($isFreeCancel, $freeDay, $freeTime, $chargeRate)
    {
        $cancelDesc = '';
        if ($isFreeCancel == 0) {
            $cancelDesc .= '予約後キャンセルした場合は、宿泊料金の' . $chargeRate . '%のキャンセル料がかかります。';
        } else {
            if ($freeDay != 0) {
                $cancelDesc .= 'チェックイン日の' . $freeDay . '日前までは無料でキャンセル可能です。それ以降にキャンセルの場合は、' . $chargeRate . '%のキャンセル料がかかります。';
            } else {
                $cancelDesc .= 'チェックイン日当日の' . $freeTime . '時まで無料でキャンセル可能です。それ以降にキャンセルの場合は、' . $chargeRate . '%のキャンセル料がかかります。';
            }
        }

        return $cancelDesc;
    }

    public function noShowConvert($chargeRate)
    {
        $noShowDesc = '無断でキャンセルした場合、' . $chargeRate . '%のキャンセル料がかかります。';
        return $noShowDesc; 
    }
}