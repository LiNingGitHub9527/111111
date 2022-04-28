<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Requests\Api\Admin\OriginalLpRequest;
use App\Models\OriginalLp;
use App\Models\OriginalLpLayout;
use Illuminate\Http\Request;

class OriginalLpController extends ApiBaseController
{
    public function list(Request $request)
    {
        $query = OriginalLp::query();
        $lpId = $request->get('lp_id');
        if (!empty($lpId)) {
            $query->where('id', $lpId);
        }

        $categoryId = $request->get('category_id');
        if (!empty($categoryId)) {
            $query->whereRaw("JSON_CONTAINS(category_ids,'[" . $categoryId . "]')");
        }

        $list = $query->orderBy('id', 'DESC')->paginate(20);

        $records = [];
        foreach ($list as $lp) {
            $row = [
                'id' => $lp->id,
                'title' => $lp->title,
                'cover_image' => $lp->imageSrc(),
                'public_status' => $lp->public_status,
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
        $lp = OriginalLp::find($id);
        if (!empty($lp)) {
            $detail = [
                'id' => $lp->id,
                'title' => $lp->title,
                'cover_image' => $lp->imageSrc(),
                'category_ids' => $lp->category_ids,
                'public_status' => $lp->public_status,
            ];

            $data = [
                'detail' => $detail,
            ];
            return $this->success($data);
        } else {
            return $this->error('データが存在しません', 404);
        }
    }

    public function update(OriginalLpRequest $request)
    {
        $id = $request->get('id');
        $lp = OriginalLp::find($id);
        if (!empty($lp)) {
            $data = $request->all();
            if (!empty($data['cover_image'])) {
                $data['cover_image'] = str_replace(config('aws.url') . '/', '', $data['cover_image']);
            }
            $lp->fill($data);
            $lp->filterData();
            $lp->save();

            $lpLayoutRemovedIds = $lp->layouts->pluck('id');

            if (isset($data['layouts']) && !empty($data['layouts'])) {
                foreach ($data['layouts'] as $key => $layout) {
                    if ($layout['id'] == 0) {
                        $layoutData = [
                            'layout_id' => $layout['source']['id'],
                            'component_id' => $layout['source']['component_id'],
                            'render_html' => $layout['content'],
                            'layout_order' => $key,
                            'unique_key' => $lp->id . str_replace('layout-', '', $layout['key'])
                        ];
                        $conditionData = [];
                        if (isset($layout['setting'])) {
                            if (isset($layout['setting']['show'])) {
                                $show = $layout['setting']['show'];
                                $conditionData['start_point_type'] = $show['type'];
                                if ($show['type'] == 1) {
                                    $conditionData['default_start_point_seconds'] = $show['data']['delay'];
                                } else if ($show['type'] == 2) {
                                    $conditionData['default_start_point_scroll'] = $show['data']['offset'];
                                }
                            }
                        }
                        $lp->addLayout($layoutData, $conditionData);
                    } else {
                        $layoutId = $layout['id'];

                        $lpLayoutRemovedIds = $lpLayoutRemovedIds->reject(function ($value) use ($layoutId) {
                            return $value == $layoutId;
                        });

                        $layoutData = [
                            'render_html' => $layout['content'],
                            'layout_order' => $key,
                        ];
                        $conditionData = [];
                        if (isset($layout['setting'])) {
                            if (isset($layout['setting']['show'])) {
                                $show = $layout['setting']['show'];
                                $conditionData['start_point_type'] = $show['type'];
                                if ($show['type'] == 1) {
                                    $conditionData['default_start_point_seconds'] = $show['data']['delay'];
                                } else if ($show['type'] == 2) {
                                    $conditionData['default_start_point_scroll'] = $show['data']['offset'];
                                }
                            }
                        }
                        $lp->updateLayout($layoutId, $layoutData, $conditionData);
                    }
                }
            }

            if (!empty($lpLayoutRemovedIds) && $lpLayoutRemovedIds->count() > 0) {
                OriginalLpLayout::whereIn('id', $lpLayoutRemovedIds)->delete();
            }

            return $this->success();
        } else {
            return $this->error('データが存在しません', 404);
        }
    }

    public function create(OriginalLpRequest $request)
    {
        $lp = new OriginalLp;
        $data = $request->all();
        if (!empty($data['cover_image'])) {
            $data['cover_image'] = str_replace(config('aws.url') . '/', '', $data['cover_image']);
        }
        $lp->fill($data);
        if ($lp->save()) {
            if (isset($data['layouts']) && !empty($data['layouts'])) {
                foreach ($data['layouts'] as $key => $layout) {
                    $layoutData = [
                        'layout_id' => $layout['source']['id'],
                        'component_id' => $layout['source']['component_id'],
                        'render_html' => $layout['content'],
                        'layout_order' => $key,
                        'unique_key' => $lp->id . str_replace('layout-', '', $layout['key'])
                    ];
                    $conditionData = [];
                    if (isset($layout['setting'])) {
                        if (isset($layout['setting']['show'])) {
                            $show = $layout['setting']['show'];
                            $conditionData['start_point_type'] = $show['type'];
                            if ($show['type'] == 1) {
                                $conditionData['default_start_point_seconds'] = $show['data']['delay'];
                            } else if ($show['type'] == 2) {
                                $conditionData['default_start_point_scroll'] = $show['data']['offset'];
                            }
                        }
                    }
                    $lp->addLayout($layoutData, $conditionData);
                }
            }
            $data = [
                'id' => $lp->id,
            ];
            return $this->success($data);
        } else {
            return $this->error('server error', 500);
        }
    }

    public function delete($id)
    {
        $lp = OriginalLp::find($id);
        if (!empty($lp)) {
            $lp->delete();
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
            $list = OriginalLp::where('title', 'like', '%' . $q . '%')->limit(10)->get();
            if (!empty($list)) {
                foreach ($list as $lp) {
                    $records[] = [
                        'id' => $lp->id,
                        'title' => $lp->title,
                        'description' => $lp->public_status == 1 ? '公開中' : '非公開',
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
