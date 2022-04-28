<?php

namespace Tests\Feature\Api\Cleint;

use App\Models\Client;
use App\Models\ClientApiToken;
use App\Models\Hotel;
use App\Models\HotelRoomType;
use App\Models\ReservationBlock;
use App\Models\ReservationRepeatGroup;
use App\Models\ReservedReservationBlock;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;


class ReservationScheduleControllerTest extends TestCase
{
    use RefreshDatabase;

    private $client;
    private $clientApiToken;
    private $hotel;
    private $hotelRoomTypes;

    public function setUp(): void
    {
        parent::setUp();
        $this->client = factory(Client::class)->create();
        $this->clientApiToken = factory(ClientApiToken::class)->create([
            'client_id' => $this->client->id,
        ]);
        $this->hotel = factory(Hotel::class)->create([
            'client_id' => $this->client->id,
        ]);
        $this->hotelRoomTypes = factory(HotelRoomType::class, 3)->create([
            'hotel_id' => $this->hotel->id,
        ]);
    }

    /** @test */
    public function get_room_type_正常系_部屋タイプ複数()
    {
        $this->actingAs($this->clientApiToken, 'client_api');

        $expected = [
            'code' => 200,
            'status' => 'OK',
            'message' => 'SUCCESS',
            'data' => [
                'detail' => [
                    'id' => $this->hotel->id,
                    'name' => $this->hotel->name,
                    'crm_base_id' => $this->hotel->crm_base_id
                ],
                'room_types' => $this->hotelRoomTypes->map(function ($item) {
                    return ['id' => $item->id, 'name' => $item->name];
                })->toArray()
            ]
        ];
        $response = $this->get("/api/client/hotel/{$this->hotel->id}/reservation_schedule/room_type");
        $response->assertStatus(200)
            ->assertExactJson($expected);
    }

    /** @test */
    public function get_room_type_正常系_部屋タイプ0()
    {
        $this->actingAs($this->clientApiToken, 'client_api');
        $hotel = factory(Hotel::class)->create([
            'client_id' => $this->client->id,
        ]);

        $expected = [
            'code' => 200,
            'status' => 'OK',
            'message' => 'SUCCESS',
            'data' => [
                'detail' => [
                    'id' => $hotel->id,
                    'name' => $hotel->name,
                    'crm_base_id' => $hotel->crm_base_id
                ],
                'room_types' => []
            ]
        ];
        $response = $this->get("/api/client/hotel/{$hotel->id}/reservation_schedule/room_type");
        $response->assertStatus(200)
            ->assertExactJson($expected);
    }

    /** @test */
    public function get_room_type_異常系_未認証()
    {
        $response = $this->get("/api/client/hotel/{$this->hotel->id}/reservation_schedule/room_type");
        $response->assertStatus(401);
    }

    /** @test */
    public function get_room_type_異常系_ログインユーザーに紐づくhotel_idではない()
    {
        $this->actingAs($this->clientApiToken, 'client_api');
        $response = $this->get('/api/client/hotel/0/reservation_schedule/room_type');
        $response->assertJsonPath('code', 404);
    }

