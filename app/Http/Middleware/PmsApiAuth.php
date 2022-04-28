<?php

namespace App\Http\Middleware;

use App\Support\Api\Signature\AuthSignature;
use Closure;

class PmsApiAuth
{
    protected $isLogin = false;

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->route()->named('api.login')) {
            $this->isLogin = true;
        }
        $clientIp = $request->getClientIp();
        if (config('app.env') != 'local' && !$this->isLogin && $clientIp != config('signature.client_ip')) {
            return $this->error(1005);
        }
        if (empty($request->all())) {
            return $this->error(1002);
        }

        $clientSignature = $request['clientSignature'];
        $timestamp = $request['timestamp'];
        $nonce = $request['nonce'];

        if (empty($clientSignature)) {
            return $this->error(1002);
        }
        if (empty($timestamp) || empty($nonce)) {
            return $this->error(1002);
        }

        unset($request['clientSignature'], $request['timestamp'], $request['nonce']);

        $data = $request->all();

        $auth = new AuthSignature;
        ksort($data);

        $stringToSign = urlencode(http_build_query($data, null, '&', PHP_QUERY_RFC3986));
        $signatureApiKey = config('signature.pms_signature_api_key');
        $flag = $auth->verifySignature($timestamp, $nonce, $stringToSign, $signatureApiKey, $clientSignature);
        if (!$flag) {
            return $this->error(401);
        }
        return $next($request);
    }

    protected function error(int $code, $message = '', $codeHeader = 200)
    {
        if ($this->isLogin) {
            echo empty($message) ? trans('api')[$code] : $message;
        } else {
            return response()->json([
                'status' => 'FAIL',
                'code' => $code,
                'msg' => empty($message) ? trans('api')[$code] : $message
            ], $codeHeader);
        }
    }


}
