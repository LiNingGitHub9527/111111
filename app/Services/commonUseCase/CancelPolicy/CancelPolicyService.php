<?php

namespace App\Services\commonUseCase\CancelPolicy;

use App\Models\CancelPolicy;
use Carbon\Carbon;

class CancelPolicyService
{

    public function __construct()
    {
    }

    public function checkFreeCancelByNow($canPoliId = NULL, $checkinDate, $policy = [])
    {
        if (!empty($canPoliId)) {
            $policy = CancelPolicy::find($canPoliId);
        }
        // 無料キャンセル期間のないキャンセルポリシー
        if (!$policy->is_free_cancel) {
            return false;
        }

        // 無料キャンセル期間のあるキャンセルポリシーなら現在の日付が無料キャンセル期間かどうかチェック
        $today = Carbon::parse(Carbon::now()->format('Y-m-d'));

        $canFreeDay = Carbon::parse($checkinDate)->subDays($policy->free_day);

        // 無料キャンセル期間を過ぎていればfalse
        if ($today->gt($canFreeDay)) {
            return false;
        } else if ($today->eq($canFreeDay)) {
            $todayFreeAt = $today->addHours($policy->free_time);
            $nowMinute = Carbon::parse(Carbon::now()->format('Y-m-d H:i'));
            if ($nowMinute->gt($todayFreeAt)) {
                return false;
            }
        }

        return true;
    }
}