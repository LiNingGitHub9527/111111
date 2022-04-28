<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Requests\Api\Admin\LpCategoryRequest;
use App\Models\LpCategory;
use App\Models\Hotel;
use Illuminate\Http\Request;

class LpCategoryController extends ApiBaseController
{
    public function __construct()
    {

    }

    public function list(Request $request)
    {
        $query = LpCategory::query();
        $categoryId = $request->get('category_id');
        if (!empty($categoryId)) {
            $query->where('id', $categoryId);
        }
        $list = $query->orderBy('id', 'DESC')->paginate(20);
        $records = [];
        foreach ($list as $category) {
            $row = [
                'id' => $category->id,
                'name' => $category->name,
                'status' => $category->is_effective == 1 ? '有効' : '使用不可',
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

    public function create(LpCategoryRequest $request)
    {
        $category = new LpCategory;
        $data = $request->all();
        $category->fill($data);
        $category->save();

        return $this->success();
    }

    public function detail($id)
    {
        $category = LpCategory::find($id);
        if (!empty($category)) {
            $detail = [
                'id' => $category->id,
                'name' => $category->name,
                'is_effective' => $category->is_effective,
            ];
            $data = [
                'detail' => $detail,
            ];
            return $this->success($data);
        } else {
            return $this->error('データが存在しません', 404);
        }
    }

    public function update(LpCategoryRequest $request)
    {
        $id = $request->get('id');
        $category = LpCategory::find($id);
        if (!empty($category)) {
            $data = $request->all();
            $category->fill($data);
            $category->save();
            return $this->success();
        } else {
            return $this->error('データが存在しません', 404);
        }
    }

    public function delete($id)
    {
        $category = LpCategory::find($id);
        if (!empty($category)) {
            $category->delete();
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
            $list = LpCategory::where('name', 'like', '%' . $q . '%')->limit(10)->get();
            if (!empty($list)) {
                foreach ($list as $item) {
                    $records[] = [
                        'id' => $item->id,
                        'title' => $item->name,
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


    public function options(Request $request)
    {
        $withEmpty = $request->get('withEmpty');
        $hotels = Hotel::select('id', 'name')->get();
        $data = [
            'options' => LpCategory::options($withEmpty),
            'hotels' => $hotels
        ];
        return $this->success($data);
    }
}
