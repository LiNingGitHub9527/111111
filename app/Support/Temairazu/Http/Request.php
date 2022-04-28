<?php

namespace App\Support\Temairazu\Http;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException;

class Request
{
	protected $sandboxURL = 'https://stg.temairazu.net/api/';

	protected $liveURL = 'https://api.temairazu.net/api/';

	protected $sandbox = false;

    public function __construct($params = [])
    {
        // $this->httpClient = new HttpClient(['verify' => false]);
        $this->httpClient = new HttpClient;
    }

    public function setSandBox($sandbox = true)
    {
    	$this->sandbox = $sandbox;
    	return $this;
    }

    public function isSandBox()
    {
    	return $this->sandbox;
    }

    protected function buildUrl($url)
    {
    	$baseUrl = $this->sandbox ? $this->sandboxURL : $this->liveURL;
    	return $baseUrl . $url;
    }

    public function post($url, $data)
    {
    	$url = $this->buildUrl($url);
    	
    	try {
	    	$response = $this->httpClient->post($url, ['form_params' => $data]);

	    	$code = $response->getStatusCode();
	    	$content = $response->getBody()->getContents();
    	} catch (RequestException $e) {
    		$code = $e->getCode();
    		$content = $e->getResponse()->getReasonPhrase();
    	} catch(\Exception $e) {
    		$code = $e->getCode();
    		$content = $e->getMessage();
    	}

    	return [
    		'code' => $code,
    		'content' => $content,
    	];
    }
}
