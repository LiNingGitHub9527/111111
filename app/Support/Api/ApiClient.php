<?php

namespace App\Support\Api;

use App\Support\Api\Signature\AuthSignature;
use Illuminate\Support\Facades\Log;

class ApiClient
{

    private $auth;

    private $signatureApiKey;

    private $urlParams;

    public function __construct($signatureApiKey, $params = [])
    {
        $this->signatureApiKey = $signatureApiKey;
        $this->auth = new AuthSignature;
        $this->buildUrlParams($params);
    }


    public function defaultParams(): array
    {
        return [
            'nonce' => $this->auth->generateNonce(),
            'timestamp' => time(),
        ];
    }

    public function buildUrlParams($params)
    {
        if (empty($params)) {
            return;
        }

        $defaultParams = $this->defaultParams();

        ksort($params);
		$params = array_filter($params,function($val){
			return !empty($val);
		});
        $stringToSign = urlencode(http_build_query($params, null, '&', PHP_QUERY_RFC3986));
        $signatureSecret = $this->auth->getSignatureApiSecret($this->signatureApiKey);
        $clientSignature = $this->auth->generateSignature($defaultParams['timestamp'], $defaultParams['nonce'], $stringToSign, $signatureSecret);

        $newParams = array_merge($defaultParams, $params);
        $newParams['clientSignature'] = $clientSignature;
        $this->urlParams = http_build_query($newParams);

    }

    public function getUrlParams()
    {
        return $this->urlParams;
    }

    public function getPath($method): string
    {
        if (empty($method)) {
            return '';
        }
        return config('signature.pms_url') . '/api/nocode/' . $method;
    }


    public function doRequest($method)
    {
        try {
            $url = $this->getPath($method);
            Log::info("POST Access:".$url);
            $result = curlPostData($url, ['Content-Type: application/x-www-form-urlencoded'], $this->urlParams, true);
            return json_decode($result);
        } catch (\Exception $e) {
        	Log::error($e->getMessage());
            return null;
        }
    }

    public function doGetRequest($method)
    {
        try {
            $url = $this->getPath($method);
			Log::info("GET Access:".$url);
			$result = curlPostData($url, ['Content-Type: application/x-www-form-urlencoded'], $this->urlParams, false);
            return json_decode($result, true);
        } catch (\Exception $e) {
            return null;
        }
    }

}