    /** @test */
    public function list_正常系_予約枠複数()
    {
        $this->actingAs($this->clientApiToken, 'client_api');
        $now = Carbon::now();
        $yesterday = $now->addDays(-1)->format('Y-m-d');
        $today = $now->addDay()->format('Y-m-d');
        $tommorow = $now->addDay()->format('Y-m-d');
        $dayAfterTommorow = $now->addDay()->format('Y-m-d');

        // 取得される予約枠
        $resavationRepeatGroup = factory(ReservationRepeatGroup::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'start_hour' => 9,
            'start_minute' => 0,
            'end_hour' => 23,
            'end_minute' => 30,
            'repeat_interval_type' => 1,
            'repeat_start_date' => $today,
            'repeat_end_date' => $tommorow,
        ]);
        $reservationBlock1 = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'reservation_repeat_group_id' => $resavationRepeatGroup->id,
            'room_num' => 1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $today,
            'start_hour' => 9,
            'start_minute' => 0,
            'end_hour' => 23,
            'end_minute' => 30,
        ]);
        $reservationBlock2 = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'reservation_repeat_group_id' => $resavationRepeatGroup->id,
            'room_num' => 1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $tommorow,
            'start_hour' => 9,
            'start_minute' => 0,
            'end_hour' => 23,
            'end_minute' => 30,
        ]);
        $reservationBlock3 = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'reserved_num' => 1,
            'room_num' => 3,
            'person_capacity' => 4,
            'price' => 5000,
            'date' => $tommorow,
            'start_hour' => 10,
            'start_minute' => 10,
            'end_hour' => 27,
            'end_minute' => 40,
        ]);
        $reservedReservationBlock = factory(ReservedReservationBlock::class)->create([
            'reservation_id' => 1,
            'reservation_block_id' => $reservationBlock3->id,
            'customer_id' => 0,
            'line_user_id' => 0,
        ]);

        // 取得されない予約枠
        factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'date' => $yesterday,
        ]);
        factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'date' => $dayAfterTommorow,
        ]);
        factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[1]->id,
            'date' => $today,
        ]);

        $expected = [
            'code' => 200,
            'status' => 'OK',
            'message' => 'SUCCESS',
            'data' => [
                'start_date' => $today,
                'end_date' => $tommorow,
                'room_type_stocks' => [
                    "{$this->hotelRoomTypes[0]->id}" => $this->hotelRoomTypes[0]->room_num,
                ],
                'reservation_blocks' => [
                    [
                        'room_type_id' => $reservationBlock1->room_type_id,
                        'reservation_block_id' => $reservationBlock1->id,
                        'reservation_repeat_group_id' => $resavationRepeatGroup->id,
                        'is_available' => 1,
                        'person_capacity' => 2,
                        'reserved_num' => 0,
                        'room_num' => 1,
                        'price' => 1000,
                        'date' => $today,
                        'start_time' => '09:00',
                        'end_time' => '23:30',
                        'reservation_ids' => [],
                        'repeat_interval_type' => 1,
                        'repeat_end_date' => $tommorow,
                        'is_closed' => 0
                    ],
                    [
                        'room_type_id' => $reservationBlock2->room_type_id,
                        'reservation_block_id' => $reservationBlock2->id,
                        'reservation_repeat_group_id' => $resavationRepeatGroup->id,
                        'is_available' => 1,
                        'person_capacity' => 2,
                        'reserved_num' => 0,
                        'room_num' => 1,
                        'price' => 1000,
                        'date' => $tommorow,
                        'start_time' => '09:00',
                        'end_time' => '23:30',
                        'reservation_ids' => [],
                        'repeat_interval_type' => 1,
                        'repeat_end_date' => $tommorow,
                        'is_closed' => 0
                    ],
                    [
                        'room_type_id' => $reservationBlock3->room_type_id,
                        'reservation_block_id' => $reservationBlock3->id,
                        'reservation_repeat_group_id' => null,
                        'is_available' => 1,
                        'person_capacity' => 4,
                        'reserved_num' => 1,
                        'room_num' => 3,
                        'price' => 5000,
                        'date' => $tommorow,
                        'start_time' => '10:10',
                        'end_time' => '27:40',
                        'reservation_ids' => [$reservedReservationBlock->reservation_id],
                        'repeat_interval_type' => 0,
                        'repeat_end_date' => '',
                        'is_closed' => 0
                    ]
                ]
            ]
        ];
        $response = $this->json(
            'GET',
            "/api/client/hotel/{$this->hotel->id}/reservation_schedule/list",
            [
                'room_type_id' => [$this->hotelRoomTypes[0]->id],
                'start_date' => $today,
                'end_date' => $tommorow,
            ]
        );
        $response->assertStatus(200)
            ->assertExactJson($expected);
    }

    /** @test */
    public function list_正常系_部屋タイプ複数()
    {
        $this->actingAs($this->clientApiToken, 'client_api');
        $now = Carbon::now();
        $yesterday = $now->addDays(-1)->format('Y-m-d');
        $today = $now->addDay()->format('Y-m-d');
        $tommorow = $now->addDay()->format('Y-m-d');
        $dayAfterTommorow = $now->addDay()->format('Y-m-d');

        // 取得される予約枠
        $resavationRepeatGroup = factory(ReservationRepeatGroup::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'start_hour' => 9,
            'start_minute' => 0,
            'end_hour' => 23,
            'end_minute' => 30,
            'repeat_interval_type' => 1,
            'repeat_start_date' => $today,
            'repeat_end_date' => $tommorow,
        ]);
        $reservationBlock1 = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'reservation_repeat_group_id' => $resavationRepeatGroup->id,
            'room_num' => 1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $today,
            'start_hour' => 9,
            'start_minute' => 0,
            'end_hour' => 23,
            'end_minute' => 30,
        ]);
        $reservationBlock2 = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'reservation_repeat_group_id' => $resavationRepeatGroup->id,
            'room_num' => 1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $tommorow,
            'start_hour' => 9,
            'start_minute' => 0,
            'end_hour' => 23,
            'end_minute' => 30,
        ]);
        $reservationBlock3 = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[1]->id,
            'reserved_num' => 1,
            'room_num' => 3,
            'person_capacity' => 4,
            'price' => 5000,
            'date' => $tommorow,
            'start_hour' => 10,
            'start_minute' => 10,
            'end_hour' => 27,
            'end_minute' => 40,
        ]);
        $reservedReservationBlock = factory(ReservedReservationBlock::class)->create([
            'reservation_id' => 1,
            'reservation_block_id' => $reservationBlock3->id,
            'customer_id' => 0,
            'line_user_id' => 0,
        ]);

        // 取得されない予約枠
        factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'date' => $yesterday,
        ]);
        factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'date' => $dayAfterTommorow,
        ]);
        factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[2]->id,
            'date' => $today,
        ]);

        $expected = [
            'code' => 200,
            'status' => 'OK',
            'message' => 'SUCCESS',
            'data' => [
                'start_date' => $today,
                'end_date' => $tommorow,
                'room_type_stocks' => [
                    "{$this->hotelRoomTypes[0]->id}" => $this->hotelRoomTypes[0]->room_num,
                    "{$this->hotelRoomTypes[1]->id}" => $this->hotelRoomTypes[1]->room_num,
                ],
                'reservation_blocks' => [
                    [
                        'room_type_id' => $reservationBlock1->room_type_id,
                        'reservation_block_id' => $reservationBlock1->id,
                        'reservation_repeat_group_id' => $resavationRepeatGroup->id,
                        'is_available' => 1,
                        'person_capacity' => 2,
                        'reserved_num' => 0,
                        'room_num' => 1,
                        'price' => 1000,
                        'date' => $today,
                        'start_time' => '09:00',
                        'end_time' => '23:30',
                        'reservation_ids' => [],
                        'repeat_interval_type' => 1,
                        'repeat_end_date' => $tommorow,
                        'is_closed' => 0
                    ],
                    [
                        'room_type_id' => $reservationBlock2->room_type_id,
                        'reservation_block_id' => $reservationBlock2->id,
                        'reservation_repeat_group_id' => $resavationRepeatGroup->id,
                        'is_available' => 1,
                        'person_capacity' => 2,
                        'reserved_num' => 0,
                        'room_num' => 1,
                        'price' => 1000,
                        'date' => $tommorow,
                        'start_time' => '09:00',
                        'end_time' => '23:30',
                        'reservation_ids' => [],
                        'repeat_interval_type' => 1,
                        'repeat_end_date' => $tommorow,
                        'is_closed' => 0
                    ],
                    [
                        'room_type_id' => $reservationBlock3->room_type_id,
                        'reservation_block_id' => $reservationBlock3->id,
                        'reservation_repeat_group_id' => null,
                        'is_available' => 1,
                        'person_capacity' => 4,
                        'reserved_num' => 1,
                        'room_num' => 3,
                        'price' => 5000,
                        'date' => $tommorow,
                        'start_time' => '10:10',
                        'end_time' => '27:40',
                        'reservation_ids' => [$reservedReservationBlock->reservation_id],
                        'repeat_interval_type' => 0,
                        'repeat_end_date' => '',
                        'is_closed' => 0
                    ]
                ]
            ]
        ];
        $response = $this->json(
            'GET',
            "/api/client/hotel/{$this->hotel->id}/reservation_schedule/list",
            [
                'room_type_id' => [
                    $this->hotelRoomTypes[0]->id,
                    $this->hotelRoomTypes[1]->id,
                    999999999,
                ],
                'start_date' => $today,
                'end_date' => $tommorow,
            ]
        );
        $response->assertStatus(200)
            ->assertExactJson($expected);
    }

    /** @test */
    public function list_正常系_予約枠0()
    {
        $this->actingAs($this->clientApiToken, 'client_api');
        $now = Carbon::now();
        $today = $now->format('Y-m-d');
        $tommorow = $now->addDay()->format('Y-m-d');

        $expected = [
            'code' => 200,
            'status' => 'OK',
            'message' => 'SUCCESS',
            'data' => [
                'start_date' => $today,
                'end_date' => $tommorow,
                'room_type_stocks' => [
                    "{$this->hotelRoomTypes[0]->id}" => $this->hotelRoomTypes[0]->room_num,
                ],
                'reservation_blocks' => []
            ]
        ];
        $response = $this->json(
            'GET',
            "/api/client/hotel/{$this->hotel->id}/reservation_schedule/list",
            [
                'room_type_id' => [
                    $this->hotelRoomTypes[0]->id,
                ],
                'start_date' => $today,
                'end_date' => $tommorow,
            ]
        );
        $response->assertStatus(200)
            ->assertExactJson($expected);
    }

    /** @test */
    public function list_正常系_部屋タイプ未作成()
    {
        $this->actingAs($this->clientApiToken, 'client_api');
        $now = Carbon::now();
        $today = $now->format('Y-m-d');
        $tommorow = $now->addDay()->format('Y-m-d');

        $expected = [
            'code' => 200,
            'status' => 'OK',
            'message' => 'SUCCESS',
            'data' => null
        ];
        $response = $this->json(
            'GET',
            "/api/client/hotel/{$this->hotel->id}/reservation_schedule/list",
            [
                'room_type_id' => [
                    $this->hotelRoomTypes[2]->id + 1,
                ],
                'start_date' => $today,
                'end_date' => $tommorow,
            ]
        );
        $response->assertStatus(200)
            ->assertExactJson($expected);
    }

    // [$input, $expected]
    public function listDataProvider()
    {
        $now = Carbon::now();
        $today = $now->format('Y-m-d');
        return [
            '必須エラー' => [
                [],
                [
                    'code' => 1422,
                    'status' => 'FAIL',
                    'message' => [
                        'start_date' => ['予約枠取得の開始日は必ず指定してください。'],
                        'end_date' => ['予約枠取得の終了日は必ず指定してください。'],
                    ],
                ]
            ],
            '配列エラー' => [
                [
                    'room_type_id' => 1,
                    'start_date' => $today,
                    'end_date' => $today,
                ],
                [
                    'code' => 1422,
                    'status' => 'FAIL',
                    'message' => [
                        'room_type_id' => ['部屋タイプIDは配列でなくてはなりません。'],
                    ],
                ]
            ],
            '数値エラー' => [
                [
                    'room_type_id' => [1, 'a'],
                    'start_date' => $today,
                    'end_date' => $today,
                ],
                [
                    'code' => 1422,
                    'status' => 'FAIL',
                    'message' => [
                        'room_type_id.1' => ['部屋タイプIDは整数で指定してください。'],
                    ],
                ]
            ],
            '日付エラー' => [
                [
                    'room_type_id' => [1],
                    'start_date' => 1,
                    'end_date' => 1,
                ],
                [
                    'code' => 1422,
                    'status' => 'FAIL',
                    'message' => [
                        'start_date' => ['予約枠取得の開始日には有効な日付を指定してください。'],
                        'end_date' => ['予約枠取得の終了日には有効な日付を指定してください。'],
                    ],
                ]
            ],
            '最小値エラー' => [
                [
                    'room_type_id' => [0],
                    'start_date' => $today,
                    'end_date' => $today,
                ],
                [
                    'code' => 1422,
                    'status' => 'FAIL',
                    'message' => [
                        'room_type_id.0' => ['部屋タイプIDには、1以上の数字を指定してください。'],
                    ],
                ]
            ],
            '日付以後エラー' => [
                [
                    'room_type_id' => [1],
                    'start_date' => $today,
                    'end_date' => $now->addDays(-1)->format('Y-m-d'),
                ],
                [
                    'code' => 1422,
                    'status' => 'FAIL',
                    'message' => [
                        'end_date' => ['予約枠取得の終了日には、予約枠取得の開始日以後の日付を指定してください。'],
                    ],
                ]
            ],
        ];
    }

    /**
     * @dataProvider listDataProvider
     * @test
     */
    public function list_異常系_validationError($input, $expected)
    {
        $this->actingAs($this->clientApiToken, 'client_api');
        $response = $this->json(
            'GET',
            "/api/client/hotel/{$this->hotel->id}/reservation_schedule/list",
            $input
        );
        $response->assertStatus(200)
            ->assertExactJson($expected);
    }

    /** @test */
    public function list_異常系_未認証()
    {
        $response = $this->get("/api/client/hotel/{$this->hotel->id}/reservation_schedule/list");
        $response->assertStatus(401);
    }

    /** @test */
    public function list_異常系_ログインユーザーに紐づくhotel_idではない()
    {
        $this->actingAs($this->clientApiToken, 'client_api');
        $now = Carbon::now();
        $today = $now->format('Y-m-d');
        $tommorow = $now->addDay()->format('Y-m-d');
        $response = $this->json(
            'GET',
            '/api/client/hotel/0/reservation_schedule/list',
            [
                'room_type_id' => [$this->hotelRoomTypes[0]->id],
                'start_date' => $today,
                'end_date' => $tommorow,
            ]
        );
        $response->assertJsonPath('code', 404);
    }

    /** @test */
    public function create_正常系_繰り返し設定0()
    {
        $this->actingAs($this->clientApiToken, 'client_api');
        $now = Carbon::now();
        $today = $now->format('Y-m-d');

        $expected = [
            'code' => 200,
            'status' => 'OK',
            'message' => 'SUCCESS',
            'data' => [
                'reservation_blocks' => [
                    [
                        'reservation_repeat_group_id' => null,
                        'is_available' => 1,
                        'person_capacity' => 10,
                        'reserved_num' => 0,
                        'room_num' => 2,
                        'price' => 1000,
                        'date' => $today,
                        'start_time' => '10:00',
                        'end_time' => '11:30',
                        'reservation_ids' => [],
                        'repeat_interval_type' => 0,
                        'repeat_end_date' => '',
                        'is_closed' => 0
                    ],
                ]
            ]
        ];
        $response = $this->postJson(
            "/api/client/hotel/{$this->hotel->id}/reservation_schedule",
            [
                'room_type_id' => $this->hotelRoomTypes[0]->id,
                'date' => $today,
                'person_capacity' => 10,
                'room_num' => 2,
                'price' => 1000,
                'start_time' => '10:00',
                'end_time' => '11:30',
                'repeat_interval_type' => 0,
                'repeat_end_date' => ''
            ]
        );
        $response->assertStatus(200)
            ->assertJson($expected);
        $this->assertDatabaseHas('reservation_blocks', [
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'date' => $today,
            'person_capacity' => 10,
            'room_num' => 2,
            'price' => 1000,
            'is_available' => 1,
            'reserved_num' => 0,
            'start_hour' => 10,
            'start_minute' => 0,
            'end_hour' => 11,
            'end_minute' => 30,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function create_正常系_繰り返し設定1()
    {
        $this->actingAs($this->clientApiToken, 'client_api');
        $now = Carbon::now();
        $today = $now->format('Y-m-d');
        $tommorow = $now->addDay()->format('Y-m-d');
        $dayAfterTommorow = $now->addDay()->format('Y-m-d');

        $expected = [
            'code' => 200,
            'status' => 'OK',
            'message' => 'SUCCESS',
            'data' => [
                'reservation_blocks' => [
                    [
                        'is_available' => 1,
                        'person_capacity' => 10,
                        'reserved_num' => 0,
                        'room_num' => 2,
                        'price' => 1000,
                        'date' => $today,
                        'start_time' => '10:00',
                        'end_time' => '11:30',
                        'reservation_ids' => [],
                        'repeat_interval_type' => 1,
                        'repeat_end_date' => $dayAfterTommorow,
                        'is_closed' => 0
                    ],
                    [
                        'is_available' => 1,
                        'person_capacity' => 10,
                        'reserved_num' => 0,
                        'room_num' => 2,
                        'price' => 1000,
                        'date' => $tommorow,
                        'start_time' => '10:00',
                        'end_time' => '11:30',
                        'reservation_ids' => [],
                        'repeat_interval_type' => 1,
                        'repeat_end_date' => $dayAfterTommorow
                    ],
                    [
                        'is_available' => 1,
                        'person_capacity' => 10,
                        'reserved_num' => 0,
                        'room_num' => 2,
                        'price' => 1000,
                        'date' => $dayAfterTommorow,
                        'start_time' => '10:00',
                        'end_time' => '11:30',
                        'reservation_ids' => [],
                        'repeat_interval_type' => 1,
                        'repeat_end_date' => $dayAfterTommorow,
                        'is_closed' => 0
                    ],
                ]
            ]
        ];
        $response = $this->postJson(
            "/api/client/hotel/{$this->hotel->id}/reservation_schedule",
            [
                'room_type_id' => $this->hotelRoomTypes[0]->id,
                'date' => $today,
                'person_capacity' => 10,
                'room_num' => 2,
                'price' => 1000,
                'start_time' => '10:00',
                'end_time' => '11:30',
                'repeat_interval_type' => 1,
                'repeat_end_date' => $dayAfterTommorow
            ]
        );
        $response->assertStatus(200)
            ->assertJson($expected);
        $this->assertDatabaseHas('reservation_blocks', [
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'date' => $today,
            'person_capacity' => 10,
            'room_num' => 2,
            'price' => 1000,
            'is_available' => 1,
            'reserved_num' => 0,
            'start_hour' => 10,
            'start_minute' => 0,
            'end_hour' => 11,
            'end_minute' => 30,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('reservation_blocks', [
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'date' => $tommorow,
            'person_capacity' => 10,
            'room_num' => 2,
            'price' => 1000,
            'is_available' => 1,
            'reserved_num' => 0,
            'start_hour' => 10,
            'start_minute' => 0,
            'end_hour' => 11,
            'end_minute' => 30,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('reservation_blocks', [
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'date' => $dayAfterTommorow,
            'person_capacity' => 10,
            'room_num' => 2,
            'price' => 1000,
            'is_available' => 1,
            'reserved_num' => 0,
            'start_hour' => 10,
            'start_minute' => 0,
            'end_hour' => 11,
            'end_minute' => 30,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('reservation_repeat_groups', [
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'start_hour' => 10,
            'start_minute' => 0,
            'end_hour' => 11,
            'end_minute' => 30,
            'repeat_interval_type' => 1,
            'repeat_start_date' => $today,
            'repeat_end_date' => $dayAfterTommorow,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function create_正常系_繰り返し設定2()
    {
        $this->actingAs($this->clientApiToken, 'client_api');
        $now = Carbon::now();
        $today = $now->format('Y-m-d');
        $nextWeek = $now->addDays(7)->format('Y-m-d');
        $inTwoWeeks = $now->addDays(7)->format('Y-m-d');

        $expected = [
            'code' => 200,
            'status' => 'OK',
            'message' => 'SUCCESS',
            'data' => [
                'reservation_blocks' => [
                    [
                        'is_available' => 1,
                        'person_capacity' => 10,
                        'reserved_num' => 0,
                        'room_num' => 2,
                        'price' => 1000,
                        'date' => $today,
                        'start_time' => '10:00',
                        'end_time' => '11:30',
                        'reservation_ids' => [],
                        'repeat_interval_type' => 2,
                        'repeat_end_date' => $inTwoWeeks,
                        'is_closed' => 0
                    ],
                    [
                        'is_available' => 1,
                        'person_capacity' => 10,
                        'reserved_num' => 0,
                        'room_num' => 2,
                        'price' => 1000,
                        'date' => $nextWeek,
                        'start_time' => '10:00',
                        'end_time' => '11:30',
                        'reservation_ids' => [],
                        'repeat_interval_type' => 2,
                        'repeat_end_date' => $inTwoWeeks,
                        'is_closed' => 0
                    ],
                    [
                        'is_available' => 1,
                        'person_capacity' => 10,
                        'reserved_num' => 0,
                        'room_num' => 2,
                        'price' => 1000,
                        'date' => $inTwoWeeks,
                        'start_time' => '10:00',
                        'end_time' => '11:30',
                        'reservation_ids' => [],
                        'repeat_interval_type' => 2,
                        'repeat_end_date' => $inTwoWeeks,
                        'is_closed' => 0
                    ],
                ]
            ]
        ];
        $response = $this->postJson(
            "/api/client/hotel/{$this->hotel->id}/reservation_schedule",
            [
                'room_type_id' => $this->hotelRoomTypes[0]->id,
                'date' => $today,
                'person_capacity' => 10,
                'room_num' => 2,
                'price' => 1000,
                'start_time' => '10:00',
                'end_time' => '11:30',
                'repeat_interval_type' => 2,
                'repeat_end_date' => $inTwoWeeks
            ]
        );
        $response->assertStatus(200)
            ->assertJson($expected);
        $this->assertDatabaseHas('reservation_blocks', [
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'date' => $today,
            'person_capacity' => 10,
            'room_num' => 2,
            'price' => 1000,
            'is_available' => 1,
            'reserved_num' => 0,
            'start_hour' => 10,
            'start_minute' => 0,
            'end_hour' => 11,
            'end_minute' => 30,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('reservation_blocks', [
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'date' => $nextWeek,
            'person_capacity' => 10,
            'room_num' => 2,
            'price' => 1000,
            'is_available' => 1,
            'reserved_num' => 0,
            'start_hour' => 10,
            'start_minute' => 0,
            'end_hour' => 11,
            'end_minute' => 30,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('reservation_blocks', [
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'date' => $inTwoWeeks,
            'person_capacity' => 10,
            'room_num' => 2,
            'price' => 1000,
            'is_available' => 1,
            'reserved_num' => 0,
            'start_hour' => 10,
            'start_minute' => 0,
            'end_hour' => 11,
            'end_minute' => 30,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('reservation_repeat_groups', [
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'start_hour' => 10,
            'start_minute' => 0,
            'end_hour' => 11,
            'end_minute' => 30,
            'repeat_interval_type' => 2,
            'repeat_start_date' => $today,
            'repeat_end_date' => $inTwoWeeks,
            'deleted_at' => null,
        ]);
    }

    // [$input, $expected]
    public function createDataProvider()
    {
        $now = Carbon::now();
        $today = $now->format('Y-m-d');
        return [
            '必須エラー' => [
                [],
                [
                    'code' => 1422,
                    'status' => 'FAIL',
                    'message' => [
                        'room_type_id' => ['部屋タイプIDは必ず指定してください。'],
                        'date' => ['予約枠設定日は必ず指定してください。'],
                        'person_capacity' => ['定員は必ず指定してください。'],
                        'room_num' => ['部屋数は必ず指定してください。'],
                        'price' => ['料金は必ず指定してください。'],
                        'start_time' => ['開始時間は必ず指定してください。'],
                        'end_time' => ['終了時間は必ず指定してください。'],
                        'repeat_interval_type' => ['繰り返し設定は必ず指定してください。'],
                    ],
                ]
            ],
            '必須エラーrepeat_interval_typeが1' => [
                [
                    'room_type_id' => 1,
                    'date' => $today,
                    'person_capacity' => 10,
                    'room_num' => 2,
                    'price' => 1000,
                    'start_time' => '10:00',
                    'end_time' => '11:30',
                    'repeat_interval_type' => 1,
                ],
                [
                    'code' => 1422,
                    'status' => 'FAIL',
                    'message' => [
                        'repeat_end_date' => ['繰り返し設定終了日は必ず指定してください。'],
                    ],
                ]
            ],
            '必須エラーrepeat_interval_typeが2' => [
                [
                    'room_type_id' => 1,
                    'date' => $today,
                    'person_capacity' => 10,
                    'room_num' => 2,
                    'price' => 1000,
                    'start_time' => '10:00',
                    'end_time' => '11:30',
                    'repeat_interval_type' => 2,
                ],
                [
                    'code' => 1422,
                    'status' => 'FAIL',
                    'message' => [
                        'repeat_end_date' => ['繰り返し設定終了日は必ず指定してください。'],
                    ],
                ]
            ],
            '数値エラー' => [
                [
                    'room_type_id' => 'a',
                    'date' => $today,
                    'person_capacity' => 'a',
                    'room_num' => 'a',
                    'price' => 'a',
                    'start_time' => '10:00',
                    'end_time' => '11:30',
                    'repeat_interval_type' => 'a',
                ],
                [
                    'code' => 1422,
                    'status' => 'FAIL',
                    'message' => [
                        'room_type_id' => ['部屋タイプIDは整数で指定してください。'],
                        'person_capacity' => ['定員は整数で指定してください。'],
                        'room_num' => ['部屋数は整数で指定してください。'],
                        'price' => ['料金は整数で指定してください。'],
                        'repeat_interval_type' => ['繰り返し設定は整数で指定してください。'],
                    ],
                ]
            ],
            '日付エラー' => [
                [
                    'room_type_id' => 1,
                    'date' => 1,
                    'person_capacity' => 10,
                    'room_num' => 2,
                    'price' => 1000,
                    'start_time' => '10:00',
                    'end_time' => '11:30',
                    'repeat_interval_type' => 1,
                    'repeat_end_date' => 1,
                ],
                [
                    'code' => 1422,
                    'status' => 'FAIL',
                    'message' => [
                        'date' => ['予約枠設定日には有効な日付を指定してください。', "予約枠設定日には、{$today}以後の日付を指定してください。"],
                        'repeat_end_date' => ['繰り返し設定終了日には有効な日付を指定してください。'],
                    ],
                ]
            ],
            '最小値エラー' => [
                [
                    'room_type_id' => 0,
                    'date' => $today,
                    'person_capacity' => 0,
                    'room_num' => 0,
                    'price' => -1,
                    'start_time' => '10:00',
                    'end_time' => '11:30',
                    'repeat_interval_type' => -1,
                ],
                [
                    'code' => 1422,
                    'status' => 'FAIL',
                    'message' => [
                        'room_type_id' => ['部屋タイプIDには、1以上の数字を指定してください。'],
                        'person_capacity' => ['定員には、1以上の数字を指定してください。'],
                        'room_num' => ['部屋数には、1以上の数字を指定してください。'],
                        'price' => ['料金には、0以上の数字を指定してください。'],
                        'repeat_interval_type' => ['繰り返し設定には、0以上の数字を指定してください。'],
                    ],
                ]
            ],
            '最大値エラー' => [
                [
                    'room_type_id' => 1,
                    'date' => $today,
                    'person_capacity' => 1,
                    'room_num' => 1,
                    'price' => 0,
                    'start_time' => '10:00',
                    'end_time' => '11:30',
                    'repeat_interval_type' => 3,
                ],
                [
                    'code' => 1422,
                    'status' => 'FAIL',
                    'message' => [
                        'repeat_interval_type' => ['繰り返し設定には、2以下の数字を指定してください。'],
                    ],
                ]
            ],
            '日付以後エラー' => [
                [
                    'room_type_id' => 1,
                    'date' => $now->addDays(-1)->format('Y-m-d'),
                    'person_capacity' => 1,
                    'room_num' => 1,
                    'price' => 0,
                    'start_time' => '10:00',
                    'end_time' => '11:30',
                    'repeat_interval_type' => 1,
                    'repeat_end_date' => $now->addDays(-1)->format('Y-m-d'),
                ],
                [
                    'code' => 1422,
                    'status' => 'FAIL',
                    'message' => [
                        'date' => ["予約枠設定日には、{$today}以後の日付を指定してください。"],
                        'repeat_end_date' => ['繰り返し設定終了日には、予約枠設定日以後の日付を指定してください。'],
                    ],
                ]
            ],
            '時刻エラー' => [
                [
                    'room_type_id' => 1,
                    'date' => $today,
                    'person_capacity' => 1,
                    'room_num' => 1,
                    'price' => 0,
                    'start_time' => '10:60',
                    'end_time' => '1130',
                    'repeat_interval_type' => 1,
                    'repeat_end_date' => $today,
                ],
                [
                    'code' => 1422,
                    'status' => 'FAIL',
                    'message' => [
                        'start_time' => ['開始時間には時刻を指定してください。'],
                        'end_time' => ['終了時間には時刻を指定してください。'],
                    ],
                ]
            ],
        ];
    }

    /**
     * @dataProvider createDataProvider
     * @test
     */
    public function create_異常系_validationError($input, $expected)
    {
        $this->actingAs($this->clientApiToken, 'client_api');
        $response = $this->postJson(
            "/api/client/hotel/{$this->hotel->id}/reservation_schedule",
            $input
        );
        $response->assertStatus(200)
            ->assertExactJson($expected);
    }

    /** @test */
    public function create_異常系_未認証()
    {
        $response = $this->postJson(
            "/api/client/hotel/{$this->hotel->id}/reservation_schedule",
            []
        );
        $response->assertStatus(401);
    }

    /** @test */
    public function create_異常系_ログインユーザーに紐づくhotel_idではない()
    {
        $this->actingAs($this->clientApiToken, 'client_api');
        $now = Carbon::now();
        $today = $now->format('Y-m-d');
        $response = $this->postJson(
            "/api/client/hotel/0/reservation_schedule",
            [
                'room_type_id' => $this->hotelRoomTypes[0]->id,
                'date' => $today,
                'person_capacity' => 10,
                'room_num' => 2,
                'price' => 1000,
                'start_time' => '10:00',
                'end_time' => '11:30',
                'repeat_interval_type' => 0,
                'repeat_end_date' => ''
            ]
        );
        $response->assertJsonPath('code', 404);
    }

    /** @test */
    public function edit_正常系_個別更新()
    {
        $this->actingAs($this->clientApiToken, 'client_api');
        $now = Carbon::now();
        $today = $now->format('Y-m-d');
        $tommorow = $now->addDay()->format('Y-m-d');
        $dayAfterTommorow = $now->addDay()->format('Y-m-d');

        $resavationRepeatGroup = factory(ReservationRepeatGroup::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'start_hour' => 9,
            'start_minute' => 0,
            'end_hour' => 23,
            'end_minute' => 30,
            'repeat_interval_type' => 1,
            'repeat_start_date' => $today,
            'repeat_end_date' => $tommorow,
        ]);
        $reservationBlock = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'reservation_repeat_group_id' => $resavationRepeatGroup->id,
            'room_num' => 1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $tommorow,
            'start_hour' => 9,
            'start_minute' => 0,
            'end_hour' => 23,
            'end_minute' => 30,
        ]);
        factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'reservation_repeat_group_id' => $resavationRepeatGroup->id,
            'room_num' => 1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $tommorow,
            'start_hour' => 9,
            'start_minute' => 0,
            'end_hour' => 23,
            'end_minute' => 30,
        ]);

        $expected = [
            'code' => 200,
            'status' => 'OK',
            'message' => 'SUCCESS',
            'data' => [
                'reservation_blocks' => [
                    [
                        'reservation_block_id' => $reservationBlock->id,
                        'reservation_repeat_group_id' => null,
                        'is_available' => 1,
                        'person_capacity' => 10,
                        'reserved_num' => 0,
                        'room_num' => 20,
                        'price' => 10000,
                        'date' => $dayAfterTommorow,
                        'start_time' => '08:05',
                        'end_time' => '27:15',
                        'reservation_ids' => [],
                        'repeat_interval_type' => 0,
                        'repeat_end_date' => '',
                        'is_closed' => 0
                    ],
                ]
            ]
        ];
        $response = $this->putJson(
            "/api/client/hotel/{$this->hotel->id}/reservation_schedule/{$reservationBlock->id}",
            [
                'room_type_id' => $this->hotelRoomTypes[1]->id,
                'date' => $dayAfterTommorow,
                'person_capacity' => 10,
                'room_num' => 20,
                'price' => 10000,
                'start_time' => '08:05',
                'end_time' => '27:15',
                'update_type' => 0,
            ]
        );
        $response->assertStatus(200)
            ->assertExactJson($expected);

        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlock->id,
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[1]->id,
            'reservation_repeat_group_id' => null,
            'date' => $dayAfterTommorow,
            'person_capacity' => 10,
            'room_num' => 20,
            'price' => 10000,
            'is_available' => 1,
            'reserved_num' => 0,
            'start_hour' => 8,
            'start_minute' => 5,
            'end_hour' => 27,
            'end_minute' => 15,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function edit_正常系_グループ更新()
    {
        $this->actingAs($this->clientApiToken, 'client_api');
        $now = Carbon::now();
        $yesterday = $now->addDays(-1)->format('Y-m-d');
        $today = $now->addDay()->format('Y-m-d');
        $tommorow = $now->addDay()->format('Y-m-d');
        $dayAfterTommorow = $now->addDay()->format('Y-m-d');

        // 更新される予約枠
        $resavationRepeatGroup = factory(ReservationRepeatGroup::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'start_hour' => 24,
            'start_minute' => 5,
            'end_hour' => 27,
            'end_minute' => 30,
            'repeat_interval_type' => 1,
            'repeat_start_date' => $yesterday,
            'repeat_end_date' => $dayAfterTommorow,
        ]);
        $reservationBlock1 = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'reservation_repeat_group_id' => $resavationRepeatGroup->id,
            'room_num' => 1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $today,
            'start_hour' => 24,
            'start_minute' => 5,
            'end_hour' => 27,
            'end_minute' => 30,
        ]);
        $reservationBlock2 = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'reservation_repeat_group_id' => $resavationRepeatGroup->id,
            'room_num' => 1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $tommorow,
            'start_hour' => 24,
            'start_minute' => 5,
            'end_hour' => 27,
            'end_minute' => 30,
        ]);

        // 更新されない予約枠
        $reservationBlock3 = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'reservation_repeat_group_id' => $resavationRepeatGroup->id,
            'reserved_num' => 1,
            'room_num' => 1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $dayAfterTommorow,
            'start_hour' => 24,
            'start_minute' => 5,
            'end_hour' => 27,
            'end_minute' => 30,
        ]);
        factory(ReservedReservationBlock::class)->create([
            'reservation_id' => 1,
            'reservation_block_id' => $reservationBlock3->id,
            'customer_id' => 0,
            'line_user_id' => 0,
        ]);
        $reservationBlock4 = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'reservation_repeat_group_id' => $resavationRepeatGroup->id,
            'room_num' => 1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $yesterday,
            'start_hour' => 20,
            'start_minute' => 5,
            'end_hour' => 22,
            'end_minute' => 30,
        ]);

        $expected = [
            'code' => 200,
            'status' => 'OK',
            'message' => 'SUCCESS',
            'data' => [
                'reservation_blocks' => [
                    [
                        'reservation_block_id' => $reservationBlock1->id,
                        'is_available' => 1,
                        'person_capacity' => 10,
                        'reserved_num' => 0,
                        'room_num' => 20,
                        'price' => 10000,
                        'date' => $today,
                        'start_time' => '25:35',
                        'end_time' => '28:00',
                        'reservation_ids' => [],
                        'repeat_interval_type' => 1,
                        'repeat_end_date' => $dayAfterTommorow,
                        'is_closed' => 0
                    ],
                    [
                        'reservation_block_id' => $reservationBlock2->id,
                        'is_available' => 1,
                        'person_capacity' => 10,
                        'reserved_num' => 0,
                        'room_num' => 20,
                        'price' => 10000,
                        'date' => $tommorow,
                        'start_time' => '25:35',
                        'end_time' => '28:00',
                        'reservation_ids' => [],
                        'repeat_interval_type' => 1,
                        'repeat_end_date' => $dayAfterTommorow,
                        'is_closed' => 0
                    ],
                ]
            ]
        ];
        $response = $this->putJson(
            "/api/client/hotel/{$this->hotel->id}/reservation_schedule/{$reservationBlock1->id}",
            [
                'room_type_id' => $this->hotelRoomTypes[1]->id,
                'date' => $today,
                'person_capacity' => 10,
                'room_num' => 20,
                'price' => 10000,
                'start_time' => '25:35',
                'end_time' => '28:00',
                'update_type' => 1,
            ]
        );
        $response->assertStatus(200)
            ->assertJson($expected);

        // 更新されている
        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlock1->id,
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[1]->id,
            'date' => $today,
            'person_capacity' => 10,
            'room_num' => 20,
            'price' => 10000,
            'is_available' => 1,
            'reserved_num' => 0,
            'start_hour' => 25,
            'start_minute' => 35,
            'end_hour' => 28,
            'end_minute' => 0,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlock2->id,
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[1]->id,
            'date' => $tommorow,
            'person_capacity' => 10,
            'room_num' => 20,
            'price' => 10000,
            'is_available' => 1,
            'reserved_num' => 0,
            'start_hour' => 25,
            'start_minute' => 35,
            'end_hour' => 28,
            'end_minute' => 0,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('reservation_repeat_groups', [
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'start_hour' => 25,
            'start_minute' => 35,
            'end_hour' => 28,
            'end_minute' => 0,
            'repeat_interval_type' => 1,
            'repeat_start_date' => $yesterday,
            'repeat_end_date' => $dayAfterTommorow,
            'deleted_at' => null,
        ]);

        // 更新されていない
        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlock3->id,
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'reservation_repeat_group_id' => $resavationRepeatGroup->id,
            'date' => $dayAfterTommorow,
            'person_capacity' => 2,
            'room_num' => 1,
            'price' => 1000,
            'is_available' => 1,
            'reserved_num' => 1,
            'start_hour' => 24,
            'start_minute' => 5,
            'end_hour' => 27,
            'end_minute' => 30,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlock4->id,
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'reservation_repeat_group_id' => $resavationRepeatGroup->id,
            'date' => $yesterday,
            'person_capacity' => 2,
            'room_num' => 1,
            'price' => 1000,
            'is_available' => 1,
            'reserved_num' => 0,
            'start_hour' => 20,
            'start_minute' => 5,
            'end_hour' => 22,
            'end_minute' => 30,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function edit_異常系_個別更新_過去の予約枠なので更新されない()
    {
        $this->actingAs($this->clientApiToken, 'client_api');
        $now = Carbon::now();
        $yesterday = $now->addDays(-1)->format('Y-m-d');
        $tommorow = $now->addDays(2)->format('Y-m-d');
        $reservationBlock = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'room_num' => 1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $yesterday,
            'start_hour' => 9,
            'start_minute' => 0,
            'end_hour' => 23,
            'end_minute' => 30,
        ]);

        $expected = [
            'code' => 500,
            'status' => 'FAIL',
            'message' => '更新できる予約枠がありませんでした',
        ];
        $response = $this->putJson(
            "/api/client/hotel/{$this->hotel->id}/reservation_schedule/{$reservationBlock->id}",
            [
                'room_type_id' => $this->hotelRoomTypes[1]->id,
                'date' => $tommorow,
                'person_capacity' => 10,
                'room_num' => 20,
                'price' => 10000,
                'start_time' => '08:05',
                'end_time' => '27:15',
                'update_type' => 0,
            ]
        );
        $response->assertStatus(200)
            ->assertExactJson($expected);

        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlock->id,
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'reservation_repeat_group_id' => null,
            'date' => $yesterday,
            'person_capacity' => 2,
            'room_num' => 1,
            'price' => 1000,
            'is_available' => 1,
            'reserved_num' => 0,
            'start_hour' => 9,
            'start_minute' => 0,
            'end_hour' => 23,
            'end_minute' => 30,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function edit_異常系_個別更新_予約済みなので更新されない()
    {
        $this->actingAs($this->clientApiToken, 'client_api');
        $now = Carbon::now();
        $tommorow = $now->addDay()->format('Y-m-d');
        $dayAfterTommorow = $now->addDay()->format('Y-m-d');

        $reservationBlock = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'reserved_num' => 1,
            'room_num' => 1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $tommorow,
            'start_hour' => 9,
            'start_minute' => 0,
            'end_hour' => 23,
            'end_minute' => 30,
        ]);
        factory(ReservedReservationBlock::class)->create([
            'reservation_id' => 1,
            'reservation_block_id' => $reservationBlock->id,
            'customer_id' => 0,
            'line_user_id' => 0,
        ]);
        $expected = [
            'code' => 500,
            'status' => 'FAIL',
            'message' => '更新できる予約枠がありませんでした',
        ];
        $response = $this->putJson(
            "/api/client/hotel/{$this->hotel->id}/reservation_schedule/{$reservationBlock->id}",
            [
                'room_type_id' => $this->hotelRoomTypes[1]->id,
                'date' => $dayAfterTommorow,
                'person_capacity' => 10,
                'room_num' => 20,
                'price' => 10000,
                'start_time' => '08:05',
                'end_time' => '27:15',
                'update_type' => 0,
            ]
        );
        $response->assertStatus(200)
            ->assertExactJson($expected);

        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlock->id,
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'reservation_repeat_group_id' => null,
            'date' => $tommorow,
            'person_capacity' => 2,
            'room_num' => 1,
            'price' => 1000,
            'is_available' => 1,
            'reserved_num' => 1,
            'start_hour' => 9,
            'start_minute' => 0,
            'end_hour' => 23,
            'end_minute' => 30,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function edit_異常系_グループ更新_グループではない場合、更新されない()
    {
        $this->actingAs($this->clientApiToken, 'client_api');
        $now = Carbon::now();
        $today = $now->format('Y-m-d');

        $reservationBlock = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'room_num' => 1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $today,
            'start_hour' => 24,
            'start_minute' => 5,
            'end_hour' => 27,
            'end_minute' => 30,
        ]);

        $expected = [
            'code' => 500,
            'status' => 'FAIL',
            'message' => '更新できる予約枠がありませんでした',
        ];
        $response = $this->putJson(
            "/api/client/hotel/{$this->hotel->id}/reservation_schedule/{$reservationBlock->id}",
            [
                'room_type_id' => $this->hotelRoomTypes[1]->id,
                'date' => $today,
                'person_capacity' => 10,
                'room_num' => 20,
                'price' => 10000,
                'start_time' => '25:35',
                'end_time' => '28:00',
                'update_type' => 1,
            ]
        );
        $response->assertStatus(200)
            ->assertExactJson($expected);

        // 更新されていない
        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlock->id,
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'room_num' => 1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $today,
            'start_hour' => 24,
            'start_minute' => 5,
            'end_hour' => 27,
            'end_minute' => 30,
            'deleted_at' => null,
        ]);
    }


    /** @test */
    public function edit_異常系_グループ更新_すべて過去の予約枠もしくは予約済みの場合、更新されない()
    {
        $this->actingAs($this->clientApiToken, 'client_api');
        $now = Carbon::now();
        $yesterday = $now->addDays(-1)->format('Y-m-d');
        $today = $now->addDay()->format('Y-m-d');
        $tommorow = $now->addDay()->format('Y-m-d');
        $dayAfterTommorow = $now->addDay()->format('Y-m-d');

        $resavationRepeatGroup = factory(ReservationRepeatGroup::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'start_hour' => 24,
            'start_minute' => 5,
            'end_hour' => 27,
            'end_minute' => 30,
            'repeat_interval_type' => 1,
            'repeat_start_date' => $yesterday,
            'repeat_end_date' => $dayAfterTommorow,
        ]);
        $reservationBlock1 = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'reservation_repeat_group_id' => $resavationRepeatGroup->id,
            'reserved_num' => 1,
            'room_num' => 1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $dayAfterTommorow,
            'start_hour' => 24,
            'start_minute' => 5,
            'end_hour' => 27,
            'end_minute' => 30,
        ]);
        factory(ReservedReservationBlock::class)->create([
            'reservation_id' => 1,
            'reservation_block_id' => $reservationBlock1->id,
            'customer_id' => 0,
            'line_user_id' => 0,
        ]);
        $reservationBlock2 = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'reservation_repeat_group_id' => $resavationRepeatGroup->id,
            'room_num' => 1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $yesterday,
            'start_hour' => 20,
            'start_minute' => 5,
            'end_hour' => 22,
            'end_minute' => 30,
        ]);

        $expected = [
            'code' => 500,
            'status' => 'FAIL',
            'message' => '更新できる予約枠がありませんでした',
        ];
        $response = $this->putJson(
            "/api/client/hotel/{$this->hotel->id}/reservation_schedule/{$reservationBlock1->id}",
            [
                'room_type_id' => $this->hotelRoomTypes[1]->id,
                'date' => $today,
                'person_capacity' => 10,
                'room_num' => 20,
                'price' => 10000,
                'start_time' => '25:35',
                'end_time' => '28:00',
                'update_type' => 1,
            ]
        );
        $response->assertStatus(200)
            ->assertExactJson($expected);

        // 更新されていない
        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlock1->id,
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'reservation_repeat_group_id' => $resavationRepeatGroup->id,
            'reserved_num' => 1,
            'room_num' => 1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $dayAfterTommorow,
            'start_hour' => 24,
            'start_minute' => 5,
            'end_hour' => 27,
            'end_minute' => 30,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlock2->id,
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'reservation_repeat_group_id' => $resavationRepeatGroup->id,
            'reserved_num' => 0,
            'room_num' => 1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $yesterday,
            'start_hour' => 20,
            'start_minute' => 5,
            'end_hour' => 22,
            'end_minute' => 30,
            'deleted_at' => null,
        ]);

        // 未使用の予約枠グループはソフトデリートされている
        $this->assertSoftDeleted('reservation_repeat_groups', [
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'start_hour' => 25,
            'start_minute' => 35,
            'end_hour' => 28,
            'end_minute' => 0,
            'repeat_interval_type' => 1,
            'repeat_start_date' => $yesterday,
            'repeat_end_date' => $dayAfterTommorow,
        ]);
    }

    // [$input, $expected]
    public function editDataProvider()
    {
        $now = Carbon::now();
        $today = $now->format('Y-m-d');
        return [
            '必須エラー' => [
                [],
                [
                    'code' => 1422,
                    'status' => 'FAIL',
                    'message' => [
                        'room_type_id' => ['部屋タイプIDは必ず指定してください。'],
                        'date' => ['予約枠設定日は必ず指定してください。'],
                        'person_capacity' => ['定員は必ず指定してください。'],
                        'room_num' => ['部屋数は必ず指定してください。'],
                        'price' => ['料金は必ず指定してください。'],
                        'start_time' => ['開始時間は必ず指定してください。'],
                        'end_time' => ['終了時間は必ず指定してください。'],
                        'update_type' => ['更新タイプは必ず指定してください。'],
                    ],
                ]
            ],
            '数値エラー' => [
                [
                    'room_type_id' => 'a',
                    'date' => $today,
                    'person_capacity' => 'a',
                    'room_num' => 'a',
                    'price' => 'a',
                    'start_time' => '10:00',
                    'end_time' => '11:30',
                    'update_type' => 'a',
                ],
                [
                    'code' => 1422,
                    'status' => 'FAIL',
                    'message' => [
                        'room_type_id' => ['部屋タイプIDは整数で指定してください。'],
                        'person_capacity' => ['定員は整数で指定してください。'],
                        'room_num' => ['部屋数は整数で指定してください。'],
                        'price' => ['料金は整数で指定してください。'],
                        'update_type' => ['更新タイプは整数で指定してください。'],
                    ],
                ]
            ],
            '日付エラー' => [
                [
                    'room_type_id' => 1,
                    'date' => 1,
                    'person_capacity' => 10,
                    'room_num' => 2,
                    'price' => 1000,
                    'start_time' => '10:00',
                    'end_time' => '11:30',
                    'update_type' => 0,
                ],
                [
                    'code' => 1422,
                    'status' => 'FAIL',
                    'message' => [
                        'date' => ['予約枠設定日には有効な日付を指定してください。', "予約枠設定日には、{$today}以後の日付を指定してください。"],
                    ],
                ]
            ],
            '最小値エラー' => [
                [
                    'room_type_id' => 0,
                    'date' => $today,
                    'person_capacity' => 0,
                    'room_num' => 0,
                    'price' => -1,
                    'start_time' => '10:00',
                    'end_time' => '11:30',
                    'update_type' => -1,
                ],
                [
                    'code' => 1422,
                    'status' => 'FAIL',
                    'message' => [
                        'room_type_id' => ['部屋タイプIDには、1以上の数字を指定してください。'],
                        'person_capacity' => ['定員には、1以上の数字を指定してください。'],
                        'room_num' => ['部屋数には、1以上の数字を指定してください。'],
                        'price' => ['料金には、0以上の数字を指定してください。'],
                        'update_type' => ['更新タイプには、0以上の数字を指定してください。'],
                    ],
                ]
            ],
            '最大値エラー' => [
                [
                    'room_type_id' => 1,
                    'date' => $today,
                    'person_capacity' => 1,
                    'room_num' => 1,
                    'price' => 0,
                    'start_time' => '10:00',
                    'end_time' => '11:30',
                    'update_type' => 2,
                ],
                [
                    'code' => 1422,
                    'status' => 'FAIL',
                    'message' => [
                        'update_type' => ['更新タイプには、1以下の数字を指定してください。'],
                    ],
                ]
            ],
            '日付以後エラー' => [
                [
                    'room_type_id' => 1,
                    'date' => $now->addDays(-1)->format('Y-m-d'),
                    'person_capacity' => 1,
                    'room_num' => 1,
                    'price' => 0,
                    'start_time' => '10:00',
                    'end_time' => '11:30',
                    'update_type' => 1,
                ],
                [
                    'code' => 1422,
                    'status' => 'FAIL',
                    'message' => [
                        'date' => ["予約枠設定日には、{$today}以後の日付を指定してください。"],
                    ],
                ]
            ],
            '時刻エラー' => [
                [
                    'room_type_id' => 1,
                    'date' => $today,
                    'person_capacity' => 1,
                    'room_num' => 1,
                    'price' => 0,
                    'start_time' => '10:60',
                    'end_time' => '1130',
                    'update_type' => 1,
                ],
                [
                    'code' => 1422,
                    'status' => 'FAIL',
                    'message' => [
                        'start_time' => ['開始時間には時刻を指定してください。'],
                        'end_time' => ['終了時間には時刻を指定してください。'],
                    ],
                ]
            ],
        ];
    }

    /**
     * @dataProvider editDataProvider
     * @test
     */
    public function edit_異常系_validationError($input, $expected)
    {
        $this->actingAs($this->clientApiToken, 'client_api');
        $response = $this->putJson(
            "/api/client/hotel/{$this->hotel->id}/reservation_schedule/1",
            $input
        );
        $response->assertStatus(200)
            ->assertExactJson($expected);
    }

    /** @test */
    public function edit_異常系_未認証()
    {
        $response = $this->putJson(
            "/api/client/hotel/{$this->hotel->id}/reservation_schedule/1",
            []
        );
        $response->assertStatus(401);
    }

    /** @test */
    public function edit_異常系_ログインユーザーに紐づくhotel_idではない()
    {
        $this->actingAs($this->clientApiToken, 'client_api');
        $now = Carbon::now();
        $today = $now->format('Y-m-d');
        $response = $this->putJson(
            "/api/client/hotel/0/reservation_schedule/1",
            [
                'room_type_id' => $this->hotelRoomTypes[0]->id,
                'date' => $today,
                'person_capacity' => 10,
                'room_num' => 20,
                'price' => 10000,
                'start_time' => '24:05',
                'end_time' => '27:15',
                'update_type' => 0,
            ]
        );
        $response->assertJsonPath('code', 404);
    }

    /** @test */
    public function edit_異常系_更新対象の予約枠がホテルに紐付いていない()
    {
        $this->actingAs($this->clientApiToken, 'client_api');
        $now = Carbon::now();
        $today = $now->format('Y-m-d');

        $reservationBlock = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id + 1,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'reserved_num' => 1,
            'room_num' => 1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $today,
            'start_hour' => 24,
            'start_minute' => 5,
            'end_hour' => 27,
            'end_minute' => 30,
        ]);

        $expected = [
            'code' => 500,
            'status' => 'FAIL',
            'message' => '更新できる予約枠がありませんでした',
        ];

        $response = $this->putJson(
            "/api/client/hotel/{$this->hotel->id}/reservation_schedule/{$reservationBlock->id}",
            [
                'room_type_id' => $this->hotelRoomTypes[0]->id,
                'date' => $today,
                'person_capacity' => 10,
                'room_num' => 20,
                'price' => 10000,
                'start_time' => '24:05',
                'end_time' => '27:15',
                'update_type' => 0,
            ]
        );
        $response->assertStatus(200)
            ->assertExactJson($expected);
    }

    /** @test */
    public function delete_正常系_削除成功()
    {
        $this->actingAs($this->clientApiToken, 'client_api');
        $now = Carbon::now();
        $today = $now->format('Y-m-d');
        $tommorow = $now->addDay()->format('Y-m-d');

        $resavationRepeatGroup = factory(ReservationRepeatGroup::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'start_hour' => 24,
            'start_minute' => 5,
            'end_hour' => 25,
            'end_minute' => 30,
            'repeat_interval_type' => 1,
            'repeat_start_date' => $today,
            'repeat_end_date' => $tommorow,
        ]);
        $reservationBlock1 = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'reservation_repeat_group_id' => $resavationRepeatGroup->id,
            'date' => $tommorow,
            'start_hour' => 24,
            'start_minute' => 5,
            'end_hour' => 25,
            'end_minute' => 30,
        ]);
        $reservationBlock2 = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'reservation_repeat_group_id' => $resavationRepeatGroup->id,
            'date' => $tommorow,
            'start_hour' => 24,
            'start_minute' => 5,
            'end_hour' => 25,
            'end_minute' => 30,
        ]);

        $expected = [
            'code' => 200,
            'status' => 'OK',
            'message' => 'SUCCESS',
            'data' => null
        ];
        $response = $this->deleteJson("/api/client/hotel/{$this->hotel->id}/reservation_schedule/delete/{$reservationBlock1->id}");
        $response->assertStatus(200)
            ->assertExactJson($expected);

        $this->assertSoftDeleted($reservationBlock1);
        $this->assertDatabaseHas(
            'reservation_blocks',
            [
                'id' => $reservationBlock2->id,
                'deleted_at' => null,
            ]
        );
    }

    /** @test */
    public function delete_異常系_過去の予約枠なのでスキップされる()
    {
        $this->actingAs($this->clientApiToken, 'client_api');
        $now = Carbon::now();
        $yesterday = $now->addDays(-1)->format('Y-m-d');

        $reservationBlock = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'date' => $yesterday,
            'start_hour' => 20,
            'start_minute' => 5,
            'end_hour' => 23,
            'end_minute' => 30,
        ]);

        $expected = [
            'code' => 500,
            'status' => 'FAIL',
            'message' => '削除できる予約枠がありませんでした',
        ];
        $response = $this->deleteJson("/api/client/hotel/{$this->hotel->id}/reservation_schedule/delete/{$reservationBlock->id}");
        $response->assertStatus(200)
            ->assertExactJson($expected);

        $this->assertDatabaseHas(
            'reservation_blocks',
            [
                'id' => $reservationBlock->id,
                'deleted_at' => null,
            ]
        );
    }

    /** @test */
    public function delete_異常系_すでに予約が入っている予約枠なのでスキップされる()
    {
        $this->actingAs($this->clientApiToken, 'client_api');
        $now = Carbon::now();
        $tommorow = $now->addDay()->format('Y-m-d');

        $reservationBlock = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'date' => $tommorow,
            'reserved_num' => 1,
            'start_hour' => 20,
            'start_minute' => 5,
            'end_hour' => 23,
            'end_minute' => 30,
        ]);
        factory(ReservedReservationBlock::class)->create([
            'reservation_id' => 1,
            'reservation_block_id' => $reservationBlock->id,
            'customer_id' => 0,
            'line_user_id' => 0,
        ]);

        $expected = [
            'code' => 500,
            'status' => 'FAIL',
            'message' => '削除できる予約枠がありませんでした',
        ];
        $response = $this->deleteJson("/api/client/hotel/{$this->hotel->id}/reservation_schedule/delete/{$reservationBlock->id}");
        $response->assertStatus(200)
            ->assertExactJson($expected);

        $this->assertDatabaseHas(
            'reservation_blocks',
            [
                'id' => $reservationBlock->id,
                'deleted_at' => null,
            ]
        );
    }

    /** @test */
    public function delete_異常系_未認証()
    {
        $response = $this->deleteJson("/api/client/hotel/{$this->hotel->id}/reservation_schedule/delete/1");
        $response->assertStatus(401);
    }

    /** @test */
    public function delete_異常系_ログインユーザーに紐づくhotel_idではない()
    {
        $this->actingAs($this->clientApiToken, 'client_api');
        $response = $this->deleteJson("/api/client/hotel/0/reservation_schedule/delete/1");
        $response->assertJsonPath('code', 404);
    }

    /** @test */
    public function delete_異常系_削除対象の予約枠がホテルに紐付いていない()
    {
        $this->actingAs($this->clientApiToken, 'client_api');
        $now = Carbon::now();
        $today = $now->format('Y-m-d');

        $reservationBlock = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id + 1,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'room_num' => 1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $today,
            'start_hour' => 24,
            'start_minute' => 5,
            'end_hour' => 27,
            'end_minute' => 30,
        ]);

        $expected = [
            'code' => 500,
            'status' => 'FAIL',
            'message' => '削除できる予約枠がありませんでした',
        ];

        $response = $this->deleteJson("/api/client/hotel/{$this->hotel->id}/reservation_schedule/delete/{$reservationBlock->id}");
        $response->assertStatus(200)
            ->assertExactJson($expected);

        $this->assertDatabaseHas(
            'reservation_blocks',
            [
                'id' => $reservationBlock->id,
                'deleted_at' => null,
            ]
        );
    }

    /** @test */
    public function delete_group_正常系_削除成功()
    {
        $this->actingAs($this->clientApiToken, 'client_api');
        $now = Carbon::now();
        $yesterday = $now->addDays(-1)->format('Y-m-d');
        $today = $now->addDay()->format('Y-m-d');
        $tommorow = $now->addDay()->format('Y-m-d');
        $dayAfterTommorow = $now->addDay()->format('Y-m-d');

        // 削除される予約枠
        $resavationRepeatGroup = factory(ReservationRepeatGroup::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'start_hour' => 24,
            'start_minute' => 5,
            'end_hour' => 27,
            'end_minute' => 30,
            'repeat_interval_type' => 1,
            'repeat_start_date' => $yesterday,
            'repeat_end_date' => $dayAfterTommorow,
        ]);
        $reservationBlock1 = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'reservation_repeat_group_id' => $resavationRepeatGroup->id,
            'room_num' => 1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $today,
            'start_hour' => 24,
            'start_minute' => 5,
            'end_hour' => 27,
            'end_minute' => 30,
        ]);
        $reservationBlock2 = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'reservation_repeat_group_id' => $resavationRepeatGroup->id,
            'room_num' => 1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $tommorow,
            'start_hour' => 24,
            'start_minute' => 5,
            'end_hour' => 27,
            'end_minute' => 30,
        ]);

        // 削除されない予約枠
        $reservationBlock3 = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'reservation_repeat_group_id' => $resavationRepeatGroup->id,
            'reserved_num' => 1,
            'room_num' => 1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $dayAfterTommorow,
            'start_hour' => 24,
            'start_minute' => 5,
            'end_hour' => 27,
            'end_minute' => 30,
        ]);
        factory(ReservedReservationBlock::class)->create([
            'reservation_id' => 1,
            'reservation_block_id' => $reservationBlock3->id,
            'customer_id' => 0,
            'line_user_id' => 0,
        ]);
        $reservationBlock4 = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'reservation_repeat_group_id' => $resavationRepeatGroup->id,
            'room_num' => 1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $yesterday,
            'start_hour' => 20,
            'start_minute' => 5,
            'end_hour' => 21,
            'end_minute' => 30,
        ]);

        $expected = [
            'code' => 200,
            'status' => 'OK',
            'message' => 'SUCCESS',
            'data' => null
        ];
        $response = $this->deleteJson("/api/client/hotel/{$this->hotel->id}/reservation_schedule/delete_group/{$reservationBlock1->id}");
        $response->assertStatus(200)
            ->assertExactJson($expected);

        // 削除されている
        $this->assertSoftDeleted($reservationBlock1);
        $this->assertSoftDeleted($reservationBlock2);

        // 削除されていない
        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlock3->id,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlock4->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function delete_group_異常系_すべて過去の予約枠もしくは予約済みの場合、削除されない()
    {
        $this->actingAs($this->clientApiToken, 'client_api');
        $now = Carbon::now();
        $yesterday = $now->addDays(-1)->format('Y-m-d');
        $today = $now->addDay()->format('Y-m-d');
        $tommorow = $now->addDay()->format('Y-m-d');
        $dayAfterTommorow = $now->addDay()->format('Y-m-d');

        $resavationRepeatGroup = factory(ReservationRepeatGroup::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'start_hour' => 24,
            'start_minute' => 5,
            'end_hour' => 27,
            'end_minute' => 30,
            'repeat_interval_type' => 1,
            'repeat_start_date' => $yesterday,
            'repeat_end_date' => $dayAfterTommorow,
        ]);

        // 削除されない予約枠
        $reservationBlock1 = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'reservation_repeat_group_id' => $resavationRepeatGroup->id,
            'reserved_num' => 1,
            'room_num' => 1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $dayAfterTommorow,
            'start_hour' => 24,
            'start_minute' => 5,
            'end_hour' => 27,
            'end_minute' => 30,
        ]);
        factory(ReservedReservationBlock::class)->create([
            'reservation_id' => 1,
            'reservation_block_id' => $reservationBlock1->id,
            'customer_id' => 0,
            'line_user_id' => 0,
        ]);
        $reservationBlock2 = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'reservation_repeat_group_id' => $resavationRepeatGroup->id,
            'room_num' => 1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $yesterday,
            'start_hour' => 20,
            'start_minute' => 5,
            'end_hour' => 21,
            'end_minute' => 30,
        ]);

        $expected = [
            'code' => 500,
            'status' => 'FAIL',
            'message' => '削除できる予約枠がありませんでした',
        ];
        $response = $this->deleteJson("/api/client/hotel/{$this->hotel->id}/reservation_schedule/delete_group/{$reservationBlock1->id}");
        $response->assertStatus(200)
            ->assertExactJson($expected);

        // 削除されていない
        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlock1->id,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlock2->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function delete_group_異常系_グループではない場合、削除されない()
    {
        $this->actingAs($this->clientApiToken, 'client_api');
        $now = Carbon::now();
        $today = $now->format('Y-m-d');

        $reservationBlock = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'room_num' => 1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $today,
            'start_hour' => 24,
            'start_minute' => 5,
            'end_hour' => 27,
            'end_minute' => 30,
        ]);

        $expected = [
            'code' => 500,
            'status' => 'FAIL',
            'message' => '削除できる予約枠がありませんでした',
        ];
        $response = $this->deleteJson("/api/client/hotel/{$this->hotel->id}/reservation_schedule/delete_group/{$reservationBlock->id}");
        $response->assertStatus(200)
            ->assertExactJson($expected);

        // 削除されていない
        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlock->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function delete_group_異常系_未認証()
    {
        $response = $this->deleteJson("/api/client/hotel/{$this->hotel->id}/reservation_schedule/delete_group/1");
        $response->assertStatus(401);
    }

    /** @test */
    public function delete_group_異常系_ログインユーザーに紐づくhotel_idではない()
    {
        $this->actingAs($this->clientApiToken, 'client_api');
        $response = $this->deleteJson("/api/client/hotel/0/reservation_schedule/delete_group/1");
        $response->assertJsonPath('code', 404);
    }

    /** @test */
    public function delete_group_異常系_削除対象の予約枠がホテルに紐付いていない()
    {
        $this->actingAs($this->clientApiToken, 'client_api');
        $now = Carbon::now();
        $today = $now->format('Y-m-d');

        $resavationRepeatGroup = factory(ReservationRepeatGroup::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'start_hour' => 24,
            'start_minute' => 5,
            'end_hour' => 27,
            'end_minute' => 30,
            'repeat_interval_type' => 1,
            'repeat_start_date' => $today,
            'repeat_end_date' => $today,
        ]);
        $reservationBlock = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id + 1,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'reservation_repeat_group_id' => $resavationRepeatGroup->id,
            'room_num' => 1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $today,
            'start_hour' => 24,
            'start_minute' => 5,
            'end_hour' => 27,
            'end_minute' => 30,
        ]);

        $expected = [
            'code' => 500,
            'status' => 'FAIL',
            'message' => '削除できる予約枠がありませんでした',
        ];

        $response = $this->deleteJson("/api/client/hotel/{$this->hotel->id}/reservation_schedule/delete_group/{$reservationBlock->id}");
        $response->assertStatus(200)
            ->assertExactJson($expected);

        // 削除されていない
        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlock->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function close_正常系_複数更新()
    {
        $this->actingAs($this->clientApiToken, 'client_api');
        $now = Carbon::now();
        $today = $now->format('Y-m-d');

        $reservationBlocks = factory(ReservationBlock::class, 3)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'room_num' => 1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $today,
            'start_hour' => 9,
            'start_minute' => 0,
            'end_hour' => 23,
            'end_minute' => 30,
            'is_closed' => 0,
        ]);
        $reservationBlock4 = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'room_num' => 1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $today,
            'start_hour' => 9,
            'start_minute' => 0,
            'end_hour' => 23,
            'end_minute' => 30,
            'is_closed' => 1,
        ]);

        $expected = [
            'code' => 200,
            'status' => 'OK',
            'message' => 'SUCCESS',
            'data' => [
                'reservation_blocks' => [
                    [
                        'reservation_block_id' => $reservationBlocks[0]->id,
                        'reservation_repeat_group_id' => null,
                        'is_available' => 1,
                        'person_capacity' => 2,
                        'reserved_num' => 0,
                        'room_num' => 1,
                        'price' => 1000,
                        'date' => $today,
                        'start_time' => '09:00',
                        'end_time' => '23:30',
                        'reservation_ids' => [],
                        'repeat_interval_type' => 0,
                        'repeat_end_date' => '',
                        'is_closed' => 1
                    ],
                    [
                        'reservation_block_id' => $reservationBlocks[1]->id,
                        'reservation_repeat_group_id' => null,
                        'is_available' => 1,
                        'person_capacity' => 2,
                        'reserved_num' => 0,
                        'room_num' => 1,
                        'price' => 1000,
                        'date' => $today,
                        'start_time' => '09:00',
                        'end_time' => '23:30',
                        'reservation_ids' => [],
                        'repeat_interval_type' => 0,
                        'repeat_end_date' => '',
                        'is_closed' => 1
                    ],
                    [
                        'reservation_block_id' => $reservationBlock4->id,
                        'reservation_repeat_group_id' => null,
                        'is_available' => 1,
                        'person_capacity' => 2,
                        'reserved_num' => 0,
                        'room_num' => 1,
                        'price' => 1000,
                        'date' => $today,
                        'start_time' => '09:00',
                        'end_time' => '23:30',
                        'reservation_ids' => [],
                        'repeat_interval_type' => 0,
                        'repeat_end_date' => '',
                        'is_closed' => 1
                    ],
                ]
            ]
        ];
        $response = $this->postJson(
            "/api/client/hotel/{$this->hotel->id}/reservation_schedule/close",
            [
                'reservation_block_ids' => [$reservationBlocks[0]->id, $reservationBlocks[1]->id, $reservationBlock4->id,],
                'is_closed' => 1,
            ]
        );
        $response->assertStatus(200)
            ->assertExactJson($expected);

        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlocks[0]->id,
            'is_closed' => 1,
        ]);
        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlocks[1]->id,
            'is_closed' => 1,
        ]);
        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlocks[2]->id,
            'is_closed' => 0,
        ]);
        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlock4->id,
            'is_closed' => 1,
        ]);
    }

    /** @test */
    public function close_正常系_単一更新()
    {
        $this->actingAs($this->clientApiToken, 'client_api');
        $now = Carbon::now();
        $today = $now->format('Y-m-d');

        $reservationBlocks = factory(ReservationBlock::class, 3)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'room_num' => 1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $today,
            'start_hour' => 9,
            'start_minute' => 0,
            'end_hour' => 23,
            'end_minute' => 30,
            'is_closed' => 1,
        ]);

        $expected = [
            'code' => 200,
            'status' => 'OK',
            'message' => 'SUCCESS',
            'data' => [
                'reservation_blocks' => [
                    [
                        'reservation_block_id' => $reservationBlocks[2]->id,
                        'reservation_repeat_group_id' => null,
                        'is_available' => 1,
                        'person_capacity' => 2,
                        'reserved_num' => 0,
                        'room_num' => 1,
                        'price' => 1000,
                        'date' => $today,
                        'start_time' => '09:00',
                        'end_time' => '23:30',
                        'reservation_ids' => [],
                        'repeat_interval_type' => 0,
                        'repeat_end_date' => '',
                        'is_closed' => 0
                    ],
                ]
            ]
        ];
        $response = $this->postJson(
            "/api/client/hotel/{$this->hotel->id}/reservation_schedule/close",
            [
                'reservation_block_ids' => [$reservationBlocks[2]->id,],
                'is_closed' => 0,
            ]
        );
        $response->assertStatus(200)
            ->assertExactJson($expected);

        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlocks[0]->id,
            'is_closed' => 1,
        ]);
        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlocks[1]->id,
            'is_closed' => 1,
        ]);
        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlocks[2]->id,
            'is_closed' => 0,
        ]);
    }

    // [$input, $expected]
    public function closeDataProvider()
    {
        $now = Carbon::now();
        $today = $now->format('Y-m-d');
        return [
            '必須エラー' => [
                [],
                [
                    'code' => 1422,
                    'status' => 'FAIL',
                    'message' => [
                        'reservation_block_ids' => ['予約枠IDは必ず指定してください。'],
                        'is_closed' => ['手仕舞いフラグは必ず指定してください。'],
                    ],
                ]
            ],
            '配列内必須エラー' => [
                [
                    'reservation_block_ids' => [],
                    'is_closed' => 1,
                ],
                [
                    'code' => 1422,
                    'status' => 'FAIL',
                    'message' => [
                        'reservation_block_ids' => ['予約枠IDは必ず指定してください。'],
                    ],
                ]
            ],
            '配列エラー' => [
                [
                    'reservation_block_ids' => 1,
                    'is_closed' => 1,
                ],
                [
                    'code' => 1422,
                    'status' => 'FAIL',
                    'message' => [
                        'reservation_block_ids' => ['予約枠IDは配列でなくてはなりません。'],
                    ],
                ]
            ],
            '数値エラー' => [
                [
                    'reservation_block_ids' => [1, 'a'],
                    'is_closed' => 'a',
                ],
                [
                    'code' => 1422,
                    'status' => 'FAIL',
                    'message' => [
                        'reservation_block_ids.1' => ['予約枠IDは整数で指定してください。'],
                        'is_closed' => ['手仕舞いフラグは整数で指定してください。'],
                    ],
                ]
            ],
            '最小値エラー' => [
                [
                    'reservation_block_ids' => [0, 1],
                    'is_closed' => -1,
                ],
                [
                    'code' => 1422,
                    'status' => 'FAIL',
                    'message' => [
                        'reservation_block_ids.0' => ['予約枠IDには、1以上の数字を指定してください。'],
                        'is_closed' => ['手仕舞いフラグには、0以上の数字を指定してください。'],
                    ],
                ]
            ],
            '最大値エラー' => [
                [
                    'reservation_block_ids' => [1],
                    'is_closed' => 2,
                ],
                [
                    'code' => 1422,
                    'status' => 'FAIL',
                    'message' => [
                        'is_closed' => ['手仕舞いフラグには、1以下の数字を指定してください。'],
                    ],
                ]
            ],
        ];
    }

    /**
     * @dataProvider closeDataProvider
     * @test
     */
    public function close_異常系_validationError($input, $expected)
    {
        $this->actingAs($this->clientApiToken, 'client_api');
        $response = $this->postJson(
            "/api/client/hotel/{$this->hotel->id}/reservation_schedule/close",
            $input
        );
        $response->assertStatus(200)
            ->assertExactJson($expected);
    }


    /** @test */
    public function close_異常系_未認証()
    {
        $response = $this->postJson(
            "/api/client/hotel/{$this->hotel->id}/reservation_schedule/close",
            []
        );
        $response->assertStatus(401);
    }

    /** @test */
    public function close_異常系_ログインユーザーに紐づくhotel_idではない()
    {
        $this->actingAs($this->clientApiToken, 'client_api');
        $response = $this->postJson(
            "/api/client/hotel/0/reservation_schedule/close",
            [
                'reservation_block_ids' => [1],
                'is_closed' => 1,
            ]
        );
        $response->assertJsonPath('code', 404);
    }

    /** @test */
    public function close_異常系_手仕舞い対象の予約枠がホテルに紐付いていない()
    {
        $this->actingAs($this->clientApiToken, 'client_api');
        $now = Carbon::now();
        $today = $now->format('Y-m-d');

        $reservationBlocks = factory(ReservationBlock::class, 3)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'room_num' => 1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $today,
            'start_hour' => 9,
            'start_minute' => 0,
            'end_hour' => 23,
            'end_minute' => 30,
            'is_closed' => 0,
        ]);
        $reservationBlock4 = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id + 1,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'room_num' => 1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $today,
            'start_hour' => 9,
            'start_minute' => 0,
            'end_hour' => 23,
            'end_minute' => 30,
            'is_closed' => 1,
        ]);

        $expected = [
            'code' => 500,
            'status' => 'FAIL',
            'message' => '更新できない予約枠が含まれていました',
        ];

        $response = $this->postJson(
            "/api/client/hotel/{$this->hotel->id}/reservation_schedule/close",
            [
                'reservation_block_ids' => [$reservationBlocks[0]->id, $reservationBlocks[1]->id, $reservationBlock4->id,],
                'is_closed' => 1,
            ]
        );
        $response->assertStatus(200)
            ->assertExactJson($expected);

        // 更新されていない
        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlocks[0]->id,
            'is_closed' => 0,
        ]);
        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlocks[1]->id,
            'is_closed' => 0,
        ]);
        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlocks[2]->id,
            'is_closed' => 0,
        ]);
        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlock4->id,
            'is_closed' => 1,
        ]);
    }

    /** @test */
    public function roomNum_正常系_部屋数更新_部屋数指定のみ()
    {
        $this->actingAs($this->clientApiToken, 'client_api');
        $now = Carbon::now();
        $today = $now->format('Y-m-d');
        $tommorow = Carbon::tomorrow()->format('Y-m-d');

        $hotelRoomType = factory(HotelRoomType::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_num' => 5,
        ]);

        $roomNum1 = 2;
        $reservationBlock1 = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $hotelRoomType->id,
            'room_num' => $roomNum1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $today,
            'start_hour' => 9,
            'start_minute' => 0,
            'end_hour' => 10,
            'end_minute' => 30,
            'is_closed' => 0,
        ]);

        $reservationBlock2 = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $hotelRoomType->id,
            'room_num' => $roomNum1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $tommorow,
            'start_hour' => 9,
            'start_minute' => 0,
            'end_hour' => 10,
            'end_minute' => 30,
            'is_closed' => 0,
        ]);

        $roomNum2 = 1;
        $reservationBlock3 = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $hotelRoomType->id,
            'room_num' => $roomNum2,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $today,
            'start_hour' => 16,
            'start_minute' => 0,
            'end_hour' => 23,
            'end_minute' => 30,
            'is_closed' => 0,
        ]);

        $reservationBlock4 = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $hotelRoomType->id,
            'room_num' => $roomNum2,
            'is_available' => 0,
            'reserved_num' => $roomNum2,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $tommorow,
            'start_hour' => 16,
            'start_minute' => 0,
            'end_hour' => 23,
            'end_minute' => 30,
            'is_closed' => 0,
        ]);

        $expected = [
            'code' => 200,
            'status' => 'OK',
            'data' => null,
            'message' => 'SUCCESS',
        ];

        $updateRoomNum = 2;
        $response = $this->postJson(
            "/api/client/hotel/{$this->hotel->id}/reservation_schedule/room_num",
            [
                'num' => $updateRoomNum,
            ]
        );
        $response->assertStatus(200)
            ->assertExactJson($expected);

        // 更新されている
        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlock1->id,
            'date' => $today,
            'room_num' => $roomNum1 + $updateRoomNum,
        ]);
        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlock2->id,
            'date' => $tommorow,
            'room_num' => $roomNum1 + $updateRoomNum,
        ]);
        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlock3->id,
            'date' => $today,
            'room_num' => $roomNum2 + $updateRoomNum,
        ]);
        // room_numがreserved_numより大きくなるので空室ありになる
        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlock4->id,
            'date' => $tommorow,
            'is_available' => 1,
            'room_num' => $roomNum2 + $updateRoomNum,
        ]);
    }

    /** @test */
    public function roomNum_正常系_部屋数更新_部屋タイプIDと部屋数指定のみ()
    {
        $this->actingAs($this->clientApiToken, 'client_api');
        $now = Carbon::now();
        $today = $now->format('Y-m-d');
        $tommorow = Carbon::tomorrow()->format('Y-m-d');

        $hotelRoomTypes = factory(HotelRoomType::class, 2)->create([
            'hotel_id' => $this->hotel->id,
            'room_num' => 5,
        ]);

        $roomNum1 = 2;
        $reservationBlock1 = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $hotelRoomTypes[0]->id,
            'room_num' => $roomNum1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $today,
            'start_hour' => 9,
            'start_minute' => 0,
            'end_hour' => 10,
            'end_minute' => 30,
            'is_closed' => 0,
        ]);

        $reservationBlock2 = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $hotelRoomTypes[0]->id,
            'room_num' => $roomNum1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $tommorow,
            'start_hour' => 9,
            'start_minute' => 0,
            'end_hour' => 10,
            'end_minute' => 30,
            'is_closed' => 0,
        ]);

        $roomNum2 = 1;
        $notUpdateReservationBlock = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $hotelRoomTypes[1]->id,
            'room_num' => $roomNum2,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $today,
            'start_hour' => 16,
            'start_minute' => 0,
            'end_hour' => 23,
            'end_minute' => 30,
            'is_closed' => 0,
        ]);

        $expected = [
            'code' => 200,
            'status' => 'OK',
            'data' => null,
            'message' => 'SUCCESS',
        ];

        $updateRoomNum = 2;
        $response = $this->postJson(
            "/api/client/hotel/{$this->hotel->id}/reservation_schedule/room_num",
            [
                'room_type_id' => $hotelRoomTypes[0]->id,
                'num' => $updateRoomNum,
            ]
        );
        $response->assertStatus(200)
            ->assertExactJson($expected);

        // 更新されている
        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlock1->id,
            'date' => $today,
            'room_num' => $roomNum1 + $updateRoomNum,
        ]);
        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlock2->id,
            'date' => $tommorow,
            'room_num' => $roomNum1 + $updateRoomNum,
        ]);
        // 更新されていない（部屋タイプIDが違う）
        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $notUpdateReservationBlock->id,
            'date' => $today,
            'room_num' => $roomNum2,
        ]);
    }

    /** @test */
    public function roomNum_正常系_部屋数更新_部屋数と日付指定のみ()
    {
        $this->actingAs($this->clientApiToken, 'client_api');
        $now = Carbon::now();
        $today = $now->format('Y-m-d');
        $tommorow = Carbon::tomorrow()->format('Y-m-d');
        $afterTommorow = Carbon::tomorrow()->addDays()->format('Y-m-d');

        $hotelRoomTypes = factory(HotelRoomType::class, 2)->create([
            'hotel_id' => $this->hotel->id,
            'room_num' => 5,
        ]);

        $roomNum1 = 2;
        $reservationBlock1 = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $hotelRoomTypes[0]->id,
            'room_num' => $roomNum1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $today,
            'start_hour' => 9,
            'start_minute' => 0,
            'end_hour' => 10,
            'end_minute' => 30,
            'is_closed' => 0,
        ]);

        $reservationBlock2 = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $hotelRoomTypes[0]->id,
            'room_num' => $roomNum1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $tommorow,
            'start_hour' => 9,
            'start_minute' => 0,
            'end_hour' => 10,
            'end_minute' => 30,
            'is_closed' => 0,
        ]);

        $reservationBlock3 = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $hotelRoomTypes[0]->id,
            'room_num' => $roomNum1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $afterTommorow,
            'start_hour' => 9,
            'start_minute' => 0,
            'end_hour' => 10,
            'end_minute' => 30,
            'is_closed' => 0,
        ]);

        $roomNum2 = 1;
        $reservationBlock4 = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $hotelRoomTypes[1]->id,
            'room_num' => $roomNum2,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $today,
            'start_hour' => 16,
            'start_minute' => 0,
            'end_hour' => 23,
            'end_minute' => 30,
            'is_closed' => 0,
        ]);

        $expected = [
            'code' => 200,
            'status' => 'OK',
            'data' => null,
            'message' => 'SUCCESS',
        ];

        $updateRoomNum = 2;
        $response = $this->postJson(
            "/api/client/hotel/{$this->hotel->id}/reservation_schedule/room_num",
            [
                'num' => $updateRoomNum,
                'date' => [$today, $afterTommorow],
            ]
        );
        $response->assertStatus(200)
            ->assertExactJson($expected);

        // 更新されている
        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlock1->id,
            'date' => $today,
            'room_num' => $roomNum1 + $updateRoomNum,
        ]);
        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlock3->id,
            'date' => $afterTommorow,
            'room_num' => $roomNum1 + $updateRoomNum,
        ]);
        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlock4->id,
            'date' => $today,
            'room_num' => $roomNum2 + $updateRoomNum,
        ]);
        // 更新されていない（日付が範囲外）
        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlock2->id,
            'date' => $tommorow,
            'room_num' => $roomNum1,
        ]);
    }

    /** @test */
    public function roomNum_正常系_部屋数更新_部屋タイプIDと部屋数と日付を指定()
    {
        $this->actingAs($this->clientApiToken, 'client_api');
        $now = Carbon::now();
        $today = $now->format('Y-m-d');
        $tommorow = Carbon::tomorrow()->format('Y-m-d');
        $afterTommorow = Carbon::tomorrow()->addDays()->format('Y-m-d');

        $hotelRoomTypes = factory(HotelRoomType::class, 2)->create([
            'hotel_id' => $this->hotel->id,
            'room_num' => 5,
        ]);

        $roomNum1 = 2;
        $reservationBlock1 = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $hotelRoomTypes[0]->id,
            'room_num' => $roomNum1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $today,
            'start_hour' => 9,
            'start_minute' => 0,
            'end_hour' => 10,
            'end_minute' => 30,
            'is_closed' => 0,
        ]);

        $reservationBlock2 = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $hotelRoomTypes[0]->id,
            'room_num' => $roomNum1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $tommorow,
            'start_hour' => 9,
            'start_minute' => 0,
            'end_hour' => 10,
            'end_minute' => 30,
            'is_closed' => 0,
        ]);

        $reservationBlock3 = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $hotelRoomTypes[0]->id,
            'room_num' => $roomNum1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $afterTommorow,
            'start_hour' => 9,
            'start_minute' => 0,
            'end_hour' => 10,
            'end_minute' => 30,
            'is_closed' => 0,
        ]);

        $roomNum2 = 1;
        $reservationBlock4 = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $hotelRoomTypes[1]->id,
            'room_num' => $roomNum2,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $today,
            'start_hour' => 16,
            'start_minute' => 0,
            'end_hour' => 23,
            'end_minute' => 30,
            'is_closed' => 0,
        ]);

        $expected = [
            'code' => 200,
            'status' => 'OK',
            'data' => null,
            'message' => 'SUCCESS',
        ];

        $updateRoomNum = 2;
        $response = $this->postJson(
            "/api/client/hotel/{$this->hotel->id}/reservation_schedule/room_num",
            [
                'room_type_id' => $hotelRoomTypes[0]->id,
                'num' => $updateRoomNum,
                'date' => [$today, $afterTommorow],
            ]
        );
        $response->assertStatus(200)
            ->assertExactJson($expected);

        // 更新されている
        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlock1->id,
            'date' => $today,
            'room_num' => $roomNum1 + $updateRoomNum,
        ]);
        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlock3->id,
            'date' => $afterTommorow,
            'room_num' => $roomNum1 + $updateRoomNum,
        ]);
        // 更新されていない（日付が範囲外）
        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlock2->id,
            'date' => $tommorow,
            'room_num' => $roomNum1,
        ]);
        // 更新されていない（部屋タイプIDが違う）
        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlock4->id,
            'date' => $today,
            'room_num' => $roomNum2,
        ]);
    }

    /** @test */
    public function roomNum_正常系_部屋数増加_予約枠の部屋数が部屋タイプの部屋数を超えない()
    {
        $this->actingAs($this->clientApiToken, 'client_api');
        $now = Carbon::now();
        $today = $now->format('Y-m-d');
        $tommorow = Carbon::tomorrow()->format('Y-m-d');

        $maxRoomNum = 5;
        $hotelRoomType = factory(HotelRoomType::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_num' => $maxRoomNum,
        ]);

        $roomNum1 = 4;
        $reservationBlock1 = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $hotelRoomType->id,
            'room_num' => $roomNum1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $today,
            'start_hour' => 9,
            'start_minute' => 0,
            'end_hour' => 10,
            'end_minute' => 30,
            'is_closed' => 0,
        ]);

        $roomNum2 = 2;
        $reservationBlock2 = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $hotelRoomType->id,
            'room_num' => $roomNum2,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $tommorow,
            'start_hour' => 9,
            'start_minute' => 0,
            'end_hour' => 10,
            'end_minute' => 30,
            'is_closed' => 0,
        ]);

        $expected = [
            'code' => 200,
            'status' => 'OK',
            'data' => null,
            'message' => 'SUCCESS',
        ];

        $updateRoomNum = 2;
        $response = $this->postJson(
            "/api/client/hotel/{$this->hotel->id}/reservation_schedule/room_num",
            [
                'num' => $updateRoomNum,
            ]
        );
        $response->assertStatus(200)
            ->assertExactJson($expected);

        // 更新されている（$maxRoomNumの値で制限される）
        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlock1->id,
            'date' => $today,
            'room_num' => $maxRoomNum,
        ]);
        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlock2->id,
            'date' => $tommorow,
            'room_num' => $roomNum2 + $updateRoomNum,
        ]);
    }

    /** @test */
    public function roomNum_正常系_部屋数減少_予約枠が満室()
    {
        $this->actingAs($this->clientApiToken, 'client_api');
        $now = Carbon::now();
        $today = $now->format('Y-m-d');
        $tommorow = Carbon::tomorrow()->format('Y-m-d');
        $afterTommorow = Carbon::tomorrow()->addDays()->format('Y-m-d');

        $maxRoomNum = 5;
        $hotelRoomType = factory(HotelRoomType::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_num' => $maxRoomNum,
        ]);

        $roomNum1 = 5;
        $reservationBlock1 = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $hotelRoomType->id,
            'room_num' => $roomNum1,
            'is_available' => 0,
            'reserved_num' => $roomNum1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $today,
            'start_hour' => 9,
            'start_minute' => 0,
            'end_hour' => 10,
            'end_minute' => 30,
            'is_closed' => 0,
        ]);

        $roomNum2 = 3;
        $reservationBlock2 = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $hotelRoomType->id,
            'room_num' => $roomNum2,
            'is_available' => 1,
            'reserved_num' => 1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $tommorow,
            'start_hour' => 9,
            'start_minute' => 0,
            'end_hour' => 10,
            'end_minute' => 30,
            'is_closed' => 0,
        ]);

        $reservationBlock3 = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $hotelRoomType->id,
            'room_num' => $roomNum2,
            'is_available' => 1,
            'reserved_num' => 0,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $afterTommorow,
            'start_hour' => 9,
            'start_minute' => 0,
            'end_hour' => 10,
            'end_minute' => 30,
            'is_closed' => 0,
        ]);

        $reservationBlock4 = factory(ReservationBlock::class)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $hotelRoomType->id,
            'room_num' => 1,
            'is_available' => 1,
            'reserved_num' => 0,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $afterTommorow,
            'start_hour' => 12,
            'start_minute' => 0,
            'end_hour' => 20,
            'end_minute' => 30,
            'is_closed' => 0,
        ]);

        $expected = [
            'code' => 200,
            'status' => 'OK',
            'data' => null,
            'message' => 'SUCCESS',
        ];

        $updateRoomNum = -2;
        $response = $this->postJson(
            "/api/client/hotel/{$this->hotel->id}/reservation_schedule/room_num",
            [
                'num' => $updateRoomNum,
            ]
        );
        $response->assertStatus(200)
            ->assertExactJson($expected);

        // 更新されていない(満室なので減らない)
        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlock1->id,
            'date' => $today,
            'is_available' => 0,
            'room_num' => $reservationBlock1->room_num,
        ]);
        // 更新されている（room_numとreserved_numが同じになるので空室なしになる）
        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlock2->id,
            'date' => $tommorow,
            'is_available' => 0,
            'room_num' => $roomNum2 + $updateRoomNum,
        ]);
        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlock3->id,
            'date' => $afterTommorow,
            'is_available' => 1,
            'room_num' => $roomNum2 + $updateRoomNum,
        ]);
        // 更新されている（room_numが0になるので空室なしになる）
        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlock4->id,
            'date' => $afterTommorow,
            'is_available' => 0,
            'room_num' => 0,
        ]);
    }

    /** @test */
    public function roomNum_異常系_更新対象の予約枠がホテルに紐付いていない()
    {
        $this->actingAs($this->clientApiToken, 'client_api');
        $now = Carbon::now();
        $today = $now->format('Y-m-d');

        $reservationBlocks = factory(ReservationBlock::class, 3)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->hotelRoomTypes[0]->id,
            'room_num' => 1,
            'person_capacity' => 2,
            'price' => 1000,
            'date' => $today,
            'start_hour' => 9,
            'start_minute' => 0,
            'end_hour' => 23,
            'end_minute' => 30,
            'is_closed' => 0,
        ]);

        $expected = [
            'code' => 200,
            'status' => 'OK',
            'data' => null,
            'message' => 'SUCCESS',
        ];

        $response = $this->postJson(
            "/api/client/hotel/{$this->hotel->id}/reservation_schedule/room_num",
            [
                'num' => 1,
            ]
        );
        $response->assertStatus(200)
            ->assertExactJson($expected);

        // 更新されていない
        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlocks[0]->id,
            'is_closed' => 0,
        ]);
        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlocks[1]->id,
            'is_closed' => 0,
        ]);
        $this->assertDatabaseHas('reservation_blocks', [
            'id' => $reservationBlocks[2]->id,
            'is_closed' => 0,
        ]);
    }

    // [$input, $expected]
    public function roomNumValidationDataProvider()
    {
        return [
            '必須エラー' => [
                [],
                [
                    'code' => 1422,
                    'status' => 'FAIL',
                    'message' => [
                        'num' => ['部屋数は必ず指定してください。'],
                    ],
                ],
            ],
            '部屋タイプIDが整数ではない' => [
                [
                    'room_type_id' => 'a',
                    'num' => 1,
                ],
                [
                    'code' => 1422,
                    'status' => 'FAIL',
                    'message' => [
                        'room_type_id' => ['部屋タイプIDは整数で指定してください。'],
                    ],
                ],
            ],
            '部屋タイプIDがnull' => [
                [
                    'room_type_id' => null,
                    'num' => 1,
                ],
                [
                    'code' => 1422,
                    'status' => 'FAIL',
                    'message' => [
                        'room_type_id' => [
                            '部屋タイプIDは整数で指定してください。',
                            '部屋タイプIDには、1以上の数字を指定してください。',
                        ],
                    ],
                ],
            ],
            '部屋タイプIDが1未満' => [
                [
                    'room_type_id' => 0,
                    'num' => 1,
                ],
                [
                    'code' => 1422,
                    'status' => 'FAIL',
                    'message' => [
                        'room_type_id' => ['部屋タイプIDには、1以上の数字を指定してください。'],
                    ],
                ],
            ],
            '部屋数が整数ではない' => [
                [
                    'num' => false,
                ],
                [
                    'code' => 1422,
                    'status' => 'FAIL',
                    'message' => [
                        'num' => ['部屋数は整数で指定してください。'],
                    ],
                ],
            ],
            '部屋数がnull' => [
                [
                    'num' => null,
                ],
                [
                    'code' => 1422,
                    'status' => 'FAIL',
                    'message' => [
                        'num' => ['部屋数は必ず指定してください。'],
                    ],
                ],
            ],
            '部屋数が0' => [
                [
                    'num' => 0,
                ],
                [
                    'code' => 1422,
                    'status' => 'FAIL',
                    'message' => [
                        'num' => ['選択された部屋数は正しくありません。'],
                    ],
                ],
            ],
            '日付が配列ではない' => [
                [
                    'num' => 1,
                    'date' => 'aaa',
                ],
                [
                    'code' => 1422,
                    'status' => 'FAIL',
                    'message' => [
                        'date' => ['日付は配列でなくてはなりません。'],
                    ],
                ],
            ],
            '日付がnull' => [
                [
                    'num' => 1,
                    'date' => null,
                ],
                [
                    'code' => 1422,
                    'status' => 'FAIL',
                    'message' => [
                        'date' => ['日付は配列でなくてはなりません。'],
                    ],
                ],
            ],
            '日付の配列にY-m-d形式以外の値が指定されている' => [
                [
                    'num' => 1,
                    'date' => [
                        '2022/01/01',
                        '20220101',
                        1,
                        true,
                        '2022-01-01',
                        null,
                    ],
                ],
                [
                    'code' => 1422,
                    'status' => 'FAIL',
                    'message' => [
                        'date.0' => ['日付はY-m-d形式で指定してください。'],
                        'date.1' => ['日付はY-m-d形式で指定してください。'],
                        'date.2' => ['日付はY-m-d形式で指定してください。'],
                        'date.3' => ['日付はY-m-d形式で指定してください。'],
                        'date.5' => ['日付はY-m-d形式で指定してください。'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider roomNumValidationDataProvider
     * @test
     */
    public function roomNum_異常系_validationError($input, $expected)
    {
        $this->actingAs($this->clientApiToken, 'client_api');
        $response = $this->postJson(
            "/api/client/hotel/{$this->hotel->id}/reservation_schedule/room_num",
            $input
        );
        $response->assertStatus(200)
            ->assertExactJson($expected);
    }

    /** @test */
    public function roomNum_異常系_未認証()
    {
        $response = $this->postJson(
            "/api/client/hotel/{$this->hotel->id}/reservation_schedule/room_num",
            []
        );
        $response->assertStatus(401);
    }

    /** @test */
    public function roomNum_異常系_ログインユーザーに紐づくhotel_idではない()
    {
        $this->actingAs($this->clientApiToken, 'client_api');
        $response = $this->postJson(
            "/api/client/hotel/0/reservation_schedule/room_num",
            [
                'num' => 1,
            ]
        );
        $expected = [
            'code' => 404,
            'status' => 'FAIL',
            'message' => 'データが存在しません',
        ];
        $response->assertStatus(200)
            ->assertExactJson($expected);
    }
}
