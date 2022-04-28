<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Requests\Api\Client\ClientFormRequest;
use App\Models\Form;
use App\Models\Hotel;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FormController extends ApiBaseController
{
    public function __construct()
    {
        $this->hotel_service = app()->make('ApiHotelFormService');
        $this->other_service = app()->make('ApiOtherFormService');
    }

    public function list($id, Request $request)
    {
        $hotel = Hotel::where('id', $id)->where('client_id', $this->user()->id)->first();
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }

        $query = Form::query();
        $formId = $request->get('form_id');
        if (!empty($formId)) {
            $query->where('id', $formId);
        }

        $list = $query->where('client_id', $this->user()->id)->where('hotel_id', $id)->orderBy('id', 'DESC')->paginate(20);

        $records = [];
        foreach ($list as $item) {
            $row = [
                'id' => $item->id,
                'name' => $item->name,
                'public_status' => $item->public_status,
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
            $query = Form::query();
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

        $data = $this->_getRelatedData($hotel);

        return $this->success($data);
    }

    public function detail($id, Request $request)
    {
        $hotelId = $request->get('hotel_id');
        $hotel = Hotel::where('id', $hotelId)->where('client_id', $this->user()->id)->first();
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }

        $form = $hotel->forms->find($id);
        if (empty($form)) {
            return $this->error('データが存在しません', 404);
        }

        $data = $this->_getRelatedData($hotel);
        $data['detail'] = [
            'id' => $form->id,
            'name' => $form->name,
            'form_parts_ids' => $form->form_parts_ids,
            'is_deadline' => $form->is_deadline,
            'deadline_start' => Carbon::parse($form->deadline_start),
            'deadline_end' => Carbon::parse($form->deadline_end),
            'is_sale_period' => $form->is_sale_period,
            'sale_period_start' => Carbon::parse($form->sale_period_start),
            'sale_period_end' => Carbon::parse($form->sale_period_end),
            'is_plan' => $form->is_plan,
            'plan_ids' => $form->plan_ids,
            'is_room_type' => $form->is_room_type,
            'room_type_ids' => $form->room_type_ids,
            'is_special_price' => $form->is_special_price,
            'is_hand_input' => $form->is_hand_input,
            'hand_input_room_prices' => $form->hand_input_room_prices,
            'is_all_plan' => $form->is_all_plan,
            'all_plan_price' => $form->all_plan_price,
            'all_room_type_price' => $form->all_room_type_price,
            'special_plan_prices' => $form->special_plan_prices,
            'all_special_plan_prices' => $form->all_special_plan_prices,
            'custom_form_item_ids' => $form->custom_form_item_ids,
            'is_all_room_price_setting' => $form->is_all_room_price_setting,
            'all_room_price_setting' => $form->all_room_price_setting,
            'special_room_price_settings' => $form->special_room_price_settings,
            'cancel_policy_id' => $form->cancel_policy_id,
            'prepay' => $form->prepay,
            'public_status' => $form->public_status,
            'is_request_reservation' => $form->is_request_reservation,
            'used' => Form::beUsed($form->id),
            'business_type' => $hotel->business_type,
        ];

        return $this->success($data);
    }

    private function _getRelatedData(object $hotel): array
    {
        $isHotel = $hotel->business_type == 1 ? true : false;
        if ($isHotel) {
            $data = $this->hotel_service->getRelatedData($hotel);
        } else {
            $data = $this->other_service->getRelatedData($hotel);
        }

        return $data;
    }

    public function save(ClientFormRequest $request)
    {
        $clientId = $this->user()->id;
        $hotelId = $request->get('hotel_id');
        $hotel = Hotel::where('id', $hotelId)->where('client_id', $clientId)->first();
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }

        $id = $request->get('id');
        if (empty($id)) {
            $form = new Form();
            $form->client_id = $clientId;
        } else {
            $form = $hotel->forms->find($id);
            if (empty($form)) {
                return $this->error('データが存在しません', 404);
            }
        }
        $data = $request->all();

        $deadlineStart = $data['deadline_start'];
        if (!empty($deadlineStart)) {
            $data['deadline_start'] = Carbon::parse($deadlineStart);
        }
        $deadlineEnd = $data['deadline_end'];
        if (!empty($deadlineEnd)) {
            $data['deadline_end'] = Carbon::parse($deadlineEnd);
        }
        $salePeriodStart = $data['sale_period_start'];
        if (!empty($salePeriodStart)) {
            $data['sale_period_start'] = Carbon::parse($salePeriodStart);
        }
        $salePeriodEnd = $data['sale_period_end'];
        if (!empty($salePeriodEnd)) {
            $data['sale_period_end'] = Carbon::parse($salePeriodEnd);
        }

        $form->fill($data);
        $form->filterData();
        $form->save();

        $data = [
            'id' => $form->id,
            'name' => $form->name
        ];

        return $this->success($data);
    }

    public function delete($id, Request $request)
    {
        $hotelId = $request->get('hotel_id');
        $form = Form::where('id', $id)->where('hotel_id', $hotelId)->where('client_id', $this->user()->id)->first();
        if (empty($form)) {
            return $this->error('データが存在しません', 404);
        }
        $beUsed = Form::beUsed($id);
        if ($beUsed) {
            return $this->error('フォームは利用されていますので、削除できません', 1006);
        }
        $form->delete();
        return $this->success();
    }

    public function options($id)
    {
        $hotel = Hotel::where('id', $id)->where('client_id', $this->user()->id)->first();
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }

        $data = [
            'options' => Form::options($hotel)
        ];
        return $this->success($data);
    }

    public function check($id)
    {
        return $this->success(Form::beUsed($id));
    }

}
