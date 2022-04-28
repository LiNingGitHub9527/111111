<?php

namespace App\Services\Api\Client\Reservation;

use App\Models\Reservation;
use App\Jobs\Pms\ReservationCancelJob;

class OtherReservationService
{
    public function __construct()
    {
    }

    /**
     * 渡されたreservationsオブジェクトに紐づく部屋タイプのnameを取得し配列で返却する
     *
     * @param \App\Models\Reservation $reservation
     * @return array
     */
    public function extractRoomTypesBy(
        \App\Models\Reservation $reservation
    ): array {
        $roomTypeNames = [];
        foreach ($reservation->reservedBlocks as $reservedBlock) {
            $roomType = $reservedBlock->reservationBlock->roomType;
            $roomTypeNames[] = $roomType->name ?? '';
        }

        return $roomTypeNames;
    }

    /**
     * 他業種の場合のreservationsレコードのリレーションを含めたクエリをbuildする
     * 業種関係ない共通のreservations取得クエリを別の箇所で発行し、他業種の場合にこのメソッドでクエリ発行することを想定
     * reservation_block_idが渡されている場合は、reserved_reservation_blocksから同じblockIdを持つ予約に絞って返却
     * ※join、whereHasをなるべく避けるため、whereInのサブクエリ内でselect,from句を利用している
     *
     * @param integer $blockId | reservation_blcoks.idの値
     * @param array $withList | 取得するリレーション名の配列
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function buildGetQueryByBlockId(
        int $blockId=0,
        array $withList,
        \Illuminate\Database\Eloquent\Builder $query
    ): \Illuminate\Database\Eloquent\Builder {
        $query->with($withList);
        if ($blockId) {
            $query->whereIn('id', function($q) use($blockId){
                $this->_buildOnlyTargetBlockReservationQuery($q, $blockId);
            });
        }
        return $query;
    }

    /**
     * サブクエリとして利用を想定
     * 渡されたreservation_block_idから、reservation_idを取得するクエリを発行して返却する
     *
     * @param \Illuminate\Database\Query\Builder $q
     * @param integer $blockId
     * @return \Illuminate\Database\Query\Builder
     */
    private function _buildOnlyTargetBlockReservationQuery(
        \Illuminate\Database\Query\Builder $q, 
        int $blockId
    ): \Illuminate\Database\Query\Builder {
        $q->from('reserved_reservation_blocks')
            ->where('reservation_block_id', $blockId)
            ->select('reservation_id as id');

        return $q;
    }
}