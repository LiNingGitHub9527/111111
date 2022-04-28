<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Requests\Api\Client\HotelRequest;
use App\Models\Hotel;
use App\Models\HotelKidsPolicy;
use App\Models\HotelNote;
use App\Models\HotelRoomType;
use App\Models\RatePlan;
use App\Services\PmsApi\Sync\HotelService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HotelController extends ApiBaseController
{
    public function list(Request $request)
    {
        $clientId = $this->user()->id;

        $list = Hotel::where('client_id', $clientId)->orderBy('id', 'DESC')->paginate(20);

        $ratePlans = RatePlan::options(false);

        $records = [];
        foreach ($list as $hotel) {
            $row = [
                'id' => $hotel->id,
                'name' => $hotel->name,
                'tel' => $hotel->tel,
                'email' => $hotel->email,
                'rate_plan' => $ratePlans[$hotel->rate_plan_id] ?? '',
                'crm_base_id' => $hotel->crm_base_id,
                'business_type' => $hotel->business_type
            ];
            $records[] = $row;
        }
        $data = [
            'records' => $records,
            'total' => $list->total(),
            'page' => $list->currentPage(),
            'pages' => $list->lastPage(),
        ];
        return $this->success($data);
    }

    public function detail($id)
    {
        $hotel = Hotel::where('id', $id)->where('client_id', $this->user()->id)->first();
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }
        $ratePlans = RatePlan::options(false);
        $ratePlan = $ratePlans[$hotel->rate_plan_id] ?? '';

        $hotelKidsPolicies = [];
        foreach ($hotel->hotelKidsPolicies as $hotelKidsPolicy) {
            if ($hotelKidsPolicy->is_forbidden == 1) {
                $rateType = 3;
            } else {
                if ($hotelKidsPolicy->is_rate == 1) {
                    $rateType = 1;
                } else {
                    $rateType = 2;
                }
            }
            $item = [
                'id' => $hotelKidsPolicy->id,
                'age_start' => $hotelKidsPolicy->age_start,
                'age_end' => $hotelKidsPolicy->age_end,
                'rate_type' => $rateType,
                'fixed_amount' => $hotelKidsPolicy->fixed_amount,
                'rate' => $hotelKidsPolicy->rate,
                'is_all_room' => $hotelKidsPolicy->is_all_room,
                'room_type_ids' => $hotelKidsPolicy->room_type_ids,
                'room_type_names' => HotelRoomType::getNames($hotelKidsPolicy->room_type_ids),
            ];
            $hotelKidsPolicies[] = $item;
        }

        $hotelNotes = [];
        foreach ($hotel->hotelNotes as $hotelNote) {
            $item = [
                'id' => $hotelNote->id,
                'title' => $hotelNote->title,
                'content' => $hotelNote->content
            ];
            $hotelNotes[] = $item;
        }

        $detail = [
            'id' => $hotel->id,
            'name' => $hotel->name,
            'agreement_date' => $hotel->agreement_date,
            'address' => $hotel->address,
            'tel' => $hotel->tel,
            'person_in_charge' => $hotel->person_in_charge,
            'email' => $hotel->email,
            'rate_plan' => $ratePlan,
            'rate_plan_id' => $hotel->rate_plan_id,
            'logo_img' => $hotel->imageSrc(),
            'checkin_start' => $hotel->checkin_start ?? '00:00:00',
            'checkin_end' => $hotel->checkin_end ?? '00:00:00',
            'checkout_end' => $hotel->checkout_end ?? '00:00:00',
            'tema_login_id' => $hotel->tema_login_id,
            'tema_login_password' => $hotel->tema_login_password,
            'is_tax' => $hotel->is_tax,
            'business_type' => $hotel->business_type ?? 1
        ];

        $hotelRoomTypes = [];
        foreach ($hotel->hotelRoomTypes as $hotelRoomType) {
            $item = [
                'id' => $hotelRoomType->id,
                'name' => $hotelRoomType->name,
            ];
            $hotelRoomTypes[] = $item;
        }

        $data = [
            'detail' => $detail,
            'hotel_name' => $hotel->name,
            'hotelKidsPolicies' => $hotelKidsPolicies,
            'hotelNotes' => $hotelNotes,
            'hotelRoomTypes' => $hotelRoomTypes
        ];
        return $this->success($data);
    }

    public function save(HotelRequest $request)
    {
        $id = $request->get('id');
        $hotel = Hotel::where('id', $id)->where('client_id', $this->user()->id)->first();
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }
        $data = $request->all();

        if (!empty($data['logo_img'])) {
            $data['logo_img'] = str_replace(config('aws.url') . '/', '', $data['logo_img']);
        }

        if ($hotel->business_type == 1) {
            $data['checkin_start'] = Carbon::parse($data['checkin_start'])->format('H:i:s');
            $data['checkin_end'] = Carbon::parse($data['checkin_end'])->format('H:i:s');
            $data['checkout_end'] = Carbon::parse($data['checkout_end'])->format('H:i:s');
        } else{
            $data['checkin_start'] = explode(' ', $data['checkin_start'])[1];
            $data['checkin_end'] = explode(' ', $data['checkin_end'])[1];
            $data['checkout_end'] = explode(' ', $data['checkout_end'])[1];
        }

        try {
            \DB::transaction(function () use ($hotel, $data) {
                $hotel->fill($data);
                $hotel->save();
                HotelService::instance()->syncHotel($hotel);

                $hkpRemovedIds = $hotel->hotelKidsPolicies->pluck('id');
                $hotelKidsPolicies = $data['hotelKidsPolicies'];
                foreach ($hotelKidsPolicies as $item) {
                    if (empty($item['id'])) {
                        $hotelKidsPolicy = new HotelKidsPolicy();
                    } else {
                        $itemId = $item['id'];
                        $hotelKidsPolicy = HotelKidsPolicy::find($itemId);
                        $hkpRemovedIds = $hkpRemovedIds->reject(function ($value) use ($itemId) {
                            return $value == $itemId;
                        });
                    }
                    $hotelKidsPolicy->fillData($item);
                    $hotelKidsPolicy->hotel_id = $hotel->id;
                    $hotelKidsPolicy->save();
                }
                if (!empty($hkpRemovedIds) && $hkpRemovedIds->count() > 0) {
                    HotelKidsPolicy::whereIn('id', $hkpRemovedIds)->delete();
                }

                $hnRemovedIds = $hotel->hotelNotes->pluck('id');
                $hotelNotes = $data['hotelNotes'];
                foreach ($hotelNotes as $item) {
                    if (empty($item['id'])) {
                        $hotelNote = new HotelNote();
                    } else {
                        $itemId = $item['id'];
                        $hotelNote = HotelNote::find($itemId);
                        $hnRemovedIds = $hnRemovedIds->reject(function ($value) use ($itemId) {
                            return $value == $itemId;
                        });
                    }
                    $hotelNote->fill($item);
                    $hotelNote->hotel_id = $hotel->id;
                    $hotelNote->save();
                }
                if (!empty($hnRemovedIds) && $hnRemovedIds->count() > 0) {
                    HotelNote::whereIn('id', $hnRemovedIds)->delete();
                }
            });
        } catch (\Exception $e) {
            Log::info('save hotel failed :' . $e);
            return $this->error();
        }
        return $this->success();
    }


    public function delete($id)
    {
        $hotel = Hotel::where('id', $id)->where('client_id', $this->user()->id)->first();
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
}
