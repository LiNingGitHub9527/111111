<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Requests\Api\HotelRequest;
use App\Models\Client;
use App\Models\Hotel;
use App\Models\RatePlan;
use App\Services\PmsApi\Sync\HotelService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HotelController extends ApiBaseController
{
    public function list($id, Request $request)
    {
        $client = Client::find($id);

        if (empty($client)) {
            return $this->error('データが存在しません', 404);
        }

        $query = Hotel::query();
        $hotelId = $request->get('hotel_id');
        if (!empty($hotelId)) {
            $query->where('id', $hotelId);
        }

        $list = $query->where('client_id', $id)->orderBy('id', 'DESC')->paginate(20);

        $ratePlans = RatePlan::options(false);

        $records = [];
        foreach ($list as $hotel) {
            $row = [
                'id' => $hotel->id,
                'name' => $hotel->name,
                'tel' => $hotel->tel,
                'email' => $hotel->email,
                'rate_plan' => $ratePlans[$hotel->rate_plan_id] ?? '',
                'person_in_charge' => $hotel->person_in_charge ?? '',
                'bank_code' => $hotel->bank_code,
                'branch_code' => $hotel->branch_code,
                'deposit_type' => getDepositType($hotel->deposit_type),
                'account_number' => $hotel->account_number,
                'recipient_name' => $hotel->recipient_name
            ];
            $records[] = $row;
        }
        $data = [
            'records' => $records,
            'total' => $list->total(),
            'page' => $list->currentPage(),
            'pages' => $list->lastPage(),
            'client_name' => $client->company_name,
        ];
        return $this->success($data);
    }

    public function detail($id)
    {
        $hotel = Hotel::with('client')->find($id);
        if (!empty($hotel)) {
            $ratePlans = RatePlan::options(false);
            $agreementDate = $hotel->agreement_date ? $hotel->agreement_date->format('Y/m/d') : '';
            $ratePlan = $ratePlans[$hotel->rate_plan_id] ?? '';
            $detail = [
                'id' => $hotel->id,
                'name' => $hotel->name,
                'agreement_date' => $agreementDate,
                'address' => $hotel->address,
                'tel' => $hotel->tel,
                'person_in_charge' => $hotel->person_in_charge,
                'email' => $hotel->email,
                'rate_plan' => $ratePlan,
                'rate_plan_id' => $hotel->rate_plan_id,
                'sync_status' => $hotel->sync_status,
                'tema_login_id' => $hotel->tema_login_id,
                'tema_login_password' => $hotel->tema_login_password,
                'business_type' => $hotel->business_type,
                'business_type_name' => $hotel->statusDisplayName(),
                'bank_code' => $hotel->bank_code, 
                'branch_code' => $hotel->branch_code, 
                'deposit_type' => $hotel->deposit_type, 
                'account_number' => $hotel->account_number, 
                'recipient_name' => $hotel->recipient_name
            ];

            $ratePlans = RatePlan::options();
            $data = [
                'detail' => $detail,
                'client_id' => $hotel->client_id,
                'client_name' => $hotel->client->company_name,
                'rate_plans' => $ratePlans,
            ];
            return $this->success($data);
        } else {
            return $this->error('データが存在しません', 404);
        }
    }

    public function update(HotelRequest $request)
    {
        $clientId = $request->get('client_id');
        $client = Client::find($clientId);

        if (empty($client)) {
            return $this->error('データが存在しません', 404);
        }

        $id = $request->get('id');
        $hotel = Hotel::find($id);
        if (!empty($hotel)) {
            $data = $request->all();
            $hotel->fill($data);
            $agreementDate = $request->get('agreement_date');
            if (!empty($agreementDate)) {
                $hotel->agreement_date = Carbon::parse($agreementDate);
            }
            $hotel->save();
            HotelService::instance()->syncHotel($hotel);
            return $this->success();
        } else {
            return $this->error('データが存在しません', 404);
        }
    }

    public function create(HotelRequest $request)
    {
        $clientId = $request->get('client_id');
        $client = Client::find($clientId);

        if (empty($client)) {
            return $this->error('データが存在しません', 404);
        }

        $hotel = new Hotel;
        $data = $request->all();
        $hotel->fill($data);
        $agreementDate = $request->get('agreement_date');
        if (!empty($agreementDate)) {
            $hotel->agreement_date = Carbon::parse($agreementDate);
        }

        try {
            \DB::transaction(function () use ($hotel) {
                $hotel->save();
            });
        } catch (\Exception $e) {
            Log::info('save failed :' . $e);
            return $this->error('save failed', 500);
        }

        HotelService::instance()->syncHotel($hotel);
        return $this->success();
    }

    public function delete($id)
    {
        $hotel = Hotel::find($id);
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }

        try {
            \DB::transaction(function () use ($hotel) {
                $hotel->delete();
            });
        } catch (\Exception $e) {
            Log::info('delete failed :' . $e);
            return $this->error('delete failed', 500);
        }

        HotelService::instance()->syncHotelDelete($hotel);
        return $this->success();
    }

    public function initData($cid)
    {
        $client = Client::find($cid);
        if (!empty($client)) {
            $detail = [
                'id' => $client->id,
                'company_name' => $client->company_name,
                'address' => $client->address,
                'tel' => $client->tel,
                'person_in_charge' => $client->person_in_charge,
                'email' => $client->email,
            ];

            $ratePlans = RatePlan::options();

            $data = [
                'detail' => $detail,
                'rateplans' => $ratePlans,
            ];
            return $this->success($data);
        } else {
            return $this->error('データが存在しません', 404);
        }
    }

    public function search(Request $request)
    {
        $records = [];
        $q = $request->get('q');
        if (!empty($q)) {
            $clientId = $request->get('client_id');
            $query = Hotel::query();
            if (!empty($clientId)) {
                $query->where('client_id', $clientId);
            }
            $list = $query->with('client')->where('name', 'like', '%' . $q . '%')->limit(10)->get();
            if (!empty($list)) {
                $ratePlans = RatePlan::options(false);
                foreach ($list as $item) {
                    $records[] = [
                        'id' => $item->id,
                        'title' => $item->name,
                        'description' => $item->client->company_name,
                        'price' => $ratePlans[$item->rate_plan_id] ?? '',
                    ];
                }
            }
        }
        $data = [
            'records' => $records,
        ];
        return $this->success($data);
    }
}
