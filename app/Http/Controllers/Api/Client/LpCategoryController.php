<?php

namespace App\Http\Controllers\Api\Client;

use App\Models\LpCategory;
use Illuminate\Http\Request;

class LpCategoryController extends ApiBaseController
{

    public function options(Request $request)
    {
        $withEmpty = $request->get('withEmpty');
        $data = [
            'options' => LpCategory::options($withEmpty)
        ];
        return $this->success($data);
    }
}
