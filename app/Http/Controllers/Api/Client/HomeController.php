<?php

namespace App\Http\Controllers\Api\Client;

use App\Models\ClientApiToken;
use App\Models\Hotel;
use App\Support\Api\ApiClient;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HomeController extends ApiBaseController
{
    public function __construct()
    {
        $this->hotel_service = app()->make('ApiHotelHomeService');
        $this->other_service = app()->make('ApiOtherHomeService');
        $this->common_reservation_service = app()->make('ApiCommonReservationService');
    }

    public function init(Request $request, $id)
    {
        $hotel = Hotel::where('id', $id)->where('client_id', $this->user()->id)->first();
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }
        $isHotel = isHotel($hotel);

        $pageSize = $request->get('pageSize');
        if (empty($pageSize)) {
            $pageSize = 2;
        }

        $start = Carbon::now()->startOfDay();
        $end = Carbon::now()->endOfDay();
        
        $checkinListQuery = $this->common_reservation_service->buildGetQueryByBetween($start, $end, $id, 'checkin_start', $isHotel);
        $checkinList = $checkinListQuery->paginate($pageSize);
        $checkoutListQuery = $this->common_reservation_service->buildGetQueryByBetween($start, $end, $id, 'checkout_end', $isHotel);
        $checkoutList = $checkoutListQuery->paginate($pageSize);

        if ($isHotel) {
            $checkinRecords = $this->hotel_service->buildListRecord($checkinList);
            $checkoutRecords = $this->hotel_service->buildListRecord($checkoutList);
        } else {
            $checkinRecords = $this->other_service->buildListRecord($checkinList);
            $checkoutRecords = $this->other_service->buildListRecord($checkoutList);
        }

        $hotelCount = $hotel->client->hotels->count();
        $detail = [
            'id' => $hotel->id,
            'name' => $hotel->name,
            'crm_base_id' => $hotel->crm_base_id
        ];

        $data = [
            'detail' => $detail,
            'checkinRecords' => $checkinRecords,
            'checkinTotal' => $checkinList->total(),
            'checkoutRecords' => $checkoutRecords,
            'checkoutTotal' => $checkoutList->total(),
            'hotelCount' => $hotelCount
        ];

        return $this->success($data);
    }

    public function list(Request $request, $id)
    {
        $hotel = Hotel::where('id', $id)->where('client_id', $this->user()->id)->first();
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }
        $isHotel = isHotel($hotel);

        $betWeenType = '';
        $isCheckin = $request->get('isCheckin');
        if (!empty($isCheckin)) {
            $start = Carbon::now()->startOfDay();
            $end = Carbon::now()->endOfDay();

            if ($isCheckin == 1) {
                $betWeenType = 'checkin_start';
            } else {
                $betWeenType = 'checkout_end';
            }
        }
        $query = $this->common_reservation_service->buildGetQueryByBetween($start, $end, $id, $betWeenType, $isHotel);
        $pageSize = $request->get('pageSize');
        if (empty($pageSize)) {
            $pageSize = 2;
        }
        $list = $query->paginate($pageSize);

        if ($isHotel) {
            $records = $this->hotel_service->buildListRecord($list);
        } else {
            $records = $this->other_service->buildListRecord($list);
        }
        $data = [
            'records' => $records,
            'total' => $list->total(),
            'page' => $list->currentPage(),
            'pages' => $list->lastPage(),
        ];

        return $this->success($data);
    }

    public function pms(Request $request, $id)
    {
        $hotel = Hotel::find($id);
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }

        if (empty($hotel->crm_base_id) || $hotel->sync_status == 0) {
            return $this->error('データが存在しません', 404);
        }

        $redirectUrl = $request->get('redirectUrl');

        if (empty($redirectUrl)) {
            return $this->error('データが存在しません', 404);
        }
        $redirectUrl = str_replace('{hotelId}', $hotel->crm_base_id, $redirectUrl);

        $params = [
            'hotel_id' => $id,
            'redirect_url' => $redirectUrl
        ];

        $token = $request->get('token');
        $isMain = substr($token, strrpos($token, '_') + 1) == 0;
        $clientApiToken = ClientApiToken::where('api_token', $token)->first();
        if ($isMain) {
            $params['main_api_token'] = $token;
        }
        $pmsUserId = $clientApiToken->pms_user_id;
        if (!empty($pmsUserId)) {
            $params['pms_user_id'] = $pmsUserId;
        }
        $apiClient = new ApiClient(config('signature.gb_signature_api_key'), $params);
        $pmsPath = $apiClient->getPath('login') . '?' . $apiClient->getUrlParams();
        $data = [
            'pms_path' => $pmsPath
        ];

        return $this->success($data);
    }


}