<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;

class GenerateSigneRouteApiTest extends TestCase
{
    use WithoutMiddleware;

    public function testGenerateSignedUrl()
    {
        $payload = [
            'url' => 'localhost:8000/api/client/form/detail/1?line_user_id=1'
        ];
        $response = $this->post('/api/pms/signed-route', $payload);
        $response->assertOk();
        $responseBody = $response->decodeResponseJson();
        $queryString = parse_url($responseBody['url'], PHP_URL_QUERY);
        parse_str($queryString, $queryParams);
        $this->assertEquals('1', $queryParams['line_user_id']);
        $this->assertNotNull($queryParams['signature']);
    }

    public function testGenerateSignedUrlNoRouteParam()
    {
        $payload = [
            'url' => 'localhost:8000/api/client/form/search?line_user_id=1&q=asdf'
        ];
        $response = $this->post('/api/pms/signed-route', $payload);
        $response->assertOk();
        $responseBody = $response->decodeResponseJson();
        $queryString = parse_url($responseBody['url'], PHP_URL_QUERY);
        parse_str($queryString, $queryParams);
        $this->assertEquals('1', $queryParams['line_user_id']);
        $this->assertEquals('asdf', $queryParams['q']);
        $this->assertNotNull($queryParams['signature']);
    }

    public function testGenerateSignedUrlOutsideUrl()
    {
        $payload = [
            'url' => 'http://googel.com'
        ];
        $response = $this->post('/api/pms/signed-route', $payload);
        $response->assertOk();
        $responseBody = $response->decodeResponseJson();
        $queryString = parse_url($responseBody['url'], PHP_URL_QUERY);
        parse_str($queryString, $queryParams);
        $this->assertEmpty($queryParams);
    }
}
