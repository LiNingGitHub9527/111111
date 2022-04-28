<?php
namespace App\Services\PmsApi;

class ApiCallService
{
    private $requestUrl;

    public function __construct()
    {
        $this->getBookingDataUrl = config('signature.pms_url') . '/pms/reservation_data/get';
    }

    public function getCurlResponseData($postData)
    {
        // cURLでCRMから情報を取得
        $curlResponse = curlPostData($this->getBookingDataUrl, [], $postData, true);
        $decodeResponseData = json_decode($curlResponse, true);

        return $decodeResponseData;
    }

}