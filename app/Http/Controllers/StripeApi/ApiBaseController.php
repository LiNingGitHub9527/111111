<?php

namespace App\Http\Controllers\StripeApi;

use Auth;
use Illuminate\Routing\Controller;

class ApiBaseController extends Controller
{
    public function success($data = null, $message = 'SUCCESS', $code = 200)
    {
        return response()->json([
            'code' => $code,
            'status' => 'OK',
            'data' => $data,
            'message' => $message,
        ], 200);
    }

    public function error($message = null, $codeStatus = 400, $codeHeader = 200)
    {
        return response()->json([
            'code' => $codeStatus,
            'status' => 'FAIL',
            'message' => $message,
        ], $codeHeader);
    }
}
