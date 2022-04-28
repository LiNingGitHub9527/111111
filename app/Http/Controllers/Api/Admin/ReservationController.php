<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\ReservationBlock;
use App\Models\ReservationBranch;
use App\Models\ReservationRefund;
use App\Models\ReservationCancelPolicy;
use App\Models\ReservedReservationBlock;
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
    ){
        $this->canpoli_service = app()->make('CancelPolicyService');
        $this->common_service = app()->make('ApiCommonReservationService');
        $this->otherReserveService = $otherReserveService;
        $this->reservation_status_service = app()->make('ReservationStatusService');
    }

    public function list($id, Request $request)
    {
        $hotel = Hotel::find($id);
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }

        $month = $request->get('month');
        if (empty($month)) {
            $monthDay = now()->startOfMonth();
        } else {
            $monthDay = Carbon::parse($month)->startOfMonth();
        }
        $start = $monthDay->format('Y-m-d');
        $end = $monthDay->endOfMonth()->format('Y-m-d') . " 23:59:59:999";

        $query = Reservation::query();
        $reservationId = $request->get('reservation_id');
        if (!empty($reservationId)) {
            $query->where('id', $reservationId);
        }

        $list = $query->where('hotel_id', $id)->whereBetween('checkin_start', [$start, $end])->with('reservationBranches')->orderBy('checkin_start', 'DESC')->paginate(20);
        $records = [];
        foreach ($list as $item) {
            $rooms = [];
            $reservationBranchs = ReservationBranch::where('reservation_id', $item->id)->orderBy('id', 'DESC')->limit($item->room_num)->get();
            foreach ($reservationBranchs as $branch) {
                if ($branch->roomType) {
                    $rooms[] = $branch->roomType->name;
                }
            }
            if ($item->reservationBranches->isNotEmpty()) {
                $canceledStatus = true;
                $reservationStatus = false;
                $reservationChangeStatus = false;
                foreach ($item->reservationBranches as $branch) {
                    if ($branch->reservation_status !== 1) {
                        $canceledStatus = false;
                    }
                    if ($branch->reservation_status !== 0) {
                        $reservationChangeStatus = true;
                    }
                    if ($branch->reservation_status == 0) {
                        $reservationStatus = true;
                    }

                    if ($branch->reservation_status == 2) {
                        $item->reservation_status = 5;
                    }
                }
                if (!$reservationChangeStatus) {
                    $item->reservation_status = 0;
                }
                if ($reservationChangeStatus && $reservationStatus || $branch->tema_reservation_type == 1) {
                    $item->reservation_status = 3;
                }
                if ($canceledStatus) {
                    $item->reservation_status = 4;
                }
            }

            $row = [
                'id' => $item->id,
                'name' => $item->name,
                'checkin_date' => $item->checkinDisplayDate(),
                'checkout_date' => $item->checkoutDisplayDate(),
                'room' => implode(',', $rooms) ?? '',
                'reservation_date' => $item->reservationDisplayDate(),
                'status' => $item->statusDisplayName(),
                'fee' => $item->reservation_status == 0 ? $item->accommodation_price : $item->cancel_fee,
                'commission_price' => $item->accommodation_commission,
                'payment_method' => $item->payment_method,
                'payment_status' => $item->payment_status,
                'approval_status' => $item->approval_status,
                'is_request' => $item->is_request,
                'checkin_time' => Carbon::parse($item->checkin_time)->format('H:i'),
                'reservation_status' => $item->reservation_status,
                'checkout_time' => Carbon::parse($item->checkout_time)->format('H:i'),
                'cancel_date_time' => $item->reservation_status !== 0 ? Carbon::parse($item->cancel_date_time)->format('Y年m月d日 H:i') : '',
            ];
            $records[] = $row;
        }
        $data = [
            'records' => $records,
            'total' => $list->total(),
            'page' => $list->currentPage(),
            'pages' => $list->lastPage(),
            'hotel' => [
                'id' => $hotel->id,
                'name' => $hotel->name,
                'business_type' => $item->hotel->business_type
            ]
        ];
        return $this->success($data);
    }

    public function csvDownload(Request $request)
    {
        ini_set('memory_limit', '1024M');
        $hotelId = $request->get('hotel_id');
        $hotel = Hotel::find($hotelId);
        if (empty($hotel)) {
            exit;
        }
        $month = $request->get('month');
        if (empty($month)) {
            $monthDay = now()->startOfMonth();
        } else {
            $monthDay = Carbon::parse($month)->startOfMonth();
        }
        $start = $monthDay->format('Y-m-d');
        $shortMonth = $monthDay->format('Y-m');
        $end = $monthDay->endOfMonth()->format('Y-m-d');
        $query = Reservation::query();
        $list = $query->where('hotel_id', $hotelId)->whereBetween('checkin_start', [$start, $end])->with('reservationBranches')->orderBy('checkin_start', 'DESC')->get();
        $data = [];
        foreach ($list as $item) {
            $rooms = [];
            $reservationBranchs = ReservationBranch::where('reservation_id', $item->id)->orderBy('id', 'DESC')->limit($item->room_num)->get();
            foreach ($reservationBranchs as $branch) {
                if ($branch->roomType) {
                    $rooms[] = $branch->roomType->name;
                }
            }
            $row = [
                'id' => $item->id,
                'name' => $item->name,
                'checkin_date' => $item->checkinDisplayDate(),
                'checkout_date' => $item->checkoutDisplayDate(),
                'room' => implode(',', $rooms),
                'reservation_date' => $item->reservationDisplayDate(),
                'status' => $item->statusDisplayName(),
                'fee' => $item->accommodation_price,
                'commission' => $item->commission_price,
                'payment_method' => $item->payment_method == 0 ? '現地決済' : '事前決済'
            ];
            $data[] = $row;
        }
        $excelService = ExcelService::instance();
        $fileName = $hotel->name . 'の予約-' . $shortMonth;

        $headData = [
            'id' => '予約ID',
            'name' => '宿泊者名',
            'checkin_date' => 'チェックイン日',
            'checkout_date' => 'チェックアウト日',
            'room' => '部屋',
            'reservation_date' => '予約日',
            'status' => 'ステータス',
            'fee' => '料金',
            'commission' => 'コミッション',
            'payment_method' => '決済方法',
        ];

        return $excelService->simpleDownload($fileName, $headData, $data);
    }

    public function search(Request $request)
    {
        $records = [];
        $q = $request->get('q');
        $hotelId = $request->get('hotel_id');
        if (!empty($q) && !empty($hotelId)) {
            $hotelId = $request->get('hotel_id');
            $month = $request->get('month');
            if (empty($month)) {
                $monthDay = now()->startOfMonth();
            } else {
                $monthDay = Carbon::parse($month)->startOfMonth();
            }
            $start = $monthDay->format('Y-m-d');
            $end = $monthDay->endOfMonth()->format('Y-m-d');
            $query = Reservation::query();
            $list = $query->where('hotel_id', $hotelId)->whereBetween('checkin_start', [$start, $end])->where('name', 'like', '%' . $q . '%')->limit(10)->get();
            if (!empty($list)) {
                foreach ($list as $item) {
                    $records[] = [
                        'id' => $item->id,
                        'title' => $item->name,
                        'description' => $item->checkinDisplayDate() . ' ~ ' . $item->checkoutDisplayDate(),
                        'price' => '¥' . number_format($item->accommodation_price),
                    ];
                }
            }
        }
        $data = [
            'records' => $records,
        ];
        return $this->success($data);
    }

    public function detail($id)
    {
        $reservation = Reservation::find($id);
        $businessType = $reservation->hotel->business_type;
        if (!empty($reservation)) {
            $roomTypeNames = [];
//            $roomTypeName;
            $accommodationNum = 0;
            $reservationBranchs = ReservationBranch::where('reservation_id', $reservation->id)->orderBy('id', 'DESC')->limit($reservation->room_num)->get();
            foreach ($reservationBranchs as $branch) {
                $plan = $branch->plan;
                $roomType = $branch->roomType;
                if ($plan) {
                    $planNames = $plan->name;
                }
                if ($roomType) {
                    $roomTypeNames[] = $roomType->name;
                }
            }
            if ($reservation->reservationBranches->isNotEmpty()) {
                $canceledStatus = true;
                $reservationStatus = false;
                $reservationChangeStatus = false;
                foreach ($reservation->reservationBranches as $reservationBranch) {
                    if ($reservationBranch->reservation_status != 1) {
                        $canceledStatus = false;
                    }
                    if ($reservationBranch->reservation_status !== 0) {
                        $reservationChangeStatus = true;
                    }
                    if ($reservationBranch->reservation_status == 0) {
                        $reservationStatus = true;
                    }
                    if ($reservationBranch->reservation_status == 2) {
                        $reservation->reservation_status = 5;
                    }
                }
                if (!$reservationChangeStatus) {
                    $reservation->reservation_status = 0;
                }
                if ($reservationChangeStatus && $reservationStatus || $reservationBranch->tema_reservation_type == 1) {
                    $reservation->reservation_status = 3;
                }
                if ($canceledStatus) {
                    $reservation->reservation_status = 4;
                }
            }

            $adultNum = $reservation->adult_num ?? 0;
            $childNum = $reservation->child_num ?? 0;
            $accommodationNum += $adultNum + $childNum;

            $accommodationPrice = $reservation->reservation_status == 0 ? $reservation->accommodation_price : $reservation->cancel_fee;
            $detail = [
                'id' => $reservation->id,
                'name' => $reservation->name,
                'check_in' => $reservation->checkinDisplay(),
                'check_out' => $reservation->checkoutDisplay(),
                'plan_names' => $planNames ?? '',
                'room_type_names' => implode(',', $roomTypeNames) ?? '',
                'reservation_date' => $reservation->reservationDisplayDate(),
                'accommodation_price' => $accommodationPrice,
                'commission_price' => $reservation->commission_price,
                'accommodation_num' => $accommodationNum,
                'accommodation_day' => $reservation->accommodationDay(),
                'reservation_status' => $reservation->reservation_status,
                'payment_commission_price' => $reservation->payment_commission_price ?? 0,
                'status' => $reservation->statusDisplayName(),
                'payment_method' => $reservation->payment_method,
                'approval_status' => $reservation->approval_status,
                'is_request' => $reservation->is_request
            ];
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
                    $cancelFee = $isFreeCancelPolicy == 0 ? ceil($accommodationPrice * $cancelChargeRate / 100) : 0;
                } else {
                    $cancelFee = ceil($accommodationPrice * $cancelChargeRate / 100);
                }
                if ($reservation->reservation_status == 2) {
                    $cancelFee = ceil($accommodationPrice * $noShowChargeRate / 100);
                }
                $cp = [
                    'cancel_fee' => $cancelFee,
                    'is_free_cancel' => $isFreeCancelPolicy,
                    'free_day' => $freeDay,
                    'free_time' => $freeTime,
                    'cancel_charge_rate' => $cancelChargeRate,
                    'no_show_charge_rate' => $noShowChargeRate
                ];
            }
            $data = [
                'detail' => $detail,
                'business_type' => $businessType,
                'hotel_id' => $reservation->hotel_id,
                'hotel_name' => $reservation->hotel->name,
                'cancelPolicy' => empty($cp) ? null : $cp
            ];
            return $this->success($data);
        } else {
            return $this->error('データが存在しません', 404);
        }
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

        $reserveService = app()->make('ReserveService');
        if ($status == 1 && Carbon::now()->gt(Carbon::parse($reservation->checkin_time)->endOfDay())) {
            return $this->error('チェックイン日に過ぎましたので、キャンセルできません。', 1400);
        }

        if ($status == 2 && Carbon::now()->gt(Carbon::parse($reservation->checkin_time)->addDays(2)->endOfDay())) {
            return $this->error('チェックイン日より二日間が過ぎましたので、ノーショーとして登録できません。', 1400);
        }

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
                $reservation->cancel_date_time = time();
                if ($status == 1) {
                    $reservation->approval_status = 0;
                }
                $reservation->save();
            });
        } catch (\Exception $e) {
            Log::info('client reservation cancel/now show reservation :' . 'hotelId: ' . $reservation->hotelId . ' / reservationId: ' .  $reservation->id . 'function "changeStatus" error exception: ' . $e);
            return $this->error('システムエラー', 500);
        }
        if ($) {
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
