<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Requests\Api\Admin\ComponentRequest;
use App\Models\Component;
use App\Models\Hotel;
use Illuminate\Http\Request;

class ComponentController extends ApiBaseController
{
    public function list(Request $request)
    {
        $query = Component::query();
        $componentId = $request->get('component_id');
        if (!empty($componentId)) {
            $query->where('id', $componentId);
        }

        $list = $query->orderBy('sort_num', 'DESC')->paginate(20);

        $records = [];
        foreach ($list as $component) {
            $row = [
                'id' => $component->id,
                'name' => $component->name,
                'layoutNames' => $component->layouts->pluck('name')->toArray(),
                'public_status' => $component->public_status == 1 ? '公開中' : '非公開',
                'sort_num' => $component->sort_num,
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
        $component = Component::find($id);
        if (!empty($component)) {
            $detail = [
                'id' => $component->id,
                'name' => $component->name,
                'html' => $component->html,
                'type' => $component->type,
                'data' => $component->data,
                'public_status' => $component->public_status,
                'business_types_name' => $component->statusDisplayName(),
                'business_types' => $component->business_types,
                'sort_num' => $component->sort_num,
                'is_limit_hotel' => $component->is_limit_hotel,
                'hotel_ids' => $component->hotel_ids,
            ];

            $data = [
                'detail' => $detail,
            ];
            return $this->success($data);
        } else {
            return $this->error('データが存在しません', 404);
        }
    }

    public function update(ComponentRequest $request)
    {
        $id = $request->get('id');
        $component = Component::find($id);
        if (!empty($component)) {
            $data = $request->all();
            if (empty($data['business_types'])) {
                $data['business_types'] = $data['businessIds'];
            }
            $component->fill($data);
            $component->filterData();
            $component->save();
            return $this->success();
        } else {
            return $this->error('データが存在しません', 404);
        }
    }

    public function create(ComponentRequest $request)
    {
        $component = new Component;
        $data = $request->all();
        if (empty($data['business_types'])) {
            $data['business_types'] = $data['businessIds'];
        }
        $component->fill($data);
        $component->filterData();
        $component->save();
        return $this->success();
    }

    public function delete($id)
    {
        $beUsed = Component::beUsed($id);
        if ($beUsed) {
            return $this->error('コンポーネントは利用されていますので、削除できません', 1006);
        }

        $component = Component::find($id);
        if (!empty($component)) {
            $component->delete();
            return $this->success();
        } else {
            return $this->error('データが存在しません', 404);
        }
    }

    public function search(Request $request)
    {
        $records = [];
        $q = $request->get('q');
        if (!empty($q)) {
            $list = Component::where('name', 'like', '%' . $q . '%')->limit(10)->get();
            if (!empty($list)) {
                foreach ($list as $item) {
                    $records[] = [
                        'id' => $item->id,
                        'title' => $item->name,
                        'description' => ($item->public_status == 1 ? '公開中' : '非公開') . '/' . ($item->type == 1 ? '通常コンポーネント' : 'ポップアップ'),
                    ];
                }
            }
        }
        $data = [
            'records' => $records,
        ];
        return $this->success($data);
    }

    public function check($id)
    {
        return $this->success(Component::beUsed($id));
    }

    public function getHotels()
    {
        $hotels = Hotel::select('id', 'name')->get();
        $data = [
            'hotels' => $hotels
        ];
        return $this->success($data);
    }

}
