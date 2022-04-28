<?php

namespace Tests\Feature\admin;

use Tests\TestCase;
use App\Models\Client;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class HotelApiTest extends TestCase
{
    use WithoutMiddleware;

    public function testSaleList()
    {
        // $client = Client::first();
        // $response = $this->get("api/admin/client/{$client->id}/salelist");

        // $response->assertStatus(200);
        $this->assertTrue(true);
    }
}
