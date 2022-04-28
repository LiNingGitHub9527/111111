<?php

namespace App\Http\Controllers\Api\Admin;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Component;
use App\Models\Layout;
use App\Models\OriginalLp;
use App\Models\Image;

class EditorController extends ApiBaseController
{
    public function components(Request $request)
    {
        $query = Component::query();

        $list = $query->whereHas('layouts', function ($q) {
            $q->where('public_status', 1);
        })->where('public_status', 1)->orderBy('id', 'DESC')->get();

        $records = [
            [
                'name' => '通常コンポーネント',
                'type' => 1,
                'list' => [],
            ],
            [
                'name' => 'ポップアップ',
                'type' => 2,
                'list' => [],
            ]
        ];
        foreach ($list as $component) {
            $row = [
                'id' => $component->id,
                'name' => $component->name,
                'type' => $component->type,
            ];
            $index = $component->type - 1;
            if (isset($records[$index])) {
                $records[$index]['list'][] = $row;
            }
        }
        $data = [
            'records' => $records,
        ];
        return $this->success($data);
    }

    public function layouts($id, Request $request)
    {
        $pageSize = (int)$request->get('pagesize', 20);
        if ($pageSize == 0) {
            $pageSize = 20;
        }

        $list = Layout::where('component_id', $id)
            ->where('public_status', 1)
            ->orderBy('id', 'DESC')
            ->paginate($pageSize);

        $records = [];
        foreach ($list as $layout) {
            $row = [
                'id' => $layout->id,
                'name' => $layout->name,
                'img' => $layout->imageSrc(),
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

    public function layout($id, Request $request)
    {
        $layout = Layout::with('component')->where('id', $id)
            ->where('public_status', 1)->first();
        if (!empty($layout)) {
            $detail = [
                'id' => $layout->id,
                'name' => $layout->name,
                'component_id' => $layout->component->id ?? 0,
                'component_name' => $layout->component->name ?? '',
                'type' => $layout->component->typeName(),
                'html' => $layout->html ?? '',
                'css' => $layout->parsedCssFile(),
                'js' => $layout->jsFile(),
                'setting' => [],
            ];
            if ($detail['type'] == 'popup') {
                $detail['setting'] = [
                    'show' => [
                        'type' => 0,
                        'data' => new \stdClass(),
                    ]
                ];
            }
            $data = [
                'detail' => $detail,
            ];
            return $this->success($data);
        } else {
            return $this->error('データが存在しません', 404);
        }
    }

    public function lp($id)
    {
        $originalLp = OriginalLp::with(['layouts' => function($q) {
            $q->orderBy('layout_order', 'ASC');
        }])->find($id);
        if (!empty($originalLp)) {
            $layouts = [];
            foreach ($originalLp->layouts as $layout) {
                $item = [
                    'key' => 'layout-' . $layout->unique_key,
                    'id' => $layout->id,
                    'source' => [
                        'id' => $layout->layout_id,
                        'name' => $layout->layout->name,
                        'component_id' => $layout->component->id,
                        'component_name' => $layout->component->name,
                        'type' => $layout->component->typeName(),
                        'css' => $layout->layout->parsedCssFile(),
                        'js' => $layout->layout->jsFile(),
                    ],
                    'content' => $layout->render_html,
                    'setting' => [],
                ];
                if ($item['source']['type'] == 'popup') {
                    $conditionType = 0;
                    $data = [];
                    if ($layout->condition) {
                        $conditionType = $layout->condition->start_point_type;
                        if ($conditionType == 1) {
                            $data['delay'] = $layout->condition->default_start_point_seconds;
                        } else if ($conditionType == 2) {
                            $data['offset'] = $layout->condition->default_start_point_scroll;
                        }
                    }
                    $item['setting'] = [
                        'show' => [
                            'type' => $conditionType,
                            'data' => count($data) > 0 ? $data : new \stdClass(),
                            'size' => $layout->component->popupSize()
                        ]
                    ];
                }
                $layouts[] = $item;
            }

            $lp = [
                'id' => $originalLp->id,
                'title' => $originalLp->title,
                'cover_image' => $originalLp->imageSrc(),
                'public_status' => $originalLp->public_status,
                'category_ids' => $originalLp->category_ids,
                'form_id' => 0,
                'business_types' => $originalLp->business_types,
                'is_limit_hotel' => $originalLp->is_limit_hotel,
                'hotel_ids' => $originalLp->hotel_ids,
            ];

            $data = [
                'lp' => $lp,
                'layouts' => $layouts,
            ];

            return $this->success($data);
        } else {
            return $this->error('データが存在しません', 404);
        }
    }

}
