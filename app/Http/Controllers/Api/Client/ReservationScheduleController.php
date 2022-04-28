<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Requests\Api\Client\ReservationBlockCloseRequest;
use App\Http\Requests\Api\Client\ReservationBlockEditRequest;
use App\Http\Requests\Api\Client\ReservationBlockListRequest;
use App\Http\Requests\Api\Client\ReservationBlockRequest;
use App\Http\Requests\Api\Client\ReservationBlockRoomNumRequest;
use App\Models\Hotel;
use App\Models\HotelRoomType;
use App\Models\ReservationBlock;
use App\Models\ReservationRepeatGroup;
use App\Models\ReservedReservationBlock;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReservationScheduleController extends ApiBaseController
{
    public function __construct()
    {
        $this->reserve_schedule_service = app()->make('ApiReservationScheduleService');
    }

    public function get_room_type(int $hotelId)
    {
        $hotel = Hotel::where('id', $hotelId)->where('client_id', $this->user()->id)->first();
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }

        $detail = [
            'id' => $hotel->id,
            'name' => $hotel->name,
            'crm_base_id' => $hotel->crm_base_id
        ];

        $getRoomTypes = HotelRoomType::where('hotel_id', $hotelId)->get()->toArray();
        if (!empty($getRoomTypes)) {
            foreach ($getRoomTypes as $key => $room_type) {
                $room_types[$key]['id'] = $room_type['id'];
                $room_types[$key]['name'] = $room_type['name'];
            }
        } else {
            $room_types = [];
        }

        $data = [
            'detail' => $detail,
            'room_types' => $room_types,
        ];

        return $this->success($data);
    }

    public function list(ReservationBlockListRequest $request, int $hotelId): JsonResponse
    {
        $roomTypeIds = $request->get('room_type_id');
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');

        $hotel = Hotel::where('id', $hotelId)->where('client_id', $this->user()->id)->first();
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }

        if (empty($roomTypeIds)) {
            // room_type_idが空の時は全ての部屋タイプを対象とする。
            $hotelRoomTypes = $hotel->hotelRoomTypes()->get();
        } else {
            $hotelRoomTypes = $hotel->hotelRoomTypes()->whereIn('id', $roomTypeIds)->get();
        }
        if ($hotelRoomTypes->isEmpty()) {
            // 部屋タイプが未作成の場合、空でレスポンスする。
            return $this->success();
        }

        $room_type_stocks = $hotelRoomTypes->keyBy('id')->map(function($roomType) {
            return $roomType->room_num;
        });

        $condition = [
            'hotel_id' => $hotelId,
        ];
        if (empty($roomTypeIds)) {
            $getReservationBlocks = ReservationBlock::where($condition)
                ->whereBetween("date", [$start_date, $end_date])->orderBy('room_type_id', 'ASC')->get();
        } else {
            $getReservationBlocks = ReservationBlock::where($condition)
                ->whereIn('room_type_id', $roomTypeIds)
                ->whereBetween("date", [$start_date, $end_date])->orderBy('room_type_id', 'ASC')->get();
        }
        $reservation_blocks = $getReservationBlocks->map(function($block) {
            $data = $this->makeReservationBlock($block);
            $data['room_type_id'] = $block->room_type_id;
            return $data;
        });
        return $this->success(
            compact('start_date', 'end_date', 'room_type_stocks', 'reservation_blocks'));
    }

    private function makeReservationBlock(ReservationBlock $block): array
    {
        $reservationIds = [];
        if ($block->reserved_num >= 1) {
            $reservedBlocks = ReservedReservationBlock::where('reservation_block_id', $block->id)->get();
            $reservationIds = collect($reservedBlocks)->map(function ($item) {
                return $item->reservation_id;
            });
        }
        $repeatIntervalType = 0;
        $repeatEndDate = '';
        if (!is_null($block->reservation_repeat_group_id)) {
            $repeatGroup = $block->reservationRepeatGroup;
            if (!is_null($repeatGroup)) {
                $repeatIntervalType = $repeatGroup->repeat_interval_type;
                $repeatEndDate = $repeatGroup->repeat_end_date;
            }
        }
        return array_merge([
            'reservation_block_id' => $block->id,
            'reservation_repeat_group_id' => $block->reservation_repeat_group_id,
            'is_available' => $block->is_available,
            'person_capacity' => $block->person_capacity,
            'reserved_num' => $block->reserved_num,
            'room_num' => $block->room_num,
            'price' => $block->price,
            'date' => $block->date,
            'reservation_ids' => $reservationIds,
            'repeat_interval_type' => $repeatIntervalType,
            'repeat_end_date' => $repeatEndDate,
            'is_closed' => $block->is_closed,
        ], $this->convertIntToTime($block));
    }

    private function convertTimeToInt(string $targetTime): array
    {
        $arr = explode(':', $targetTime);
        if (count($arr) != 2) {
            return [];
        }
        return array_map('intval', $arr);
    }

    /**
     * ReservationBlockのstart_hour/start_minute, end_hour/end_minuteをtime形式に変換して返却する
     *
     * @param ReservationBlock $block 予約枠モデル
     * @return array
     */
    private function convertIntToTime(ReservationBlock $block): array
    {
        $startHour = $this->checkValidTime($block->start_hour, 0, 99);
        $startMinute = $this->checkValidTime($block->start_minute, 0, 59);
        $endHour = $this->checkValidTime($block->end_hour, 0, 99);
        $endMinute = $this->checkValidTime($block->end_minute, 0, 59);
        return [
            'start_time' => sprintf("%02d:%02d", $startHour, $startMinute),
            'end_time' => sprintf("%02d:%02d", $endHour, $endMinute),
        ];
    }

    /**
     * $valueが$minと$maxの範囲内であるかチェックする
     *
     * @param int $value チェック対象の値
     * @param int $min 範囲の最小値
     * @param int $max 範囲の最大値
     * @return int 範囲内の場合：$value、範囲外の場合：0
     */
    private function checkValidTime(int $value, int $min, int $max): int
    {
        if ($value < $min || $value > $max) {
            return 0;
        }
        return $value;
    }

    public function create(ReservationBlockRequest $request, int $hotelId): JsonResponse
    {
        $hotel = Hotel::where('id', $hotelId)->where('client_id', $this->user()->id)->first();
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }

        $parameters = $request->only([
            'room_type_id', 'date', 'person_capacity',
            'room_num', 'price', 'start_time', 'end_time',
            'repeat_interval_type', 'repeat_end_date'
        ]);
        $parameters['hotel_id'] = $hotelId;
        [$startHour, $startMinute] = $this->convertTimeToInt($parameters['start_time']);
        [$endHour, $endMinute] = $this->convertTimeToInt($parameters['end_time']);
        $times = [
            'start_hour' => $startHour,
            'start_minute' => $startMinute,
            'end_hour' => $endHour,
            'end_minute' => $endMinute,
        ];

        $retValReservationBlocks = [];
        try {
            \DB::transaction(
                function () use ($parameters, $times, &$retValReservationBlocks) {
                    $intervalType = $parameters['repeat_interval_type'];
                    if ($intervalType == 1 || $intervalType == 2) {
                        $reservationBlocks = $this->createRepeatReservationBlocks($intervalType, $parameters, $times);
                    } else {
                        $reservationBlock = $this->createOneReservationBlock($parameters, $times);
                        $reservationBlocks = array($reservationBlock);
                    }

                    foreach ($reservationBlocks as $reservationBlock) {
                        array_push($retValReservationBlocks, $this->makeReservationBlock($reservationBlock));
                    }
                }
            );
        } catch (\Exception $e) {
            Log::info('save failed :' . $e);
            return $this->error('create failed', 500);
        }

        $data = [
            'reservation_blocks' => $retValReservationBlocks,
        ];

        return $this->success($data);
    }

    private function createRepeatReservationBlocks(int $intervalType, array $parameters, array $times): array
    {
        $adjustDate = $intervalType == 1 ? ' +1day' : ' +1week';

        $createRepeatGroup = array_diff_key($parameters, array_flip(['start_time', 'end_time']));
        $createRepeatGroup['repeat_start_date'] = $parameters['date'];
        $createRepeatGroup = array_merge($createRepeatGroup, $times);

        $reservationRepeatGroup = ReservationRepeatGroup::create($createRepeatGroup);
        $parameters['reservation_repeat_group_id'] = $reservationRepeatGroup->id;

        $reservationBlocks = [];
        while (true) {
            if ($parameters['date'] > $parameters['repeat_end_date']) {
                break;
            }

            $createData = array_merge(
                array_diff_key($parameters, array_flip(['start_time', 'end_time'])),
                $times,
            );
            $reservationBlock = ReservationBlock::create($createData);
            array_push($reservationBlocks, ReservationBlock::find($reservationBlock->id));

            $parameters['date'] = date('Y-m-d', strtotime($parameters['date'] . $adjustDate));
        }

        // 1件も予約枠が作成されなかった場合、作成した予約枠グループを削除する。
        if (empty($reservationBlocks)) {
            $reservationRepeatGroup->delete();
        }

        return $reservationBlocks;
    }

    private function createOneReservationBlock(array $parameters, array $times): ReservationBlock
    {
        $createData = array_diff_key($parameters, array_flip(['start_time', 'end_time']));
        $reservationBlock = new ReservationBlock;
        $reservationBlock->fill(array_merge($createData, $times));
        $reservationBlock->save();
        return ReservationBlock::find($reservationBlock->id);
    }

    public function edit(ReservationBlockEditRequest $request, int $hotelId, int $reservationBlockId): JsonResponse
    {
        $hotel = Hotel::where('id', $hotelId)->where('client_id', $this->user()->id)->first();
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }

        $reservationBlock = $hotel->reservationBlocks()->where('id', $reservationBlockId)->first();
        if (empty($reservationBlock)) {
            return $this->error('更新できる予約枠がありませんでした', 500);
        }

        $parameters = $request->only([
            'room_type_id', 'date', 'person_capacity',
            'room_num', 'price', 'start_time', 'end_time', 'update_type'
        ]);
        [$startHour, $startMinute] = $this->convertTimeToInt($parameters['start_time']);
        [$endHour, $endMinute] = $this->convertTimeToInt($parameters['end_time']);
        $times = [
            'start_hour' => $startHour,
            'start_minute' => $startMinute,
            'end_hour' => $endHour,
            'end_minute' => $endMinute,
        ];

        $retValReservationBlocks = [];
        try {
            \DB::transaction(
                function () use ($reservationBlock, $parameters, $times, &$retValReservationBlocks) {

                    // update_type 0:個別更新 1:グループ更新
                    if ($parameters['update_type'] == 1) {
                        $reservationBlocks = $this->editRepeatReservationBlocks($reservationBlock, $parameters, $times);
                    } else {
                        if ($this->editOneReservationBlock($reservationBlock, $parameters, $times)) {
                            $reservationBlocks = array($reservationBlock);
                        } else {
                            $reservationBlocks = [];
                        }
                    }

                    foreach ($reservationBlocks as $b) {
                        array_push($retValReservationBlocks, $this->makeReservationBlock($b));
                    }
                }
            );
        } catch (\Exception $e) {
            Log::info('edit failed :' . $e);
            return $this->error('edit failed', 500);
        }

        if (empty($retValReservationBlocks)) {
            return $this->error('更新できる予約枠がありませんでした', 500);
        }

        $data = [
            'reservation_blocks' => $retValReservationBlocks,
        ];

        return $this->success($data);
    }

    private function editRepeatReservationBlocks(ReservationBlock $reservationBlock, array $parameters, array $times): array
    {
        // グループ更新だが、対象の予約枠がグループではない場合、更新せずスキップする。
        if (is_null($reservationBlock->reservation_repeat_group_id)) {
            return [];
        }

        // グループ更新の場合、dateは変更しない。
        unset($parameters['date']);

        // 新規に予約枠グループを作成する。
        $createRepeatGroup = $reservationBlock->reservationRepeatGroup->toArray();
        $reservationRepeatGroup = ReservationRepeatGroup::create(array_merge($createRepeatGroup, $times));

        $reservationBlocks = [];
        // group全てを変更
        foreach ($reservationBlock->reservationRepeatGroup->reservationBlocks as $groupBlock) {
            // 過去の予約枠や、すでに予約が入っている予約枠は更新せずスキップする。
            if ($this->isPastTime($groupBlock) || $groupBlock->reservedReservationBlocks()->count() > 0) {
                continue;
            }

            $groupBlock->fill($parameters);
            $groupBlock->fill($times);
            $groupBlock->reservation_repeat_group_id = $reservationRepeatGroup->id;
            $groupBlock->save();
            array_push($reservationBlocks, $groupBlock);
        }

        // 1件も予約枠が作成されなかった場合、作成した予約枠グループを削除する。
        if (empty($reservationBlocks)) {
            $reservationRepeatGroup->delete();
        }

        return $reservationBlocks;
    }

    private function editOneReservationBlock(ReservationBlock &$reservationBlock, array $parameters, array $times): bool
    {
        // 過去の予約枠や、すでに予約が入っている予約枠は更新せずスキップする。
        if ($this->isPastTime($reservationBlock) || $reservationBlock->reservedReservationBlocks()->count() > 0) {
            return false;
        }

        $reservationBlock->fill($parameters);
        $reservationBlock->fill($times);
        $reservationBlock->reservation_repeat_group_id = null;
        $reservationBlock->save();

        return true;
    }

    public function detail(Request $request, int $hotelId, int $reservationBlockId): JsonResponse
    {
        $hotel = Hotel::where('id', $hotelId)->where('client_id', $this->user()->id)->first();
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }

        $reservationBlock = $hotel->reservationBlocks()->where('id', $reservationBlockId)->first();
        if (empty($reservationBlock)) {
            return $this->error('データが存在しません', 404);
        }
        $data = [
            'status' => 200,
            'detail' => $this->makeReservationBlock($reservationBlock),
        ];
        return $this->success($data);
    }

    public function delete(int $hotelId, int $reservationBlockId): JsonResponse
    {
        $hotel = Hotel::where('id', $hotelId)->where('client_id', $this->user()->id)->first();
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }

        $reservationBlock = $hotel->reservationBlocks()->where('id', $reservationBlockId)->first();
        if (empty($reservationBlock)) {
            return $this->error('削除できる予約枠がありませんでした', 500);
        }

        // 過去の予約枠や、すでに予約が入っている予約枠は削除せずスキップする。
        if ($this->isPastTime($reservationBlock) || $reservationBlock->reservedReservationBlocks()->count() > 0) {
            return $this->error('削除できる予約枠がありませんでした', 500);
        }

        $reservationBlock->delete();
        return $this->success();
    }

    public function delete_group(int $hotelId, int $reservationBlockId): JsonResponse
    {
        $hotel = Hotel::where('id', $hotelId)->where('client_id', $this->user()->id)->first();
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }

        $reservationBlock = $hotel->reservationBlocks()->where('id', $reservationBlockId)->first();
        if (empty($reservationBlock)) {
            return $this->error('削除できる予約枠がありませんでした', 500);
        }

        $reservationRepeatGroup = $reservationBlock->reservationRepeatGroup;
        if (empty($reservationRepeatGroup)) {
            return $this->error('削除できる予約枠がありませんでした', 500);
        }

        // 過去の予約枠や、すでに予約が入っている予約枠は削除せずスキップする。
        $reservationBlockIds = [];
        foreach ($reservationRepeatGroup->reservationBlocks()->get() as $groupBlock) {
            if (!$this->isPastTime($groupBlock) && $groupBlock->reservedReservationBlocks()->count() == 0) {
                array_push($reservationBlockIds, $groupBlock->id);
            }
        }

        // 全てスキップして削除できる予約枠がない場合はエラー
        if (empty($reservationBlockIds)) {
            return $this->error('削除できる予約枠がありませんでした', 500);
        }

        ReservationBlock::whereIn('id', $reservationBlockIds)->delete();
        return $this->success();
    }

    private function isPastTime(ReservationBlock $reservationBlock): bool
    {
        $now = Carbon::now();
        $today = $now->format('Y-m-d');
        if ($today > $reservationBlock->date) {
            return true;
        } elseif ($today < $reservationBlock->date) {
            return false;
        } else {
            [$hour, $minute] = $this->convertTimeToInt($now->format('H:i'));
            return $reservationBlock->start_hour < $hour || ($reservationBlock->start_hour === $hour && $reservationBlock->start_minute < $minute);
        }
    }

    public function close(ReservationBlockCloseRequest $request, int $hotelId): JsonResponse
    {
        $hotel = Hotel::where('id', $hotelId)->where('client_id', $this->user()->id)->first();
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }

        $reservationBlocks = $hotel->reservationBlocks()->whereIn('id', $request->reservation_block_ids)->get();
        if (count($request->reservation_block_ids) > $reservationBlocks->count()) {
            return $this->error('更新できない予約枠が含まれていました', 500);
        }

        $isClosed = $request->is_closed;
        $retValReservationBlocks = [];
        try {
            \DB::transaction(
                function () use ($reservationBlocks, $isClosed, &$retValReservationBlocks) {
                    foreach ($reservationBlocks as $b) {
                        $b->is_closed = $isClosed;
                        $b->save();
                        array_push($retValReservationBlocks, $this->makeReservationBlock($b));
                    }
                }
            );
        } catch (\Exception $e) {
            Log::info('close failed :' . $e);
            return $this->error('close failed', 500);
        }

        $data = [
            'reservation_blocks' => $retValReservationBlocks,
        ];

        return $this->success($data);
    }

    public function roomNum(ReservationBlockRoomNumRequest $request, int $hotelId): JsonResponse
    {
        $params = $request->only(['room_type_id', 'num', 'date']);
        $clientId = $this->user()->id;

        $ret = $this->reserve_schedule_service->updateRoomNum($hotelId, $clientId, $params);
        extract($ret);

        if (!$isSuccess) {
            return $this->error($message, $code);
        }
        return $this->success($data);
    }
}
