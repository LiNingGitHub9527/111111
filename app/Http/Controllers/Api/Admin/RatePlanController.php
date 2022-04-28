<?php

namespace App\Http\Controllers\Api\Admin;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\Api\Admin\RatePlanRequest;
use App\Models\RatePlan;

class RatePlanController extends ApiBaseController
{
    public function list(Request $request)
    {
        $query = RatePlan::query();
        $rateplanId = $request->get('rateplan_id');
        if (!empty($rateplanId)) {
            $query->where('id', $rateplanId);
        }
        $list = $query->orderBy('id', 'DESC')->paginate(20);

        $records = [];
        foreach ($list as $rateplan) {
            $row = [
                'id' => $rateplan->id,
                'name' => $rateplan->name,
                'fee' => $rateplan->fee,
                'status' => $rateplan->is_effective == 1 ? '有効' : '使用不可',
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

    public function options()
    {
        $data = RatePlan::options();
        return $this->success($data);
    }

    public function detail($id)
    {
        $ratePlan = RatePlan::find($id);
        if (!empty($ratePlan)) {
            $detail = [
                'id' => $ratePlan->id,
                'name' => $ratePlan->name,
                'fee' => $ratePlan->fee,
                'is_effective' => $ratePlan->is_effective,
            ];

            $data = [
                'detail' => $detail,
            ];
            return $this->success($data);
        } else {
            return $this->error('データが存在しません', 404);
        }
    }

    public function update(RatePlanRequest $request)
    {
        $id = $request->get('id');
        $ratePlan = RatePlan::find($id);
        if (!empty($ratePlan)) {
            $data = $request->all();
            $ratePlan->fill($data);
            $ratePlan->save();
            return $this->success();
        } else {
            return $this->error('データが存在しません', 404);
        }
    }

    public function create(RatePlanRequest $request)
    {
        $ratePlan = new RatePlan;
        $data = $request->all();
        $ratePlan->fill($data);
        $ratePlan->save();
        return $this->success();
    }

    public function delete($id)
    {
        $beUsed = RatePlan::beUsed($id);
        if ($beUsed) {
            return $this->error('料金プランは利用されていますので、削除できません', 1006);
        }

        $ratePlan = RatePlan::find($id);
        if (!empty($ratePlan)) {
            $ratePlan->delete();
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
            $list = RatePlan::where('name', 'like', '%'.$q.'%')->limit(10)->get();
            if (!empty($list)) {
                foreach ($list as $item) {
                    $records[] = [
                        'id' => $item->id,
                        'title' => $item->name,
                        'price' => '¥'.number_format($item->fee),
                        'description' => $item->is_effective == 1 ? '有効' : '使用不可',
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
        return $this->success(RatePlan::beUsed($id));
    }
}
