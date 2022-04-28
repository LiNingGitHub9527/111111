<?php

namespace App\Http\Controllers\Api\Client;

use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\ReservationRefund;
use App\Models\ReservationCancelPolicy;
use App\Models\ReservedReservationBlock;
use App\Models\ReservationBlock;
use App\Services\ExcelService;
use App\Services\ScEndPoint\Temairazu\TemairazuService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\CommonUseCase\Reservation\BookingCoreController;
use App\Services\commonUseCase\Reservation\Other\OtherReserveService;

class ReservationController extends ApiBaseController
{
    public function __construct(
        OtherReserveService $otherReserveService
    ) {
        $this->common_service = app()->make('ApiCommonReservationService');
        $this->reservation_status_service = app()->make('ReservationStatusService');
        $this->canpoli_service = app()->make('CancelPolicyService');
        $this->otherReservationService = app()->make('ApiOtherReservationService');
        $this->otherReserveService = $otherReserveService;
    }

    public function list($id, Request $request)
    {
        $hotel = Hotel::where('id', $id)->where('client_id', $this->user()->id)->first();
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }
        $isHotel = isHotel($hotel);

        $reservationBlockId = $request->get('reservationBlockId', 0);
        $records = [];
        $clientId = $this->user()->id;
        $isPage = true;
        list($list, $records) = $this->common_service->getPage(
            $request,
            $id,
            $records,
            $isPage,
            $clientId,
            $isHotel,
            $reservationBlockId
        );

        $data = [
            'records' => $records,
            'total' => $list->total(),
            'page' => $list->currentPage(),
            'pages' => $list->lastPage(),
            'hotel' => [
                'id' => $hotel->id,
                'name' => $hotel->name,
                'business_type' => $hotel->business_type ?? 1
            ]
        ];

