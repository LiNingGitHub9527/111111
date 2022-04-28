<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\Lp;
use App\Models\Plan;
use App\Support\Api\ApiClient;
use App\Support\Api\Signature\AuthSignature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class LpController extends Controller
{
    protected $viewPathPrefix = 'user.lp';

    public function __construct()
    {
        $this->browse_reserve_service = app()->make('BrowserReserveService');
        $this->form_service = app()->make('FormSearchService');
		$this->reserve_session_service = app()->make('ReserveSessionService');
	}

    public function index($urlParam, Request $request)
    {
		if ($request->has('signature') ) {
        	if(!$request->hasValidSignature()){
				abort(401, 'Signature Invalid');
			}else{
				$lineUserId = $request->get('line_user_id', null);
			}
        }
        if (empty($urlParam)) {
            return $this->view('index');
        }
        $lp = Lp::with(['layouts' => function ($q) {
            $q->orderBy('layout_order', 'ASC');
        }])->where('url_param', $urlParam)->first();
        if (empty($lp)) {
            return $this->view('index');
        }
        $normalLayouts = [];
        $popupLayouts = [];
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
                $popupLayouts[] = $item;
            } else {
                $normalLayouts[] = $item;
            }
        }
        $layouts = array_merge($normalLayouts, $popupLayouts);
        $styles = [];
        $scripts = [];
        foreach ($layouts as $layout) {
            $css = $layout['source']['css'];
            if (!empty($css)) {
                $styles[] = $css;
            }
            $js = $layout['source']['js'];
            if (!empty($css)) {
                $scripts[] = $js;
            }
        }
        $deviceType = $this->agent()->deviceType();
        if (in_array($deviceType, ['other', 'robot'])) {
            $deviceType = 'desktop';
        }
        if ($deviceType == 'phone') {
            $deviceType = 'mobile';
        }

        $searchLink = null;
        if ($lp->form_id) {
            $form = $this->form_service->findForm($lp->form_id);
            $businessType = Hotel::find($form->hotel_id)->business_type;
            if ($businessType == 1) {
                $planIds = $form->plan_ids;
                $stayTypes = Plan::whereIn('id', $planIds)->pluck('stay_type')->unique()->toArray();
                $searchLink = config('app.url') . '/page/search_panel?url_param=' . $urlParam;
                if (!in_array(1, $stayTypes) && in_array(2, $stayTypes)) {
                    $searchLink = route('user.booking_search_panel', ['url_param' => $urlParam, 'dayuse' => 'true']);
                }
            } else {
				$params = ['url_param'=>$urlParam];
                if(!empty($lineUserId)){
                	$params['line_user_id'] = $lineUserId;
				}
				$searchLink = URL::signedRoute('user.other.render_search_panel', $params);
            }
        }

        $title = $lp->title;

        return $this->view('index', compact('normalLayouts', 'popupLayouts', 'styles', 'scripts', 'deviceType', 'searchLink', 'title'));
    }

}
