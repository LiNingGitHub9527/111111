<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Requests\Api\Client\CancelPolicyRequest;
use App\Models\CancelPolicy;
use App\Models\Hotel;
use Illuminate\Http\Request;

class CancelPolicyController extends ApiBaseController
{

    public function list($id, Request $request)
    {
        $hotel = Hotel::where('id', $id)->where('client_id', $this->user()->id)->first();

        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }

        $query = CancelPolicy::query();
        $cancelPolicyId = $request->get('cancel_policy_id');
        if (!empty($cancelPolicyId)) {
            $query->where('id', $cancelPolicyId);
        }

        $list = $query->where('hotel_id', $id)->orderBy('id', 'DESC')->paginate(20);

        $records = [];
        foreach ($list as $item) {
            $row = [
                'id' => $item->id,
                'name' => $item->name,
                'hotel_id' => $item->hotel_id,
                'is_free_cancel' => $item->is_free_cancel,
                'free_day' => $item->free_day,
                'free_time' => $item->free_time,
                'cancel_charge_rate' => $item->cancel_charge_rate,
                'no_show_charge_rate' => $item->no_show_charge_rate,
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
            ]
        ];
        return $this->success($data);
    }

    public function search(Request $request)
    {
        $records = [];
        $q = $request->get('q');
        $hotelId = $request->get('hotel_id');
        if (!empty($q) && !empty($hotelId)) {
            $hotelId = $request->get('hotel_id');
            $query = CancelPolicy::query();
            $list = $query->where('hotel_id', $hotelId)->where('name', 'like', '%' . $q . '%')->limit(10)->get();
            if (!empty($list)) {
                foreach ($list as $item) {
                    $records[] = [
                        'id' => $item->id,
                        'title' => $item->name,
                    ];
                }
            }
        }
        $data = [
            'records' => $records,
        ];
        return $this->success($data);
    }

    public function init($id)
    {
        $hotel = Hotel::where('id', $id)->where('client_id', $this->user()->id)->first();
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }
        return $this->success($this->getRelatedData($hotel));
    }

    public function detail($id, Request $request)
    {
        $hotelId = $request->get('hotel_id');
        $hotel = Hotel::where('id', $hotelId)->where('client_id', $this->user()->id)->first();
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }

        $cancelPolicy = $hotel->cancelPolicies->find($id);
        if (empty($cancelPolicy)) {
            return $this->error('データが存在しません', 404);
        }
        $data = $this->getRelatedData($hotel);
        $data['detail'] = [
            'id' => $cancelPolicy->id,
            'name' => $cancelPolicy->name,
            'hotel_id' => $cancelPolicy->hotel_id,
            'is_free_cancel' => $cancelPolicy->is_free_cancel,
            'free_day' => $cancelPolicy->free_day,
            'free_time' => $cancelPolicy->free_time,
            'cancel_charge_rate' => $cancelPolicy->cancel_charge_rate,
            'no_show_charge_rate' => $cancelPolicy->no_show_charge_rate,
            'is_default' => $cancelPolicy->is_default
        ];
        return $this->success($data);
    }

    function getRelatedData($hotel)
    {
        return [
            'hotel_name' => $hotel->name,
        ];
    }

    public function save(CancelPolicyRequest $request)
    {
        $hotelId = $request->get('hotel_id');
        $hotel = Hotel::where('id', $hotelId)->where('client_id', $this->user()->id)->first();
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }

        $id = $request->get('id');
        $data = $request->all();

        if($data['is_default'] == "1") {
            $hotel->cancelPolicies()->update([
                'is_default' => 0
            ]);
        }

        if (empty($id)) {
            $cancelPolicy = new CancelPolicy();
            $cancelPolicy->free_time = 0;
        } else {
            $cancelPolicy = $hotel->cancelPolicies->find($id);
            if (empty($cancelPolicy)) {
                return $this->error('データが存在しません', 404);
            }
        }

        if ($data['free_day'] > 0) {
            $data['free_time'] = 0;
        }
        $cancelPolicy->fill($data);
        $cancelPolicy->save();

        return $this->success();
    }

    public function delete($id, Request $request)
    {
        $hotelId = $request->get('hotel_id');
        $hotel = Hotel::where('id', $hotelId)->where('client_id', $this->user()->id)->first();
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }

        $cancelPolicy = $hotel->cancelPolicies->find($id);
        if (empty($cancelPolicy)) {
            return $this->error('データが存在しません', 404);
        }

        $beUsed = CancelPolicy::beUsed($id);
        if ($beUsed) {
            return $this->error('キャンセルポリシーは利用されていますので、削除できません', 1006);
        }

        $cancelPolicy->delete();
        return $this->success();
    }

    public function check($id)
    {
        return $this->success(CancelPolicy::beUsed($id));
    }


}
