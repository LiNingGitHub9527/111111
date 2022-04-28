<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Form;
use App\Models\Hotel;
use App\Models\Lp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class LpControllerTest extends TestCase
{
    use RefreshDatabase;

    private $hotel;


    public function setUp(): void
    {
        parent::setUp();
        $this->client = factory(Client::class)->create();
        $this->hotel = factory(Hotel::class)->create([
            'client_id' => $this->client->id,
        ]);
    }

    /** @test */
    public function index_正常系_hotelのbusiness_typeが1()
    {
        $hotel = factory(Hotel::class)->create([
            'client_id' => $this->client->id,
            'business_type' => 1,
        ]);
        $form = factory(Form::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'public_status' => 0,
        ]);
        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);

        $url = config('app.url');
        $lpTitle = $lp->title;
        $lpUrlParam = $lp->url_param;

        $response = $this->get("/lp/{$lpUrlParam}");
        $response->assertViewHas('searchLink', "{$url}/page/search_panel?url_param={$lpUrlParam}")
            ->assertViewHas('title', $lpTitle);
    }

    /** @test */
    public function index_正常系_hotelのbusiness_typeが1以外()
    {
        $hotel = factory(Hotel::class)->create([
            'client_id' => $this->client->id,
            'business_type' => random_int(2, 5),
        ]);
        $form = factory(Form::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'public_status' => 0,
        ]);
        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);

        $url = config('app.url');
        $lpTitle = $lp->title;
        $lpUrlParam = $lp->url_param;

        $response = $this->get("/lp/{$lpUrlParam}");
        $response->assertViewHas('searchLink', "{$url}/page/reservation/search_panel?url_param={$lpUrlParam}")
            ->assertViewHas('title', $lpTitle);
    }

    /** @test */
    public function index_異常系_パラメータのurlParamが空()
    {
        $lpUrlParam = '';

        $response = $this->get("/lp/{$lpUrlParam}");
        $response->assertNotFound();
    }

    /** @test */
    public function index_異常系_パラメータのurlParamがDBに存在しない()
    {
        $lpUrlParam = 'invalid_test';

        $response = $this->get("/lp/{$lpUrlParam}");
        $response->assertViewMissing('normalLayouts')
            ->assertViewMissing('popupLayouts')
            ->assertViewMissing('styles')
            ->assertViewMissing('scripts')
            ->assertViewMissing('deviceType')
            ->assertViewMissing('searchLink')
            ->assertViewMissing('title');
    }

}
