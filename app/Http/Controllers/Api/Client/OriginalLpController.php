<?php

namespace App\Http\Controllers\Api\Client;

use App\Models\Hotel;
use App\Models\OriginalLp;
use App\Models\LpCategory;
use Illuminate\Http\Request;

class OriginalLpController extends ApiBaseController
{
    public function list($id, Request $request)
    {
        $hotel = Hotel::where('id', $id)->where('client_id', $this->user()->id)->first();
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }

        $query = OriginalLp::where('public_status', 1)->whereRaw("JSON_CONTAINS(business_types, '[" . $hotel->business_type . "]')");

        $lpId = $request->get('lp_id');
        if (!empty($lpId)) {
            $query->where('id', $lpId);
        }

        $categoryIds = $request->get('category_ids');
        if (!empty($categoryIds)) {
            $categoryIds = json_encode(array_map('intval', $categoryIds));
            $query->whereRaw("JSON_CONTAINS(category_ids,'" . $categoryIds . "')");
        }
        $query->where('is_limit_hotel', 0)->orWhere(function($q) use($id){
            $q->where('is_limit_hotel', 1)->whereRaw("JSON_CONTAINS(hotel_ids, '[" . $id . "]')");
        });

        $query->orderBy('id', 'DESC');

        // カテゴリ取得
        $categoryIdArr = $query->pluck('category_ids')->toArray();
        $categoryIds = [];
        foreach ($categoryIdArr as $ids) {
            if (!empty($ids)) {
                foreach($ids as $id) {
                    array_push($categoryIds, $id);
                }
            }
        }
        $categoryIds = collect($categoryIds)->unique()->toArray();
        $categories = LpCategory::select('id', 'name')->whereIn('id', $categoryIds)->get()->keyBy('id');
        // カテゴリ取得完了
        
        $list = $query->paginate(20);

        $records = [];
        foreach ($list as $lp) {
            $ctgs = $categories->whereIn('id', $lp->category_ids)->toArray();
            $row = [
                'id' => $lp->id,
                'title' => $lp->title,
                'cover_image' => $lp->imageSrc(),
                'categories' => $ctgs,
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

    public function layouts($id, Request $request)
    {
        $hotelId = $request->get('hotel_id');
        $hotel = Hotel::where('id', $hotelId)->where('client_id', $this->user()->id)->first();
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }
        $originalLp = OriginalLp::with(['layouts' => function ($q) {
            $q->orderBy('layout_order', 'ASC');
        }])->find($id);
        if (!empty($originalLp)) {
            $layouts = [];
            foreach ($originalLp->layouts as $key => $layout) {
                $item = [
                    'key' => 'layout-' . $key,
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

            $data = [
                'layouts' => $layouts,
                'lp_title' => $originalLp->title
            ];
            return $this->success($data);
        } else {
            return $this->error('データが存在しません', 404);
        }
    }
}
