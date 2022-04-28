<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Requests\Api\Client\LpRequest;
use App\Models\Hotel;
use App\Models\HotelRoomType;
use App\Models\Lp;
use App\Models\LpLayout;
use App\Models\Plan;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;

class LpController extends ApiBaseController
{
    public function list($id, Request $request)
    {
        $hotel = Hotel::where('id', $id)->where('client_id', $this->user()->id)->first();
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }

        $query = Lp::where('hotel_id', $id)->where('client_id', $this->user()->id);

        $lpId = $request->get('lp_id');
        if (!empty($lpId)) {
            $query->where('id', $lpId);
        }

        $publicStatus = $request->get('public_status');
        if ($publicStatus != 2) {
            $query->where('public_status', $publicStatus);
        }

        $list = $query->where('hotel_id', $id)->orderBy('id', 'DESC')->paginate(20);

        $records = [];
        foreach ($list as $lp) {
            $row = [
                'id' => $lp->id,
                'title' => $lp->title,
                'cover_image' => $lp->imageSrc(),
                'public_status' => $lp->public_status,
                'url' => !empty($lp->url_param) ? config('app.url') . "/lp/$lp->url_param" : '',
                'form_id' => $lp->form_id,
            ];

            #TODO: formの対応
            $form = $lp->form;
            if (!empty($form)) {
                $row['formName'] = $form->name;
            }

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
            $list = Lp::where('title', 'like', '%' . $q . '%')->limit(10)->get();
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

    public function update(LpRequest $request)
    {
        $id = $request->get('id');
        $clientId = $this->user()->id;
        $hotelId = $request->get('hotel_id');
        $hotel = Hotel::where('id', $hotelId)->where('client_id', $clientId)->first();
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }
        $lp = $hotel->lps->find($id);
        if (!empty($lp)) {
            $data = $request->all();
            if (!empty($data['cover_image'])) {
                $data['cover_image'] = str_replace(config('aws.url') . '/', '', $data['cover_image']);
            }
            $lp->fill($data);
            if ($lp->public_status == 0) {
                $lp->url_param = null;
            } else {
                if (empty($lp->url_param)) {
                    $lp->url_param = Uuid::uuid1(time());
                }
            }
            $lp->save();

            $lpLayoutRemovedIds = $lp->layouts->pluck('id');

            if (isset($data['layouts']) && !empty($data['layouts'])) {
                foreach ($data['layouts'] as $key => $layout) {
                    if ($layout['id'] == 0) {
                        $layoutData = [
                            'hotel_id' => $lp->hotel_id,
                            'client_id' => $lp->client_id,
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
                LpLayout::whereIn('id', $lpLayoutRemovedIds)->delete();
            }

            return $this->success();
        } else {
            return $this->error('データが存在しません', 404);
        }
    }

    public function create(LpRequest $request)
    {
        $clientId = $this->user()->id;
        $hotelId = $request->get('hotel_id');
        $hotel = Hotel::where('id', $hotelId)->where('client_id', $clientId)->first();
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }

        $lp = new Lp;
        $data = $request->all();
        if (!empty($data['cover_image'])) {
            $data['cover_image'] = str_replace(config('aws.url') . '/', '', $data['cover_image']);
        }
        $lp->fill($data);
        $lp->client_id = $clientId;
        if ($lp->public_status == 1) {
            $lp->url_param = Uuid::uuid1(time());
        }
        if ($lp->save()) {
            if (isset($data['layouts']) && !empty($data['layouts'])) {
                foreach ($data['layouts'] as $key => $layout) {
                    $layoutData = [
                        'hotel_id' => $hotelId,
                        'client_id' => $clientId,
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

    public function delete($id, Request $request)
    {
        $hotelId = $request->get('hotel_id');
        $lp = Lp::where('id', $id)->where('hotel_id', $hotelId)->where('client_id', $this->user()->id)->first();
        if (empty($lp)) {
            return $this->error('データが存在しません', 404);
        }
        $lp->delete();

        return $this->success();
    }

    public function layouts($id, Request $request)
    {
        $hotelId = $request->get('hotel_id');
        $hotel = Hotel::where('id', $hotelId)->where('client_id', $this->user()->id)->first();
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }
        $lp = Lp::with(['layouts' => function ($q) {
            $q->orderBy('layout_order', 'ASC');
        }])->find($id);
        if (!empty($lp)) {
            $layouts = [];
            foreach ($lp->layouts as $key => $layout) {
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
                'lp_title' => $lp->title,
                'url' => !empty($lp->url_param) ? config('app.url') . "/lp/$lp->url_param" : '',
            ];
            return $this->success($data);
        } else {
            return $this->error('データが存在しません', 404);
        }
    }
}
