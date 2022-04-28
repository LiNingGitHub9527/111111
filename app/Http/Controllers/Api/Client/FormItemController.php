<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Requests\Api\Client\FormItemRequest;
use App\Models\FormItem;
use App\Models\Hotel;
use Illuminate\Http\Request;

class FormItemController extends ApiBaseController
{

    public function list($id, Request $request)
    {
        $hotel = Hotel::where('id', $id)->where('client_id', $this->user()->id)->first();
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }

        $query = FormItem::query();
        $formItemId = $request->get('form_item_id');
        if (!empty($formItemId)) {
            $query->where('id', $formItemId);
        }

        $list = $query->where('hotel_id', $id)->orderBy('id', 'DESC')->paginate(20);

        $records = [];
        foreach ($list as $item) {
            $row = [
                'id' => $item->id,
                'name' => $item->name,
                'item_type' => $item->item_type,
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
            $query = FormItem::query();
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

        $formItem = $hotel->formItems->find($id);
        if (empty($formItem)) {
            return $this->error('データが存在しません', 404);
        }

        $detail = [
            'id' => $formItem->id,
            'name' => $formItem->name,
            'client_id' => $formItem->client_id,
            'hotel_id' => $formItem->hotel_id,
            'required' => $formItem->required,
            'item_type' => $formItem->item_type,
            'option_default' => $formItem->option_default,
            'options' => $formItem->options,
        ];
        $data = $this->getRelatedData($formItem->hotel);
        $data['detail'] = $detail;
        return $this->success($data);
    }

    function getRelatedData($hotel)
    {
        return [
            'hotel_name' => $hotel->name,
        ];
    }

    public function save(FormItemRequest $request)
    {
        $clientId = $this->user()->id;
        $hotelId = $request->get('hotel_id');
        $hotel = Hotel::where('id', $hotelId)->where('client_id', $clientId)->first();
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }

        $id = $request->get('id');
        if (empty($id)) {
            $formItem = new FormItem();
            $formItem->client_id = $clientId;
            $formItem->sort_order = 1;
        } else {
            $formItem = $hotel->formItems->find($id);
            if (empty($formItem)) {
                return $this->error('データが存在しません', 404);
            }
        }
        $data = $request->all();

        if (empty($data['option_default'])) {
            $data['option_default'] = '';
        }
        $formItem->fill($data);
        $formItem->save();
        return $this->success([
            'id' => $formItem->id,
            'name' => $formItem->name,
        ]);
    }

    public function delete($id, Request $request)
    {
        $hotelId = $request->get('hotel_id');
        $item = FormItem::where('id', $id)->where('hotel_id', $hotelId)->where('client_id', $this->user()->id)->first();
        if (empty($item)) {
            return $this->error('データが存在しません', 404);
        }
        $item->delete();
        return $this->success();
    }


}
