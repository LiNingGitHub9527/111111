<?php
namespace App\Http\Controllers\Api\Client;

use App\Models\Component;
use App\Models\Layout;
use App\Models\Hotel;
use App\Models\Lp;
use App\Models\OriginalLp;
use Illuminate\Http\Request;

class EditorController extends ApiBaseController
{
    public function components(Request $request)
    {
        $hotel = Hotel::where('id', $request->hid)->first();
        $query = Component::query();

        $query->whereHas('layouts', function($q) {
            $q->where('public_status', 1);
        })->where('public_status', 1)->whereRaw("JSON_CONTAINS(business_types,'[" . $hotel->business_type . "]')");

        $query->where('is_limit_hotel', 0)->orWhere(function($q) use($hotel){
            $q->where('is_limit_hotel', 1)->whereRaw("JSON_CONTAINS(hotel_ids, '[" . $hotel->id . "]')");
        });
        $list = $query->orderBy('sort_num', 'DESC')->get();


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
            ->orderBy('sort_num', 'DESC')
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

    public function originalLp($id)
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
                'form_id' => 0,
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

    public function lp($id)
    {
        $lp = Lp::with(['layouts' => function($q) {
            $q->orderBy('layout_order', 'ASC');
        }])->find($id);
        if (!empty($lp)) {
            $layouts = [];
            foreach ($lp->layouts as $layout) {
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
                'id' => $lp->id,
                'client_id' => $lp->client_id,
                'hotel_id' => $lp->hotel_id,
                'original_lp_id' => $lp->original_lp_id,
                'title' => $lp->title,
                'cover_image' => $lp->imageSrc(),
                'public_status' => $lp->public_status,
                'form_id' => $lp->form_id,
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