        return $this->success($data);
    }

    public function csvDownload(Request $request)
    {
        ini_set('memory_limit', '1024M');
        $hotelId = $request->get('hotel_id');
        $hotel = Hotel::where('id', $hotelId)->where('client_id', $this->user()->id)->first();
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }
        $isHotel = isHotel($hotel);

        $records = [];
        $clientId = $this->user()->id;
        $isPage = false;
        list($list, $records) = $this->common_service->getPage($request, $hotelId, $records, $isPage, $clientId, $isHotel);
        $excelService = ExcelService::instance();
        $fileName = $hotel->name . 'の予約-';
        $headData = [
            'id' => '予約ID',
            'name' => '宿泊者名',
            'checkin_date' => 'チェックイン日',
            'checkout_date' => 'チェックアウト日',
            'room' => '部屋',
            'reservation_date' => '予約日',
            'status' => 'ステータス',
            'fee' => '料金',
            'payment_commission_price' => '決済手数料',
            'payment_method' => '決済方法'
        ];

        return $excelService->simpleDownload($fileName, $headData, $records);
    }

    public function check($id)
    {
        return $this->success(Reservation::isNoShow($id));
    }

    public function checkFreeCancel($id)
    {
        $reservation = Reservation::find($id);
        if ($reservation->payment_method == 1) {
            return $this->success(true);
        }
        $cancelPolicyService = app()->make('CancelPolicyService');
        $reservationCancelPolicy = ReservationCancelPolicy::where('reservation_id', $reservation->id)->first();
        $checkinTime = Carbon::parse($reservation->checkin_time)->format('Y-m-d');
        $isFreeCancel = $cancelPolicyService->checkFreeCancelByNow(null, $checkinTime, $reservationCancelPolicy);
        return $this->success($isFreeCancel);
    }

    public function detail($id, Request $request)
    {
        $hotelId = $request->get('hotel_id');
        $clientId = $this->user()->id;
        $hotel = Hotel::where('id', $hotelId)->where('client_id', $clientId)->first();
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }
        $isHotel = isHotel($hotel);

        $reservationQuery = $this->common_service->buildTargetIdQuery($isHotel, $id, $hotelId, $clientId);
        $reservation = $reservationQuery->first();
        if (empty($reservation)) {
            return $this->error('データが存在しません', 404);
        }

        $detail = $this->common_service->makeDetailByReservation($reservation, $isHotel);

        $cancelPolicy = $reservation->reservationCancelPolicy;
        $cp = [];
        if ($cancelPolicy) {
            $isFreeCancelPolicy = $cancelPolicy->is_free_cancel;
            $freeDay = $cancelPolicy->free_day;
            $freeTime = $cancelPolicy->free_time;
            $checkinTime = Carbon::parse($reservation->checkin_time)->format('Y-m-d');
            $cancelChargeRate = $cancelPolicy->cancel_charge_rate;
            $noShowChargeRate = $cancelPolicy->no_show_charge_rate;
            $canpoli_service = app()->make('CancelPolicyService');
            $isFreeCancel = $canpoli_service->checkFreeCancelByNow(null, $checkinTime, $cancelPolicy);
            if ($isFreeCancel) {
                $cancelFee = $isFreeCancelPolicy == 0 ? ceil($detail['accommodation_price'] * $cancelChargeRate / 100) : 0;
            } else {
                $cancelFee = ceil($detail['accommodation_price'] * $cancelChargeRate / 100);
            }
            if ($reservation->reservation_status == 2) {
                $cancelFee = ceil($detail['accommodation_price'] * $noShowChargeRate / 100);
            }
            $cp = [
                'cancel_fee' => $cancelFee,
                'is_free_cancel' => $isFreeCancelPolicy,
                'free_day' => $freeDay,
                'free_time' => $freeTime,
                'cancel_charge_rate' => $cancelChargeRate,
                'no_show_charge_rate' => $noShowChargeRate,
            ];
        }
        $data = [
            'detail' => $detail,
            'business_type' => $hotel->business_type,
            'hotel_id' => $reservation->hotel_id,
            'hotel_name' => $reservation->hotel->name,
            'cancelPolicy' => empty($cp) ? null : $cp
        ];
        return $this->success($data);
    }

    public function changeStatus($id, Request $request)
    {
        $status = $request->get('status');
        if (!in_array($status, [1, 2])) {
            return $this->error('status error', 404);
        }

        $hotelId = $request->get('hotel_id');
        $hotel = Hotel::where('id', $hotelId)->where('client_id', $this->user()->id)->first();
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }
        $isHotel = isHotel($hotel);

        $query = $this->common_service->buildTargetIdQuery($isHotel, $id, $hotelId, $this->user()->id);
        $reservation = $query->first();

        if (empty($reservation)) {
            return $this->error('予約の特定ができませんでした。誤った操作の可能性があります。', 404);
        }
        if ($status == 2 && Carbon::now()->lt(Carbon::parse($reservation->checkin_time)->endOfDay())) {
            return $this->error('チェックイン日を過ぎていないので、ノーショーに変更できません。', 1400);
        }

        if ($status == 1 && Carbon::now()->gt(Carbon::parse($reservation->checkin_time)->endOfDay())) {
            return $this->error('チェックイン日に過ぎましたので、キャンセルできません。', 1400);
        }

        if ($status == 2 && Carbon::now()->gt(Carbon::parse($reservation->checkin_time)->addDays(2)->endOfDay())) {
            return $this->error('チェックイン日より二日間が過ぎましたので、ノーショーとして登録できません。', 1400);
        }

        $reserveService = app()->make('ReserveService');
        $reservation->payment_commission_price = 0;
        if ($reservation->payment_method == 1) {
            $reservationRefund = ReservationRefund::where('reservation_id', $reservation->id)->first();
            if (!empty($reservationRefund)) {
                return $this->error('既にキャンセル、もしくはノーショーの処理済みです', 1400);
            } else {
                if (!$reserveService->cancelReservation($reservation, $status, $isHotel)) {
                    return $this->error('決済処理が失敗しました', 1400);
                }
            }
        } else {
            $reservationCancelPolicy = ReservationCancelPolicy::where('reservation_id', $reservation->id)->first();
            $checkinTime = Carbon::parse($reservation->checkin_time)->format('Y-m-d');
            $isFreeCancel = $this->canpoli_service->checkFreeCancelByNow(null, $checkinTime, $reservationCancelPolicy);
            $cancelAmount = 0;
            if (!$isFreeCancel) {
                if ($status == 1) {
                    $chargeRate = $reservationCancelPolicy->cancel_charge_rate / 100;
                } else {
                    $chargeRate = $reservationCancelPolicy->no_show_charge_rate / 100;
                }
                $cancelAmount = ceil($reservation->accommodation_price * $chargeRate);
            }
            $reservation->cancel_fee = $cancelAmount;
            $reservation->cancel_date_time = Carbon::now();
            $reservation->payment_commission_price = ceil($cancelAmount * config('commission.payment_rate'));
            $reservation->commission_price = ceil($cancelAmount * config('commission.reserve_rate'));
            $reservation->save();
        }
        $reserveService->undoStockByCancel($isHotel, $reservation);

        try {
            \DB::transaction(function () use ($reservation, $status) {
                $reservation->reservation_status = $status;
                if ($status == 1) {
                    $reservation->approval_status = 0;
                }
                $reservation->cancel_date_time = time();
                $reservation->save();
            });
        } catch (\Exception $e) {
            Log::info('client reservation cancel/now show reservation :' . 'hotelId: ' . $reservation->hotelId . ' / reservationId: ' .  $reservation->id . 'function "changeStatus" error exception: ' . $e);
            return $this->error('システムエラー', 500);
        }
        if ($isHotel) {
            TemairazuService::instance()->sendReservationNotification($reservation->client_id, $reservation->id);
        } else {
            $this->otherReserveService->sendCancelNotificationToCRM($reservation);
        }
        return $this->success();
    }

    public function approvalStatus($id, Request $request)
    {
        $param = $request->all();
        try {
            $reservation_block_id = ReservedReservationBlock::where('reservation_id', $id)->first()->reservation_block_id;
            $reservation_block = ReservationBlock::find($reservation_block_id);

            if ($reservation_block['reserved_num'] >= $reservation_block['room_num'] && $param['isApproved'] == 0) {
                $alertmessage = 'このリクエスト予約の予約枠は既に埋まっているため、リクエストを承認できません';
                return $this->error($alertmessage, 1400);
            } else {
                $reservation = Reservation::findOrFail($id);
                $reservation->reservation_status = $param['isApproved'];
                $reservation->approval_status = 2;

                if ($param['isApproved'] == 1 && $reservation->payment_method == 1) { // cancel and card payment method
                    $bookingData = [
                        'cancel_info' => [
                            'is_free_cancel' => true,
                            'cancel_fee' => $reservation->cancel_fee,
                            ]
                        ];
                        $bookingCoreController = new BookingCoreController();
                        $result = $bookingCoreController->cancelRefund($reservation, $bookingData);
                    if (!$result['res']) {
                        return $this->error($result['message']);
                    }
                } elseif ($param['isApproved'] == 0) {
                    $reservation_block->increment('reserved_num');
                }// other status

                $reservation->save();
                $this->reservation_status_service->send($id);
                return $this->success($reservation);
            }
        } catch (\Exception $e) {
            return $e;
        }
    }
}
