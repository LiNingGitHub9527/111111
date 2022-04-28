<?php

namespace App\Services\commonUseCase\Reservation\Other;

use App\Jobs\Mail\User\Other\ReserveOtherJob;
use App\Models\BaseCustomerItemValue;
use App\Models\Hotel;
use App\Models\ReservationBlock;
use App\Models\ReservedReservationBlock;
use App\Support\Api\ApiClient;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use App\Jobs\Pms\ReservationCancelJob;

class OtherReserveService
{
    public function __construct()
    {
    }

    /**
     * 渡されたreservationに紐づくreservation_blocksの枠数を元に戻す
     *
     * @param \App\Models\Reservation $reservation
     * @return boolean
     */
    public function increaseReserveBlockByReservation(
        \App\Models\Reservation $reservation
    ): bool {
        // 予約された予約枠を取得する
        $reservedBlocks = $reservation->reservedBlocks;
        $reservationBlockIds = [];
        foreach ($reservedBlocks as $reservedBlock) {
            $reserveBlock = $reservedBlock->reservationBlock;
            $reservationBlockIds[] = $reserveBlock->id;
        }
        $reservationBlocks = ReservationBlock::where('hotel_id', $reservation->hotel_id)
            ->whereIn('id', $reservationBlockIds)
            ->get();

        // 取得した予約枠（reservation_blocks）を１レコードずつキャンセル処理する
        foreach ($reservationBlocks as $reservationBlock) {
            $reservationBlock->cancel();
        }

        // 予約済みの予約枠を論理削除状態にする
        ReservedReservationBlock::where('reservation_id', $reservation->id)->delete();

        return true;
    }

    /**
     * 渡された予約に対して、CRMへキャンセル通知をAPIリクエスト
     *
     * @param \App\Models\Reservation $reservation
     * @return void
     */
    public function sendCancelNotificationToCRM(
        \App\Models\Reservation $reservation
    ): void {
        $data = [
            "id" => $reservation->id,
            "reservation_kinds" => 3
        ];

        dispatch_now(new ReservationCancelJob($data));
    }

    // 予約可能な予約枠を取得する
    public function getReservationBlocks(array $selectedRooms): Collection
    {
        $ids = collect($selectedRooms)->pluck('reservation_block_id');
        $blocks = ReservationBlock::with('roomType')
            ->whereIn('id', $ids)
            ->where('room_num', '>', 0)
            ->where('is_closed', 0)
            ->where('is_available', 1)
            ->orderBy('date', 'asc')->orderBy('start_hour', 'asc')->orderBy('start_minute', 'asc')
            ->get();
        return $blocks;
    }

    /**
     * base_customer_item_valuesテーブルへデータを保存する
     *
     * @param integer $reserveId 予約ID
     * @param array $params
     * @param array $baseCustomerItems
     * @return boolean falseの場合、DBへの保存エラー
     */
    public function saveBaseCustomerItemValues(int $reserveId, array $params, array $baseCustomerItems): bool
    {
        $items = BaseCustomerItemValue::where('reservation_id', $reserveId)->get();
        if ($items->isEmpty()) {
            // 新規登録
            return $this->_saveBaseCustomerItemValues($reserveId, $params, $baseCustomerItems);
        } else {
            // 更新
            try {
                // 登録されていない場合はinsert
                $existsIds = array_column($items->toArray(), 'base_customer_item_id');
                $notExistsItems = collect($baseCustomerItems)->filter(function($item) use ($existsIds, $params) {
                    return !in_array($item['id'], $existsIds) && !empty($params['item_' . $item['id']]);
                })->toArray();
                if (!empty($notExistsItems)
                    && !$this->_saveBaseCustomerItemValues($reserveId, $params, $notExistsItems)) {
                    return false;
                }
                // 登録されている場合は update or delete
                collect($items)->each(function($item) use ($params) {
                    $id = $item->base_customer_item_id;
                    $itemId = 'item_' . $id;
                    $value = $params[$itemId] ?? '';
                    if ($item->data_type == 6 && !empty($value)) {
                        $value = Carbon::parse($params[$itemId])->format('Y-m-d H:i:s');
                    }
                    if (empty($value)) {
                        // 空になった場合は論理削除
                        $item->delete();
                    } elseif ($item->value != $value) {
                        $item->update(['value' => $value]);
                    }
                });
                return true;
            } catch (\Exception $e) {
                return false;
            }
        }
    }

