<?php

namespace App\Services\Api\Client\Reservation;

use App\Models\Reservation;
use Carbon\Carbon;

class CommonReservationService
{
    const BETWEEN_TYPE = ['checkin_start', 'checkout_end'];
    const HOTEL_QUERY_WITH_LIST = ['reservationBranches'];
    const OTHER_QUERY_WITH_LIST = [
        'reservedBlocks',
        'reservedBlocks.reservationBlock',
        'reservedBlocks.reservationBlock.roomType'
    ];

    public function __construct()
    {
        $this->hotel_service = app()->make('ApiHotelReservationService');
        $this->other_service = app()->make('ApiOtherReservationService');
    }

    public function buildGetQueryByBetween(
        \Carbon\Carbon $start,
        \Carbon\Carbon $end,
        int $hotelId,
        ?string $betWeenType,
        bool $isHotel
    ): \Illuminate\Database\Eloquent\Builder {

        if (!in_array($betWeenType, self::BETWEEN_TYPE)) {
            throw new \Exception('第4引数に渡された値が間違っています。定数BETWEEN_TYPEを参照してください。');
        }
        if ($isHotel) {
            $withList = self::HOTEL_QUERY_WITH_LIST;
        } else {
            $withList = self::OTHER_QUERY_WITH_LIST;
        }

        $query = Reservation::query()
            ->with($withList)
            ->where('hotel_id', $hotelId)
            ->where('reservation_status', 0);

        if (!empty($betWeenType)) {
            $query->whereBetween($betWeenType, [$start, $end]);
        }

        return $query;
    }

    public function getPage(
        \Illuminate\Http\Request $request,
        int $hotelId,
        array $records,
        bool $isPage = true,
        int $clientId,
        bool $isHotel,
        int $blockId = 0
    ): array {
        $query = Reservation::where('hotel_id', $hotelId)->where('client_id', $clientId);
        if (!$isHotel) {
            $query = $this->other_service->buildGetQueryByBlockId($blockId, self::OTHER_QUERY_WITH_LIST, $query);
        }

        $isCheckin = $request->get('isCheckin');
        $searchColumn = 'checkin_start';
        if (!empty($searchColumn) && $isCheckin == 2) {
            $searchColumn = 'checkout_end';
        }

        $startDate = $request->get('startDate');
        if (!empty($startDate) && empty($blockId)) {
            $query->where($searchColumn, '>=', Carbon::parse($startDate)->startOfDay());
        }
        $endDate = $request->get('endDate');
        if (!empty($startDate) && empty($blockId)) {
            $query->where($searchColumn, '<=', Carbon::parse($endDate)->endOfDay());
        }

        $hasReservationStatus = $request->get('hasReservationStatus');
        if (!empty($hasReservationStatus) && $hasReservationStatus) {
            $query->where(function ($query) use ($request) {
                $ok = $request->get('ok');
                if (!empty($ok)) {
                    $query->orWhere('reservation_status', 0);
                }
                $canceled = $request->get('canceled');
                if (!empty($canceled)) {
                    $query->orWhere('reservation_status', 1);
                }
                $noShow = $request->get('noShow');
                if (!empty($noShow)) {
                    $query->orWhere('reservation_status', 2);
                }
            });
        }

        $hasPaymentMethod = $request->get('hasPaymentMethod');
        if (!empty($hasPaymentMethod) && $hasPaymentMethod) {
            $query->where(function ($query) use ($request) {
                $local = $request->get('local');
                if (!empty($local)) {
                    $query->orWhere('payment_method', 0);
                }
                $online = $request->get('online');
                if (!empty($online)) {
                    $query->orWhere('payment_method', 1);
                }
            });
        }

        if ($isPage) {
            $list = $query->paginate(20);
        } else {
            $list = $query->get();
        }

        $records = $this->_buildListRecord($list, $records, $isHotel);

        return [
            $list,
            $records
        ];
    }

    private function _buildListRecord(
        object $list,
        array $records,
        bool $isHotel
    ): array {
        foreach ($list as $item) {
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
            if ($isHotel) {
                $rooms = $this->hotel_service->getRoomsBy($item);
            } else {
                $rooms = $this->other_service->extractRoomTypesBy($item);
            }
            $row = $this->_buildListRow($item, $rooms, $isHotel);

            $records[] = $row;
        }

        return $records;
    }

    private function _buildListRow(
        \App\Models\Reservation $reservation,
        array $rooms,
        bool $isHotel
    ): array {
        $row = [];
        $row = [
            'id' => $reservation->id,
            'name' => $reservation->name,
            'checkin_date' => $reservation->checkinDisplayDate(),
            'checkout_date' => $reservation->checkoutDisplayDate(),
            'checkin_time' => Carbon::parse($reservation->checkin_time)->format('H:i'),
            'checkout_time' => Carbon::parse($reservation->checkout_time)->format('H:i'),
            'room' => implode(',', $rooms) ?? '',
            'reservation_date' => $reservation->reservationDisplayDate(),
            'status' => $reservation->statusDisplayName(),
            'approval_status' => $reservation->approval_status,
            'fee' => $reservation->reservation_status == 0 ? $reservation->accommodation_price : $reservation->cancel_fee,
            'payment_commission_price' => $reservation->payment_commission_price ?? 0,
            'commission_price' => $reservation->commission_price ?? 0,
            'payment_method' => $reservation->payment_method,
            'is_request' => $reservation->is_request,
            'reservation_status' => $reservation->reservation_status,
            'cancel_date_time' => $reservation->reservation_status !== 0 ? Carbon::parse($reservation->cancel_date_time)->format('Y年m月d日 H:i') : '',
        ];

        return $row;
    }

    public function buildTargetIdQuery(
        bool $isHotel,
        int $reservationId,
        int $hotelId,
        int $clientId
    ): \Illuminate\Database\Eloquent\Builder {
        if ($isHotel) {
            $withList = self::HOTEL_QUERY_WITH_LIST;
        } else {
            $withList = self::OTHER_QUERY_WITH_LIST;
        }

        $query = Reservation::with($withList)
            ->where('id', $reservationId)
            ->where('hotel_id', $hotelId)
            ->where('client_id', $clientId);

        return $query;
    }

    public function makeDetailByReservation(
        \App\Models\Reservation $reservation,
        bool $isHotel
    ): array {
        if ($isHotel) {
            $this->hotel_service->addStatusByBranchStatus($reservation);
            list($planNames, $roomTypeNames) = $this->hotel_service->getPlanAndRoomTypeNameFromBranch($reservation);
        } else {
            $roomTypeNames = $this->other_service->extractRoomTypesBy($reservation);
            $planNames = '';
        }
        $accommodationNum = 0;
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
            'payment_commission_price' => $reservation->payment_commission_price ?? 0,
            'accommodation_num' => $accommodationNum,
            'accommodation_day' => $reservation->accommodationDay(),
            'reservation_status' => $reservation->reservation_status,
            'approval_status' => $reservation->approval_status,
            'status' => $reservation->statusDisplayName(),
            'payment_method' => $reservation->payment_method,
            'checkin_time' => $reservation->checkinDisplayTime(),
            'checkout_time' => $reservation->checkoutDisplayTime(),
            'is_request' => $reservation->is_request
        ];

        return $detail;
    }
}
