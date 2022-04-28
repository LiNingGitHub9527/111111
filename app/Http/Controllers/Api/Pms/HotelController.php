<?php

namespace App\Http\Controllers\Api\Pms;

use App\Http\Requests\Api\Pms\HotelRequest;
use App\Models\Hotel;
use App\Services\ClientLogService;
use Carbon\Carbon;

class HotelController extends ApiBaseController
{

    public function save(HotelRequest $request)
    {
        $data = $request->all();

        $logger = ClientLogService::instance($data['client_id'], 'jobs/hotel-save');

        $logger->info('handle begin:' . json_encode($data));

        if (empty($data['tel'])) {
            $data['tel'] = '';
        }

        if (empty($data['address'])) {
            $data['address'] = '';
        }

        if (empty($data['email'])) {
            $data['email'] = '';
        }

        if (empty($data['business_type'])) {
            $data['business_type'] = $data['bussiness_type'];
        }

        $crmBaseId = $data['pms_base_id'];

        $hotel = Hotel::where('crm_base_id', $crmBaseId)->first();
        if (empty($hotel)) {
            $hotel = new Hotel();
            $logger->info("hotel not exists, new one");
        }
        $hotel->fill($data);
        $hotel->crm_base_id = $crmBaseId;
        $hotel->sync_status = 1;
        $hotel->last_sync_time = Carbon::now();
        $hotel->save();
        $logger->info("hotel($hotel->id) handle end");
        return $this->success([
            'nocode_hotel_id' => $hotel->id
        ]);

    }

}
