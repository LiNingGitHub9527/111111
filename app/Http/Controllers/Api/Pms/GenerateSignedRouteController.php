<?php

namespace App\Http\Controllers\Api\Pms;

use Carbon\Carbon;
use App\Models\Hotel;
use Illuminate\Http\Request;
use App\Services\ClientLogService;
use Illuminate\Support\Facades\URL;

class GenerateSignedRouteController extends ApiBaseController
{

    public function otherAdminSearchRender(Request $request)
    {
        $hotelId = $request->hotel_id;
        $lineUserId = $request->line_user_id;
        $baseCustomerId = $request->base_customer_id;
        return [
            'url' => URL::signedRoute('user.other.render_search_panel', ['url_param' => 'AdminUrlParam' , 'hotel_id' => $hotelId, 'line_user_id' => $lineUserId, 'base_customer_id' => $baseCustomerId])
        ];
    }

    public function generateSignedUrl(Request $request)
    {
        $urlOrig = $request->url;
        $pathOrig = parse_url($urlOrig, PHP_URL_PATH);
        parse_str(parse_url($urlOrig, PHP_URL_QUERY), $queryParams);
        try {
            $route = app('router')->getRoutes()->match(app('request')->create($pathOrig));
            $routeName = $route->getName();
            $routeParams = $route->parameters();
            return [
                'url' => URL::signedRoute($routeName, array_merge($queryParams, $routeParams))
            ];
        } catch(\Exception $e) {
            return [
                'url' => $urlOrig
            ];
        }
    }
}