    private function _saveBaseCustomerItemValues(int $reserveId, array $params, array $items): bool
    {
        $saveDataList = collect($items)->map(function($item) use ($reserveId, $params) {
            $id = $item['id'];
            $dataType = $item['data_type'];
            $itemId = 'item_' . $id;
            $value = $params[$itemId] ?? '';
            if ($dataType == 6 && !empty($value)) {
                $value = Carbon::parse($value)->format('Y-m-d H:i:s');
            }
            return [
                'reservation_id' => $reserveId,
                'base_customer_item_id' => $id,
                'name' => $item['name'],
                'data_type' => $dataType,
                'value' => $value,
            ];
        })->filter(function($item) {
            return !empty($item['value']);
        })->toArray();
        if (empty($saveDataList)) {
            return true;
        }
        return BaseCustomerItemValue::insert($saveDataList);
    }

    // 予約完了時に確認メール送信
    public function sendMail(string $verifyToken, array $data, Hotel $hotel, int $reserveId, string $type='confirm'): string
    {
        try {
            $userShowUrl = route('user.other.booking_show', $verifyToken);
            if($type=='request'){
				$subject = '【予約受け付け】「'. $hotel->name . '」の予約申請を受け付けました';
			}else if ($type == 'confirm') {
                $subject = '【新規ご予約】「'. $hotel->name . '」のご予約が完了しました';
            } elseif ($type == 'update') {
                $subject = '【ご予約の変更】「' . $hotel->name . '」のご予約を変更しました';
            } else {
                $subject = '';
            }
            $res = dispatch_now(
                        new ReserveOtherJob(
                            $userShowUrl,
                            $data['email'],
                            $data['reservation_code'], $data['accommodation_price'],
                            $data['payment_method'], $hotel,
                            $data['checkin_time'], $data['checkout_time'],
                            $reserveId, $subject,($type=='request'?'user/booking/other/email/reservation_request_confirm':'user/booking/other/email/confirm')));
            return $userShowUrl;
        } catch (\Exception $e)  {
            return $userShowUrl;
        }
    }


    /**
     * CRM(PMS)側へ予約データを同期する
     *
     * @param array $bookingData 予約のセッション
     * @param Hotel $hotel 施設
     * @param array $baseCustomerItems 予約入力項目
     * @param integer $reserveId 予約ID
     * @param array $saveReserveData 予約IDに紐づく予約データ
     * @param array $params リクエストパラメータ
     * @param integer $reservationKinds 1: 新規登録, 2: 更新
     * @return void
     */
    public function savePmsReservationData(array $bookingData, Hotel $hotel,
        array $baseCustomerItems, int $reserveId, array $saveReserveData, array $params,
        int $reservationKinds,?int $lineUserId)
    {
        $selectedRooms = $bookingData['selected_rooms'];
        $baseCustomerItemValues = [];
        foreach ($baseCustomerItems as $item) {
            $value = [
                'base_customer_item_id'=> $item['id'],
                'data_type'=> $item['data_type'],
            ];
            $key = 'item_' . $item['id'];
            if (array_key_exists($key, $params)) {
                $value['value'] = $params[$key];
            }
            $baseCustomerItemValues[] = $value;
        };
        $reservationRoomType = [];
        foreach ($selectedRooms as $value) {
            $reservationRoomType[] = [
                "room_type_id" => $value['room_type_id'],
                "room_type_name" => $value['room_name'],
                "date" => $value['date'],
            ];
        };

        $saveReservationData = [
            'id' => $reserveId,
            'crm_base_id'=> $hotel->crm_base_id,
            'reservation_kinds'=> $reservationKinds,
            'line_user_id'=> $lineUserId,
            'name'=> $saveReserveData['name'],
            'tel'=> $saveReserveData['tel'],
            'email'=> $saveReserveData['email'],
            'address'=> $saveReserveData['address'],
            'checkin_date'=> $selectedRooms[0]['date'],
            'start_time'=> $selectedRooms[0]['start_time'],
            'end_time'=> $selectedRooms[0]['end_time'],
            'price'=> $bookingData['room_amount']['sum'],
            'person_num'=> $selectedRooms[0]['person_num'],
            'base_customer_item_values'=> $baseCustomerItemValues,
            "reservation_room_types"=> $reservationRoomType
        ];

        $apiClient = new ApiClient(config('signature.gb_signature_api_key'), $saveReservationData);
        return $apiClient->doRequest('save_reservation'. '?' . $apiClient->getUrlParams());
    }

    /**
     * セッション時刻が有効期限切れであるかをチェックして返却する
     *
     * @param string $time セッション時刻
     * @param integer $interval チェックする経過時間
     * @return boolean true: 有効期限切れ, false: 有効期限内
     */
    public function checkSessionTimeOut(string $time, int $interval = 15): bool
    {
        return Carbon::now()->diffInMinutes($time) >= $interval;
    }

}
