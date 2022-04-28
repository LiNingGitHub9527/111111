<?php

namespace App\Services\Api\Client\Reservation;

use App\Models\Hotel;

class ReservationScheduleService
{

    /**
     * 予約枠の部屋数を更新する
     *
     * @param integer $hotelId 施設ID
     * @param integer $clientId クライアントID
     * @param array $reqParams リクエストパラメータ
     * @return array
     */
    public function updateRoomNum(int $hotelId, int $clientId, array $reqParams): array
    {
        $hotel = Hotel::where('id', $hotelId)->where('client_id', $clientId)->first();
        if (empty($hotel)) {
            return [
                'isSuccess' => false,
                'code' => 404,
                'message' => 'データが存在しません',
            ];
        }

        $condition = [];
        $num = $reqParams['num'] ?? 0;
        // 部屋数を減らす場合、満室の予約枠は除外する
        if ($num < 0) {
            $condition['is_available'] = 1;
        }
        if (array_key_exists('room_type_id', $reqParams) && !is_null($reqParams['room_type_id'])) {
            $condition['room_type_id'] = $reqParams['room_type_id'];
        }

        // 満室以外の予約枠を取得する
        if (array_key_exists('date', $reqParams) && !empty($reqParams['date'])) {
            $reservationBlocks = $hotel->reservationBlocks()
                ->where($condition)
                ->whereIn('date', $reqParams['date'])
                ->get();
        } else {
            $reservationBlocks = $hotel->reservationBlocks()
                ->where($condition)
                ->get();
        }

        try {
            \DB::transaction(
                function () use ($reservationBlocks, $num) {
                    $reservationBlocks->each(function($block) use ($num) {
                        $roomType = $block->roomType()->first();
                        // 部屋タイプの部屋数上限
                        $maxRoomNum = $roomType->room_num;
                        // 予約済みの部屋数
                        $reservedNum = $block->reserved_num;
                        $newRoomNum = $block->room_num + $num;
                        if ($newRoomNum < $reservedNum) {
                            // 更新後の部屋数は予約済みの部屋数より少なくなってはいけない
                            $newRoomNum = $reservedNum;
                        } else if ($newRoomNum > $maxRoomNum) {
                            // 更新後の部屋数は部屋タイプの部屋数上限より多くなってはいけない
                            $newRoomNum = $maxRoomNum;
                        }
                        if ($newRoomNum < 0) {
                            // 更新後の部屋数は0より少なくなってはいけない
                            $newRoomNum = 0;
                        }
                        // 部屋数の増減によるis_availableの更新
                        $block->is_available = $newRoomNum > $reservedNum ? 1 : 0;
                        $block->room_num = $newRoomNum;
                        $block->save();
                    });
                }
            );
        } catch (\Exception $e) {
            Log::info('update room num failed :' . $e);
            return [
                'isSuccess' => false,
                'code' => 500,
                'message' => 'update room num failed',
            ];
        }

        return [
            'isSuccess' => true,
            'data' => null,
        ];
    }

}
