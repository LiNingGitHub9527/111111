<?php

namespace Tests\Feature\User\Other;

use App\Models\BaseCustomerItemValue;
use App\Models\CancelPolicy;
use App\Models\Client;
use App\Models\ClientApiToken;
use App\Models\Form;
use App\Models\Hotel;
use App\Models\HotelHardItem;
use App\Models\HotelRoomType;
use App\Models\HotelRoomTypeImage;
use App\Models\Lp;
use App\Models\OriginalHotelHardItem;
use App\Models\Reservation;
use App\Models\ReservationBlock;
use App\Models\ReservationCancelPolicy;
use App\Models\ReservedReservationBlock;
use App\Services\commonUseCase\Reservation\Other\OtherReserveService;
use App\Services\StripeService;
use App\Support\Api\ApiClient;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;


class BookingOtherControllerTest extends TestCase
{
    use RefreshDatabase;

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
    }

    /** @test */
    public function ajaxGetRoomTypeDetail_正常系_画像やhardItem複数()
    {
        $hotelRoomTypes = factory(HotelRoomType::class, 3)->create([
            'hotel_id' => $this->hotel->id,
        ]);
        $hotelRoomTypeImages = factory(HotelRoomTypeImage::class, 3)->create([
            'room_type_id' => $hotelRoomTypes[0]->id,
        ]);
        $originalHotelHardItems = factory(OriginalHotelHardItem::class, 3)->create([
            'hard_category_id' => 1,
        ]);
        $hotelHardItem1 = factory(HotelHardItem::class)->create([
            'hotel_id' => $this->hotel->id,
            'original_hotel_hard_item_id' => $originalHotelHardItems[0]->id,
        ]);
        $hotelHardItem2 = factory(HotelHardItem::class)->create([
            'hotel_id' => $this->hotel->id,
            'original_hotel_hard_item_id' => $originalHotelHardItems[1]->id,
            'is_all_room' => 0,
            'room_type_ids' => [$hotelRoomTypes[0]->id],
        ]);
        $hotelHardItem3 = factory(HotelHardItem::class)->create([
            'hotel_id' => $this->hotel->id,
            'original_hotel_hard_item_id' => $originalHotelHardItems[2]->id,
            'is_all_room' => 0,
            'room_type_ids' => [$hotelRoomTypes[1]->id],
        ]);

        Storage::fake('s3');

        $expected = [
            'code' => 200,
            'status' => 'OK',
            'message' => 'SUCCESS',
            'data' => [
                'room_type_detail' => [
                    'name' => $hotelRoomTypes[0]->name,
                    'room_num' => $hotelRoomTypes[0]->room_num,
                    'adult_num' => $hotelRoomTypes[0]->adult_num,
                    'child_num' => $hotelRoomTypes[0]->child_num,
                    'room_size' => $hotelRoomTypes[0]->room_size,
                    'images' => $hotelRoomTypeImages->map(function ($item) {
                        return '/storage/' . $item->image;
                    })->toArray(),
                    'hard_items' => [$originalHotelHardItems[0]->name, $originalHotelHardItems[1]->name],
                ]
            ]
        ];
        $response = $this->withSession([
            'booking_other.room_type.token' => ['room_type_id' => $hotelRoomTypes[0]->id],
            'booking_other.base_info.hotel_id' => $this->hotel->id,
        ])
            ->get('/booking/reservation/search/room_type/detail?room_type_token=token');
        $response->assertStatus(200)
            ->assertExactJson($expected);
    }

    /** @test */
    public function ajaxGetRoomTypeDetail_正常系_画像やhardItemが0()
    {
        $hotelRoomTypes = factory(HotelRoomType::class, 3)->create([
            'hotel_id' => $this->hotel->id,
        ]);
        $hotelRoomTypeImages = factory(HotelRoomTypeImage::class, 3)->create([
            'room_type_id' => $hotelRoomTypes[1]->id,
        ]);
        $originalHotelHardItems = factory(OriginalHotelHardItem::class, 3)->create([
            'hard_category_id' => 1,
        ]);
        $hotelHardItem1 = factory(HotelHardItem::class)->create([
            'hotel_id' => $this->hotel->id + 1,
            'original_hotel_hard_item_id' => $originalHotelHardItems[0]->id,
        ]);

        $expected = [
            'code' => 200,
            'status' => 'OK',
            'message' => 'SUCCESS',
            'data' => [
                'room_type_detail' => [
                    'name' => $hotelRoomTypes[0]->name,
                    'room_num' => $hotelRoomTypes[0]->room_num,
                    'adult_num' => $hotelRoomTypes[0]->adult_num,
                    'child_num' => $hotelRoomTypes[0]->child_num,
                    'room_size' => $hotelRoomTypes[0]->room_size,
                    'images' => [],
                    'hard_items' => [],
                ]
            ]
        ];
        $response = $this->withSession([
            'booking_other.room_type.token' => ['room_type_id' => $hotelRoomTypes[0]->id],
            'booking_other.base_info.hotel_id' => $this->hotel->id,
        ])
            ->get('/booking/reservation/search/room_type/detail?room_type_token=token');
        $response->assertStatus(200)
            ->assertExactJson($expected);
    }


    /** @test */
    public function ajaxGetRoomTypeDetail_異常系_セッションに紐づくroom_type_tokenが無い()
    {
        $hotelRoomTypes = factory(HotelRoomType::class, 3)->create([
            'hotel_id' => $this->hotel->id,
        ]);
        $response = $this->withSession([
            'booking_other.room_type.aaa' => ['room_type_id' => $hotelRoomTypes[0]->id],
            'booking_other.base_info.hotel_id' => $this->hotel->id,
        ])
            ->get('/booking/reservation/search/room_type/detail?room_type_token=bbb');
        $response->assertJsonPath('code', 500);
    }

    /** @test */
    public function ajaxGetRoomTypeDetail_異常系_room_type_idに一致するHotelRoomTypeが無い()
    {
        $hotelRoomTypes = factory(HotelRoomType::class, 3)->create([
            'hotel_id' => $this->hotel->id,
        ]);
        $response = $this->withSession([
            'booking_other.room_type.token' => ['room_type_id' => $hotelRoomTypes[0]->id - 1],
            'booking_other.base_info.hotel_id' => $this->hotel->id,
        ])
            ->get('/booking/reservation/search/room_type/detail?room_type_token=token');
        $response->assertJsonPath('code', 500);
    }

    /** @test */
    public function ajaxGetRoomTypeDetail_異常系_セッションに紐づくhotel_idが無い()
    {
        $hotelRoomTypes = factory(HotelRoomType::class, 3)->create([
            'hotel_id' => $this->hotel->id,
        ]);
        $response = $this->withSession([
            'booking_other.room_type.token' => ['room_type_id' => $hotelRoomTypes[0]->id],
            'booking_other.base_info.hotel_idaaa' => $this->hotel->id,
        ])
            ->get('/booking/reservation/search/room_type/detail?room_type_token=token');
        $response->assertJsonPath('code', 500);
    }

    /** @test */
    public function renderSearchPanel_正常系_formのis_room_typeが0()
    {
        $hotel = factory(Hotel::class)->create([
            'client_id' => $this->client->id,
            'business_type' => random_int(3, 4),
        ]);
        $hotelRoomTypes = factory(HotelRoomType::class, 3)->create([
            'hotel_id' => $hotel->id,
        ]);
        $cancelPolicy = factory(CancelPolicy::class)->create([
            'hotel_id' => $hotel->id,
        ]);
        $form = factory(Form::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'public_status' => 1,
            'is_sale_period' => 1,
            'sale_period_start' => Carbon::yesterday()->format('Y-m-d h:i:s'),
            'sale_period_end' => Carbon::tomorrow()->format('Y-m-d h:i:s'),
            'is_room_type' => 0,
            'cancel_policy_id' => $cancelPolicy->id,
        ]);
        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);
        $lpUrlParam = $lp->url_param;
        $response = $this->get("/page/reservation/search_panel?url_param={$lpUrlParam}");
        $response->assertViewHas('lpParam', $lpUrlParam)
            ->assertViewHas('cancelDesc', "チェックイン日の{$cancelPolicy->free_day}日前までは無料でキャンセル可能です。それ以降にキャンセルの場合は、{$cancelPolicy->cancel_charge_rate}%のキャンセル料がかかります。")
            ->assertViewHas('noShowDesc', "無断でキャンセルした場合、{$cancelPolicy->no_show_charge_rate}%のキャンセル料がかかります。");
    }

    /** @test */
    public function renderSearchPanel_正常系_formのis_room_typeが1でroom_type_idsに存在するhotel_idが指定されている()
    {
        [
            'hotel' => $hotel,
            'hotelRoomTypes' => $hotelRoomTypes,
            'cancelPolicy' => $cancelPolicy,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $form = factory(Form::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'public_status' => 1,
            'is_sale_period' => 1,
            'sale_period_start' => Carbon::yesterday()->format('Y-m-d h:i:s'),
            'sale_period_end' => Carbon::tomorrow()->format('Y-m-d h:i:s'),
            'is_room_type' => 1,
            'room_type_ids' => $hotelRoomTypes->pluck('id')->toArray(),
            'cancel_policy_id' => $cancelPolicy->id,
        ]);
        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);
        $lpUrlParam = $lp->url_param;
        $response = $this->get("/page/reservation/search_panel?url_param={$lpUrlParam}");
        $response->assertViewHas('lpParam', $lpUrlParam)
            ->assertViewHas('cancelDesc', "チェックイン日の{$cancelPolicy->free_day}日前までは無料でキャンセル可能です。それ以降にキャンセルの場合は、{$cancelPolicy->cancel_charge_rate}%のキャンセル料がかかります。")
            ->assertViewHas('noShowDesc', "無断でキャンセルした場合、{$cancelPolicy->no_show_charge_rate}%のキャンセル料がかかります。");
    }

    /** @test */
    public function renderSearchPanel_異常系_formのis_room_typeが1でroom_type_idsに存在しないroom_type_idが指定されている()
    {
        [
            'hotel' => $hotel,
            'cancelPolicy' => $cancelPolicy,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $form = factory(Form::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'public_status' => 1,
            'is_sale_period' => 1,
            'sale_period_start' => Carbon::yesterday()->format('Y-m-d h:i:s'),
            'sale_period_end' => Carbon::tomorrow()->format('Y-m-d h:i:s'),
            'is_room_type' => 1,
            'room_type_ids' => [0],
            'cancel_policy_id' => $cancelPolicy->id,
        ]);
        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);
        $lpUrlParam = $lp->url_param;
        $response = $this->get("/page/reservation/search_panel?url_param={$lpUrlParam}");
        $response->assertViewHas('notReserve', 1)
            ->assertViewHas('attentionMessage', '申し訳ありません。アクセスされたURLからは現在ご予約を受け付けておりません。恐れ入りますが、別のページからお手続きくださいませ。');
    }

    /** @test */
    public function renderSearchPanel_異常系_url_paramで指定したLPが存在しない()
    {
        $lpUrlParam = 'testtest';
        $response = $this->get("/page/reservation/search_panel?url_param={$lpUrlParam}");
        $response->assertViewHas('notReserve', 1)
            ->assertViewHas('attentionMessage', '申し訳ありません。アクセスされたURLからは現在ご予約を受け付けておりません。恐れ入りますが、別のページからお手続きくださいませ。');
    }

    /** @test */
    public function renderSearchPanel_異常系_LPに紐づくformが存在しない()
    {
        [
            'hotel' => $hotel,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();
        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => -1,
        ]);
        $lpUrlParam = $lp->url_param;
        $response = $this->get("/page/reservation/search_panel?url_param={$lpUrlParam}");
        $response->assertViewHas('notReserve', 1)
            ->assertViewHas('attentionMessage', '申し訳ありません。アクセスされたURLからは現在ご予約を受け付けておりません。恐れ入りますが、別のページからお手続きくださいませ。');
    }

    /** @test */
    public function renderSearchPanel_異常系_LPに紐づくformのpublic_statusが0()
    {
        [
            'hotel' => $hotel,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

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
        $lpUrlParam = $lp->url_param;
        $response = $this->get("/page/reservation/search_panel?url_param={$lpUrlParam}");
        $response->assertViewHas('notReserve', 1)
            ->assertViewHas('attentionMessage', '申し訳ありません。アクセスされたURLからは現在ご予約を受け付けておりません。恐れ入りますが、別のページからお手続きくださいませ。');
    }

    public function createOutofRangeSalePeriodProvider()
    {
        $yesterday = Carbon::yesterday()->format('Y-m-d h:i:s');
        $tomorrow = Carbon::tomorrow()->format('Y-m-d h:i:s');
        return [
            'ページアクセス日が販売期間より前の日付' => [
                [
                    'is_sale_period' => 1,
                    'sale_period_start' => $tomorrow,
                    'sale_period_end' => $tomorrow,
                ],
                [
                    'notReserve' => 1,
                    'attentionMessage' => '申し訳ありません。アクセスされたURLからは現在利用のご予約を受け付けておりません。恐れ入りますが、別のページからお手続きくださいませ。',
                ],
            ],
            'ページアクセス日が販売期間より後の日付' => [
                [
                    'is_sale_period' => 1,
                    'sale_period_start' => $yesterday,
                    'sale_period_end' => $yesterday,
                ],
                [
                    'notReserve' => 1,
                    'attentionMessage' => '申し訳ありません。アクセスされたURLからは現在利用のご予約を受け付けておりません。恐れ入りますが、別のページからお手続きくださいませ。',
                ],
            ],
        ];
    }

    /**
     * @dataProvider createOutofRangeSalePeriodProvider
     * @test
     */
    public function renderSearchPanel_異常系_販売期間外の日付でアクセス($input, $expect)
    {
        [
            'hotel' => $hotel,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $form = factory(Form::class)->create(array_merge([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'public_status' => 1,
        ], $input));
        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);
        $lpUrlParam = $lp->url_param;
        $response = $this->get("/page/reservation/search_panel?url_param={$lpUrlParam}");
        $response->assertViewHas('notReserve', $expect['notReserve'])
            ->assertViewHas('attentionMessage', $expect['attentionMessage']);
    }

    /** @test */
    public function renderSearchPanel_異常系_部屋タイプが存在しない()
    {
        [
            'hotel' => $hotel,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $form = factory(Form::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'public_status' => 1,
            'is_sale_period' => 1,
            'sale_period_start' => Carbon::yesterday()->format('Y-m-d h:i:s'),
            'sale_period_end' => Carbon::tomorrow()->format('Y-m-d h:i:s'),
            'is_room_type' => 1,
            'room_type_ids' => [0],
        ]);
        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);
        $lpUrlParam = $lp->url_param;
        $response = $this->get("/page/reservation/search_panel?url_param={$lpUrlParam}");
        $response->assertViewHas('notReserve', 1)
            ->assertViewHas('attentionMessage', '申し訳ありません。アクセスされたURLからは現在ご予約を受け付けておりません。恐れ入りますが、別のページからお手続きくださいませ。');
    }

    /** @test */
    public function renderSearchPanel_異常系_キャンセルポリシーがformに紐付いていない()
    {
        [
            'hotel' => $hotel,
            'hotelRoomTypes' => $hotelRoomTypes,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $form = factory(Form::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'public_status' => 1,
            'is_sale_period' => 1,
            'sale_period_start' => Carbon::yesterday()->format('Y-m-d h:i:s'),
            'sale_period_end' => Carbon::tomorrow()->format('Y-m-d h:i:s'),
            'is_room_type' => 0,
        ]);
        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);
        $lpUrlParam = $lp->url_param;
        $response = $this->get("/page/reservation/search_panel?url_param={$lpUrlParam}");
        $response->assertViewHas('notReserve', 1)
            ->assertViewHas('attentionMessage', '申し訳ありません。予期せぬエラーが発生しました。お時間をおいて再度お試しくださいませ。');
    }

    /** @test */
    public function getReservationBlock_正常系_予約可能な予約枠の登録なし()
    {
        [
            'hotel' => $hotel,
            'hotelRoomTypes' => $hotelRoomTypes,
            'cancelPolicy' => $cancelPolicy,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $form = factory(Form::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'public_status' => 1,
            'is_sale_period' => 1,
            'sale_period_start' => Carbon::yesterday()->format('Y-m-d h:i:s'),
            'sale_period_end' => Carbon::tomorrow()->format('Y-m-d h:i:s'),
            'is_room_type' => 1,
            'room_type_ids' => $hotelRoomTypes->pluck('id')->toArray(),
            'cancel_policy_id' => $cancelPolicy->id,
        ]);
        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);

        $roomTypeToken = 'token';
        $now = Carbon::today();
        $query = http_build_query([
            'hotel_id' => $hotel->id,
            'room_type_token' => $roomTypeToken,
            'start_date' => $now->format('Y-m-d'),
            'end_date' => $now->addDays(1)->format('Y-m-d'),
        ]);
        $expected = [
            'code' => 200,
            'status' => 'OK',
            'message' => 'SUCCESS',
            'data' => [
                'reservation_blocks' => []
            ]
        ];

        $response = $this->withSession([
            'booking_other.room_type.' . $roomTypeToken => ['room_type_id' => $hotelRoomTypes[0]->id],
            'booking_other.base_info.hotel_id' => $this->hotel->id,
            'booking_other.base_info.url_param' => $lp->url_param,
        ])->get("/booking/reservation/reservation_block?{$query}");
        $response->assertStatus(200)
            ->assertExactJson($expected);
    }

    /** @test */
    public function getReservationBlock_正常系_予約枠の登録が1件あり()
    {
        [
            'hotel' => $hotel,
            'hotelRoomTypes' => $hotelRoomTypes,
            'cancelPolicy' => $cancelPolicy,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $form = factory(Form::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'public_status' => 1,
            'is_sale_period' => 1,
            'sale_period_start' => Carbon::yesterday()->format('Y-m-d h:i:s'),
            'sale_period_end' => Carbon::tomorrow()->format('Y-m-d h:i:s'),
            'is_room_type' => 1,
            'room_type_ids' => $hotelRoomTypes->pluck('id')->toArray(),
            'cancel_policy_id' => $cancelPolicy->id,
            'is_deadline' => 0,
            'is_special_price' => 0,
        ]);
        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);

        // 予約枠の作成
        $roomTypeId = $hotelRoomTypes[0]->id;
        $tommorow = Carbon::tomorrow()->format('Y-m-d');
        $reservationBlock = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 1,
            'date' => $tommorow,
            'is_closed' => 0,
            'room_num' => 5,
            'reserved_num' => 1,
            'start_hour' => 20,
            'start_minute' => 0,
            'end_hour' => 22,
            'end_minute' => 30,
        ]);

        $roomTypeToken = 'token';
        $query = http_build_query([
            'hotel_id' => $hotel->id,
            'room_type_token' => $roomTypeToken,
            'start_date' => $tommorow,
            'end_date' => Carbon::tomorrow()->addDays(1)->format('Y-m-d'),
        ]);
        $expected = [
            'code' => 200,
            'status' => 'OK',
            'message' => 'SUCCESS',
            'data' => [
                'reservation_blocks' => [
                    "{$tommorow}" => [
                        $this->_formatExpectReservationBlock($reservationBlock),
                    ],
                ],
            ]
        ];
        $structure = [
            'code',
            'status',
            'message',
            'data' => [
                'reservation_blocks' => [
                    '*' => [
                        [
                            'end_time',
                            'is_available',
                            'person_capacity',
                            'price',
                            'reservation_block_token',
                            'start_time',
                            'room_num'
                        ],
                    ],
                ],
            ]
        ];

        $response = $this->withSession([
            'booking_other.room_type.' . $roomTypeToken => ['room_type_id' => $roomTypeId],
            'booking_other.base_info.hotel_id' => $hotel->id,
            'booking_other.base_info.url_param' => $lp->url_param,
        ])->get("/booking/reservation/reservation_block?{$query}");
        $response->assertStatus(200)
            ->assertJson($expected)
            ->assertJsonStructure($structure);
    }

    /** @test */
    public function getReservationBlock_正常系_予約枠の登録が複数件あり()
    {
        [
            'hotel' => $hotel,
            'hotelRoomTypes' => $hotelRoomTypes,
            'cancelPolicy' => $cancelPolicy,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $form = factory(Form::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'public_status' => 1,
            'is_sale_period' => 1,
            'sale_period_start' => Carbon::yesterday()->format('Y-m-d h:i:s'),
            'sale_period_end' => Carbon::tomorrow()->format('Y-m-d h:i:s'),
            'is_room_type' => 1,
            'room_type_ids' => $hotelRoomTypes->pluck('id')->toArray(),
            'cancel_policy_id' => $cancelPolicy->id,
            'is_deadline' => 0,
            'is_special_price' => 0,
        ]);
        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);

        // 予約枠の作成
        $roomTypeId = $hotelRoomTypes[0]->id;
        $tommorow = Carbon::tomorrow()->format('Y-m-d');
        $reservationBlock1 = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 1,
            'date' => $tommorow,
            'start_hour' => 10,
            'start_minute' => 0,
            'end_hour' => 11,
            'end_minute' => 30,
            'is_closed' => 0,
            'room_num' => 5,
            'reserved_num' => 1
        ]);
        $reservationBlock2 = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 1,
            'date' => $tommorow,
            'start_hour' => 20,
            'start_minute' => 0,
            'end_hour' => 22,
            'end_minute' => 30,
            'is_closed' => 0,
            'room_num' => 5,
            'reserved_num' => 1
        ]);
        $afterTommorow = Carbon::tomorrow()->addDays(1)->format('Y-m-d');
        $reservationBlock3 = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 1,
            'date' => $afterTommorow,
            'start_hour' => 23,
            'start_minute' => 30,
            'end_hour' => 27,
            'end_minute' => 00,
            'is_closed' => 0,
            'room_num' => 5,
            'reserved_num' => 1
        ]);
        $reservationBlock4 = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 0,
            'date' => $afterTommorow,
            'start_hour' => 0,
            'start_minute' => 1,
            'end_hour' => 27,
            'end_minute' => 0,
            'is_closed' => 0,
            'room_num' => 4,
            'reserved_num' => 4
        ]);
        $today = Carbon::today()->format('Y-m-d');
        $reservationBlock5 = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 0,
            'date' => $today,
            'start_hour' => 24,
            'start_minute' => 1,
            'end_hour' => 27,
            'end_minute' => 0,
            'is_closed' => 0,
            'room_num' => 5,
            'reserved_num' => 5
        ]);
        // 取得されていはいけない(price=0)予約枠
        $zeroPriceReservationBlock = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 0,
            'date' => $afterTommorow,
            'start_hour' => 22,
            'start_minute' => 00,
            'end_hour' => 23,
            'end_minute' => 00,
            'price' => 0,
            'is_closed' => 0,
        ]);
        // 手仕舞い済み(is_closed=1)予約枠
        $closedReservationBlock = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 0,
            'date' => $afterTommorow,
            'start_hour' => 10,
            'start_minute' => 00,
            'end_hour' => 27,
            'end_minute' => 00,
            'is_closed' => 1
        ]);
        // 受付開始時刻前の予約枠
        $beforeCheckinStartReservationBlock = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 0,
            'date' => $afterTommorow,
            'start_hour' => 00,
            'start_minute' => 00,
            'end_hour' => 27,
            'end_minute' => 00,
            'is_closed' => 0
        ]);
        // 最終退館時刻を超えた予約枠
        $beyondCheckoutEndReservationBlock = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 0,
            'date' => $afterTommorow,
            'start_hour' => 15,
            'start_minute' => 00,
            'end_hour' => 27,
            'end_minute' => 1,
            'is_closed' => 0
        ]);
        // 当日の予約枠で、現在時刻の15分後より前に開始する予約枠
        $nowMinutesLater = Carbon::now()->addMinutes(14);
        $beforeMinutesLaterReservationBlock = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 0,
            'date' => $nowMinutesLater->format('Y-m-d'),
            'start_hour' => $nowMinutesLater->format('H'),
            'start_minute' => $nowMinutesLater->format('i'),
            'end_hour' => 27,
            'end_minute' => 00,
            'is_closed' => 0
        ]);

        $roomTypeToken = 'token';
        $query = http_build_query([
            'hotel_id' => $hotel->id,
            'room_type_token' => $roomTypeToken,
            'start_date' => $today,
            'end_date' => Carbon::tomorrow()->addDays(1)->format('Y-m-d'),
        ]);
        $expected = [
            'code' => 200,
            'status' => 'OK',
            'message' => 'SUCCESS',
            'data' => [
                'reservation_blocks' => [
                    "{$today}" => [
                        $this->_formatExpectReservationBlock($reservationBlock5),
                    ],
                    "{$tommorow}" => [
                        $this->_formatExpectReservationBlock($reservationBlock1),
                        $this->_formatExpectReservationBlock($reservationBlock2),
                    ],
                    "{$afterTommorow}" => [
                        $this->_formatExpectReservationBlock($reservationBlock4),
                        $this->_formatExpectReservationBlock($reservationBlock3),
                    ]
                ],
            ]
        ];
        $structure = [
            'code',
            'status',
            'message',
            'data' => [
                'reservation_blocks' => [
                    '*' => [
                        [
                            'end_time',
                            'is_available',
                            'person_capacity',
                            'price',
                            'reservation_block_token',
                            'start_time',
                            'room_num'
                        ],
                    ],
                ],
            ]
        ];

        $response = $this->withSession([
            'booking_other.room_type.' . $roomTypeToken => ['room_type_id' => $roomTypeId],
            'booking_other.base_info.hotel_id' => $hotel->id,
            'booking_other.base_info.url_param' => $lp->url_param,
        ])->get("/booking/reservation/reservation_block?{$query}");
        $response->assertStatus(200)
            ->assertJson($expected)
            ->assertJsonStructure($structure);
    }

    /** @test */
    public function getReservationBlock_正常系_有効な予約可能期間ではない()
    {
        [
            'hotel' => $hotel,
            'hotelRoomTypes' => $hotelRoomTypes,
            'cancelPolicy' => $cancelPolicy,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        // 予約可能期間は明日から
        $tomorrow = Carbon::tomorrow();
        $form = factory(Form::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'public_status' => 1,
            'is_deadline' => 1,
            'deadline_start' => $tomorrow->format('Y-m-d h:i:s'),
            'deadline_end' => $tomorrow->format('Y-m-d h:i:s'),
            'is_room_type' => 1,
            'room_type_ids' => $hotelRoomTypes->pluck('id')->toArray(),
            'is_special_price' => 0,
        ]);
        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);

        $roomTypeToken = 'token';
        $today = Carbon::today();
        $query = http_build_query([
            'hotel_id' => $hotel->id,
            'room_type_token' => $roomTypeToken,
            'start_date' => $today->format('Y-m-d'),
            'end_date' => $tomorrow->format('Y-m-d'),
        ]);
        $expected = [
            'code' => 200,
            'status' => 'OK',
            'message' => 'SUCCESS',
            'data' => [
                'reservation_blocks' => []
            ],
        ];

        $response = $this->withSession([
            'booking_other.base_info.url_param' => $lp->url_param,
        ])->get("/booking/reservation/reservation_block?{$query}");
        $response->assertStatus(200)
            ->assertExactJson($expected);
    }

    /** @test */
    public function getReservationBlock_正常系_特別価格手動入力あり()
    {
        [
            'hotel' => $hotel,
            'hotelRoomTypes' => $hotelRoomTypes,
            'cancelPolicy' => $cancelPolicy,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $handInputPrice = 10000;
        $form = $this->_特別価格手動入力ありのフォーム作成(
            $hotel,
            $hotelRoomTypes,
            $cancelPolicy,
            $handInputPrice
        );

        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);

        // 予約枠の作成
        $roomTypeId = $hotelRoomTypes[0]->id;
        $tommorow = Carbon::tomorrow()->format('Y-m-d');
        $reservationBlock1 = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 1,
            'date' => $tommorow,
            'start_hour' => 10,
            'start_minute' => 0,
            'end_hour' => 11,
            'end_minute' => 30,
            'price' => 1000,
            'is_closed' => 0,
        ]);
        $reservationBlock2 = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 1,
            'date' => $tommorow,
            'start_hour' => 20,
            'start_minute' => 0,
            'end_hour' => 22,
            'end_minute' => 30,
            'price' => 2000,
            'is_closed' => 0,
        ]);
        $afterTommorow = Carbon::tomorrow()->addDays(1)->format('Y-m-d');
        $reservationBlock3 = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 1,
            'date' => $afterTommorow,
            'start_hour' => 23,
            'start_minute' => 30,
            'end_hour' => 27,
            'end_minute' => 00,
            'price' => 500,
            'is_closed' => 0,
        ]);

        $roomTypeToken = 'token';
        $query = http_build_query([
            'hotel_id' => $hotel->id,
            'room_type_token' => $roomTypeToken,
            'start_date' => $tommorow,
            'end_date' => $afterTommorow,
        ]);
        $price = ['price' => $handInputPrice];
        $expected = [
            'code' => 200,
            'status' => 'OK',
            'message' => 'SUCCESS',
            'data' => [
                'reservation_blocks' => [
                    "{$tommorow}" => [
                        $this->_formatExpectReservationBlock($reservationBlock1, $price),
                        $this->_formatExpectReservationBlock($reservationBlock2, $price),
                    ],
                    "{$afterTommorow}" => [
                        $this->_formatExpectReservationBlock($reservationBlock3, $price),
                    ],
                ],
            ]
        ];
        $structure = [
            'code',
            'status',
            'message',
            'data' => [
                'reservation_blocks' => [
                    '*' => [
                        [
                            'end_time',
                            'is_available',
                            'person_capacity',
                            'price',
                            'reservation_block_token',
                            'start_time',
                            'room_num'
                        ],
                    ],
                ],
            ]
        ];

        $response = $this->withSession([
            'booking_other.room_type.' . $roomTypeToken => ['room_type_id' => $roomTypeId],
            'booking_other.base_info.hotel_id' => $hotel->id,
            'booking_other.base_info.url_param' => $lp->url_param,
        ])->get("/booking/reservation/reservation_block?{$query}");
        $response->assertStatus(200)
            ->assertJson($expected)
            ->assertJsonStructure($structure);
    }

    // [$input, $expected]
    public function createAllRoomPriceSettingProvider()
    {
        return [
            '10%UP' => [
                [
                    'room_prices' => [1000, 2000, 500],
                    'all_room_price_setting' => [
                        'num' =>  10,
                        'unit' =>  0,
                        'up_off' =>  1,
                    ],
                ],
                [
                    'room_prices' => [1100, 2200, 550],
                ]
            ],
            '25%OFF' => [
                [
                    'room_prices' => [1000, 2000, 500],
                    'all_room_price_setting' => [
                        'num' =>  25,
                        'unit' =>  0,
                        'up_off' =>  2,
                    ],
                ],
                [
                    'room_prices' => [750, 1500, 375],
                ]
            ],
            '1000円UP' => [
                [
                    'room_prices' => [1000, 2000, 500],
                    'all_room_price_setting' => [
                        'num' =>  1000,
                        'unit' =>  1,
                        'up_off' =>  1,
                    ],
                ],
                [
                    'room_prices' => [2000, 3000, 1500],
                ]
            ],
            '300円OFF' => [
                [
                    'room_prices' => [1000, 2000, 500],
                    'all_room_price_setting' => [
                        'num' =>  300,
                        'unit' =>  1,
                        'up_off' =>  2,
                    ],
                ],
                [
                    'room_prices' => [700, 1700, 200],
                ]
            ],
            '3番目の部屋タイプの料金が計算後に0円以下になる' => [
                [
                    'room_prices' => [1800, 500, 3000],
                    'all_room_price_setting' => [
                        // 1000円OFF
                        'num' =>  1000,
                        'unit' =>  1,
                        'up_off' =>  2,
                    ],
                    'is_minus' => true,
                ],
                [
                    'room_prices' => [800, -500, 2000],
                ]
            ],
        ];
    }

    /**
     * @dataProvider createAllRoomPriceSettingProvider
     * @test
     */
    public function getReservationBlock_正常系_特別価格手動入力なし_全ての部屋タイプの金額を一括登録あり($input, $expect)
    {
        [
            'hotel' => $hotel,
            'hotelRoomTypes' => $hotelRoomTypes,
            'cancelPolicy' => $cancelPolicy,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $form = factory(Form::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'public_status' => 1,
            'is_sale_period' => 1,
            'sale_period_start' => Carbon::yesterday()->format('Y-m-d h:i:s'),
            'sale_period_end' => Carbon::tomorrow()->format('Y-m-d h:i:s'),
            'is_room_type' => 0,
            'cancel_policy_id' => $cancelPolicy->id,
            'is_deadline' => 0,
            'is_special_price' => 1,
            // 特別価格の金額を手動で入力しますか？：いいえ
            'is_hand_input' => 0,
            // 全ての部屋タイプの金額を一括登録しますか？：はい
            'is_all_room_price_setting' => 1,
            'all_room_price_setting' => $input['all_room_price_setting'],
        ]);
        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);

        // 予約枠の作成
        $roomTypeId = $hotelRoomTypes[0]->id;
        $tommorow = Carbon::tomorrow()->format('Y-m-d');
        $reservationBlock1 = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 1,
            'date' => $tommorow,
            'start_hour' => 10,
            'start_minute' => 0,
            'end_hour' => 11,
            'end_minute' => 30,
            'price' => $input['room_prices'][0],
            'is_closed' => 0,
        ]);
        $reservationBlock2 = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 1,
            'date' => $tommorow,
            'start_hour' => 20,
            'start_minute' => 0,
            'end_hour' => 22,
            'end_minute' => 30,
            'price' => $input['room_prices'][1],
            'is_closed' => 0,
        ]);
        $afterTommorow = Carbon::tomorrow()->addDays(1)->format('Y-m-d');
        $reservationBlock3 = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 1,
            'date' => $afterTommorow,
            'start_hour' => 23,
            'start_minute' => 30,
            'end_hour' => 27,
            'end_minute' => 00,
            'price' => $input['room_prices'][2],
            'is_closed' => 0,
        ]);

        $roomTypeToken = 'token';
        $query = http_build_query([
            'hotel_id' => $hotel->id,
            'room_type_token' => $roomTypeToken,
            'start_date' => $tommorow,
            'end_date' => $afterTommorow,
        ]);
        $expected = [
            'code' => 200,
            'status' => 'OK',
            'message' => 'SUCCESS',
            'data' => [
                'reservation_blocks' => [
                    "{$tommorow}" => [
                        $this->_formatExpectReservationBlock($reservationBlock1, [
                            'price' => $expect['room_prices'][0],
                        ]),
                    ],
                    "{$afterTommorow}" => [
                        $this->_formatExpectReservationBlock($reservationBlock3, [
                            'price' => $expect['room_prices'][2],
                        ]),
                    ],
                ],
            ]
        ];
        // priceが0円以下にならない場合
        $isMinus = $input['is_minus'] ?? false;
        if (!$isMinus) {
            $block = $this->_formatExpectReservationBlock($reservationBlock2, [
                'price' => $expect['room_prices'][1],
            ]);
            $expected['data']['reservation_blocks'][$tommorow][] = $block;
        }

        $structure = [
            'code',
            'status',
            'message',
            'data' => [
                'reservation_blocks' => [
                    '*' => [
                        [
                            'end_time',
                            'is_available',
                            'person_capacity',
                            'price',
                            'reservation_block_token',
                            'start_time',
                            'room_num'
                        ],
                    ],
                ],
            ]
        ];

        $response = $this->withSession([
            'booking_other.room_type.' . $roomTypeToken => ['room_type_id' => $roomTypeId],
            'booking_other.base_info.hotel_id' => $hotel->id,
            'booking_other.base_info.url_param' => $lp->url_param,
        ])->get("/booking/reservation/reservation_block?{$query}");
        $response->assertStatus(200)
            ->assertJson($expected)
            ->assertJsonStructure($structure);
    }

    // [$input, $expected]
    public function createSpecialRoomPriceSettingsProvider()
    {
        return [
            '10%UP' => [
                [
                    'room_prices' => [1000, 2000, 600],
                    'special_room_price_settings' => [
                        [
                            'num' =>  10,
                            'unit' =>  0,
                            'up_off' =>  1,
                        ],
                    ],
                ],
                [
                    'room_prices' => [1100, 2200, 660],
                ],
            ],
            '25%OFF' => [
                [
                    'room_prices' => [1000, 2000, 500],
                    'special_room_price_settings' => [
                        [
                            'num' =>  25,
                            'unit' =>  0,
                            'up_off' =>  2,
                        ],
                    ],
                ],
                [
                    'room_prices' => [750, 1500, 375],
                ]
            ],
            '1000円UP' => [
                [
                    'room_prices' => [1000, 2000, 500],
                    'special_room_price_settings' => [
                        [
                            'num' =>  1000,
                            'unit' =>  1,
                            'up_off' =>  1,
                        ],
                    ],
                ],
                [
                    'room_prices' => [2000, 3000, 1500],
                ]
            ],
            '300円OFF' => [
                [
                    'room_prices' => [1000, 2000, 500],
                    'special_room_price_settings' => [
                        [
                            'num' =>  300,
                            'unit' =>  1,
                            'up_off' =>  2,
                        ],
                    ],
                ],
                [
                    'room_prices' => [700, 1700, 200],
                ]
            ],
            '3番目の部屋タイプの料金が計算後に0円以下になる' => [
                [
                    'room_prices' => [1800, 500, 3000],
                    'special_room_price_settings' => [
                        [
                            // 1000円OFF
                            'num' =>  1000,
                            'unit' =>  1,
                            'up_off' =>  2,
                        ],
                    ],
                    'is_minus' => true,
                ],
                [
                    'room_prices' => [800, -500, 2000],
                ]
            ],
        ];
    }

    /**
     * @dataProvider createSpecialRoomPriceSettingsProvider
     * @test
     */
    public function getReservationBlock_正常系_特別価格手動入力なし_全ての部屋タイプの金額を一括登録なし($input, $expect)
    {
        [
            'hotel' => $hotel,
            'hotelRoomTypes' => $hotelRoomTypes,
            'cancelPolicy' => $cancelPolicy,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $specialRoomPriceSettings = $input['special_room_price_settings'];
        $specialRoomPriceSettings[0]['room_type_id'] = $hotelRoomTypes[0]->id;
        $form = factory(Form::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'public_status' => 1,
            'is_sale_period' => 1,
            'sale_period_start' => Carbon::yesterday()->format('Y-m-d h:i:s'),
            'sale_period_end' => Carbon::tomorrow()->format('Y-m-d h:i:s'),
            'is_room_type' => 0,
            'cancel_policy_id' => $cancelPolicy->id,
            'is_deadline' => 0,
            'is_special_price' => 1,
            // 特別価格の金額を手動で入力しますか？：いいえ
            'is_hand_input' => 0,
            // 全ての部屋タイプの金額を一括登録しますか？：いいえ
            'is_all_room_price_setting' => 0,
            'special_room_price_settings' => $specialRoomPriceSettings,
        ]);
        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);

        // 予約枠の作成
        $roomTypeId = $hotelRoomTypes[0]->id;
        $tommorow = Carbon::tomorrow()->format('Y-m-d');
        $reservationBlock1 = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 1,
            'date' => $tommorow,
            'start_hour' => 10,
            'start_minute' => 0,
            'end_hour' => 11,
            'end_minute' => 30,
            'price' => $input['room_prices'][0],
            'is_closed' => 0,
        ]);
        $reservationBlock2 = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 1,
            'date' => $tommorow,
            'start_hour' => 20,
            'start_minute' => 0,
            'end_hour' => 22,
            'end_minute' => 30,
            'price' => $input['room_prices'][1],
            'is_closed' => 0,
        ]);
        $afterTommorow = Carbon::tomorrow()->addDays(1)->format('Y-m-d');
        $reservationBlock3 = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 1,
            'date' => $afterTommorow,
            'start_hour' => 23,
            'start_minute' => 30,
            'end_hour' => 27,
            'end_minute' => 00,
            'price' => $input['room_prices'][2],
            'is_closed' => 0,
        ]);

        $roomTypeToken = 'token';
        $query = http_build_query([
            'hotel_id' => $hotel->id,
            'room_type_token' => $roomTypeToken,
            'start_date' => $tommorow,
            'end_date' => $afterTommorow,
        ]);
        $expected = [
            'code' => 200,
            'status' => 'OK',
            'message' => 'SUCCESS',
            'data' => [
                'reservation_blocks' => [
                    "{$tommorow}" => [
                        $this->_formatExpectReservationBlock($reservationBlock1, [
                            'price' => $expect['room_prices'][0],
                        ]),
                    ],
                    "{$afterTommorow}" => [
                        $this->_formatExpectReservationBlock($reservationBlock3, [
                            'price' => $expect['room_prices'][2],
                        ]),
                    ],
                ],
            ]
        ];
        // priceが0円以下にならない場合
        $isMinus = $input['is_minus'] ?? false;
        if (!$isMinus) {
            $block = $this->_formatExpectReservationBlock($reservationBlock2, [
                'price' => $expect['room_prices'][1],
            ]);
            $expected['data']['reservation_blocks'][$tommorow][] = $block;
        }

        $structure = [
            'code',
            'status',
            'message',
            'data' => [
                'reservation_blocks' => [
                    '*' => [
                        [
                            'end_time',
                            'is_available',
                            'person_capacity',
                            'price',
                            'reservation_block_token',
                            'start_time',
                            'room_num'
                        ],
                    ],
                ],
            ]
        ];

        $response = $this->withSession([
            'booking_other.room_type.' . $roomTypeToken => ['room_type_id' => $roomTypeId],
            'booking_other.base_info.hotel_id' => $hotel->id,
            'booking_other.base_info.url_param' => $lp->url_param,
        ])->get("/booking/reservation/reservation_block?{$query}");
        $response->assertStatus(200)
            ->assertJson($expected)
            ->assertJsonStructure($structure);
    }

    // [$input, $expected]
    public function createValidationErrorProvider()
    {
        $today = Carbon::today()->format('Y-m-d');
        $yesterday = Carbon::yesterday()->format('Y-m-d');
        return [
            '必須エラー' => [
                [],
                [
                    'code' => 1422,
                    'status' => 'FAIL',
                    'message' => [
                        'hotel_id' => ['施設IDは必ず指定してください。'],
                        'room_type_token' => ['部屋タイプトークンは必ず指定してください。'],
                        'start_date' => ['予約枠取得の開始日は必ず指定してください。'],
                        'end_date' => ['予約枠取得の終了日は必ず指定してください。'],
                    ],
                ]
            ],
            '型エラー' => [
                [
                    'hotel_id' => 'a',
                    'room_type_token' => -1,
                    'start_date' => true,
                    'end_date' => 0,
                    'is_available' => 'a',
                ],
                [
                    'code' => 1422,
                    'status' => 'FAIL',
                    'message' => [
                        'hotel_id' => ['施設IDは整数で指定してください。'],
                        'start_date' => ['予約枠取得の開始日には、現在日以降の日付を指定してください。'],
                        'end_date' => ['予約枠取得の終了日には、予約枠取得の開始日より後の日付を指定してください。'],
                        'is_available' => ['選択された空室チェックは正しくありません。'],
                    ],
                ]
            ],
            'start_dateに現在日より前の日付が指定されているエラー' => [
                [
                    'hotel_id' => 2,
                    'room_type_token' => 'token',
                    'start_date' => $yesterday,
                    'end_date' => $today,
                ],
                [
                    'code' => 1422,
                    'status' => 'FAIL',
                    'message' => [
                        'start_date' => ['予約枠取得の開始日には、現在日以降の日付を指定してください。'],
                    ],
                ]
            ],
            'end_dateにstart_dateより後の日付が指定されていないエラー' => [
                [
                    'hotel_id' => 2,
                    'room_type_token' => 'token',
                    'start_date' => $today,
                    'end_date' => $today,
                ],
                [
                    'code' => 1422,
                    'status' => 'FAIL',
                    'message' => [
                        'end_date' => ['予約枠取得の終了日には、予約枠取得の開始日より後の日付を指定してください。'],
                    ],
                ]
            ],
        ];
    }

    /**
     * @dataProvider createValidationErrorProvider
     * @test
     */
    public function getReservationBlock_異常系_validationError($input, $expected)
    {
        $query = http_build_query($input);
        $response = $this->get("/booking/reservation/reservation_block?{$query}");
        $response->assertStatus(200)
            ->assertExactJson($expected);
    }

    /** @test */
    public function getReservationBlock_異常系_セッションが存在しない()
    {
        [
            'hotel' => $hotel,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $now = Carbon::today();
        $query = http_build_query([
            'hotel_id' => $hotel->id,
            'room_type_token' => 'token',
            'start_date' => $now->format('Y-m-d'),
            'end_date' => $now->addDays(1)->format('Y-m-d'),
        ]);
        $expected = [
            'code' => 400,
            'status' => 'FAIL',
            'message' => '申し訳ありません。予期せぬエラーが発生しました。お時間をおいて再度お試しくださいませ。',
        ];

        $response = $this->get("/booking/reservation/reservation_block?{$query}");
        $response->assertStatus(200)
            ->assertExactJson($expected);
    }

    /** @test */
    public function getReservationBlock_異常系_セッションに保持しているurl_paramのLPが存在しない()
    {
        [
            'hotel' => $hotel,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $now = Carbon::today();
        $query = http_build_query([
            'hotel_id' => $hotel->id,
            'room_type_token' => 'token',
            'start_date' => $now->format('Y-m-d'),
            'end_date' => $now->addDays(1)->format('Y-m-d'),
        ]);
        $expected = [
            'code' => 404,
            'status' => 'FAIL',
            'message' => '申し訳ありません。アクセスされたURLからは現在ご予約を受け付けておりません。恐れ入りますが、別のページからお手続きくださいませ。',
        ];

        $response = $this->withSession([
            'booking_other.base_info.url_param' =>  'test_illegal',
        ])->get("/booking/reservation/reservation_block?{$query}");
        $response->assertJsonPath('code', 404)
            ->assertExactJson($expected);
    }

    /** @test */
    public function getReservationBlock_異常系_LPに紐づくformが存在しない()
    {
        [
            'hotel' => $hotel,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => -1,
        ]);

        $now = Carbon::today();
        $query = http_build_query([
            'hotel_id' => $hotel->id,
            'room_type_token' => 'token',
            'start_date' => $now->format('Y-m-d'),
            'end_date' => $now->addDays(1)->format('Y-m-d'),
        ]);
        $expected = [
            'code' => 404,
            'status' => 'FAIL',
            'message' => '申し訳ありません。アクセスされたURLからは現在ご予約を受け付けておりません。恐れ入りますが、別のページからお手続きくださいませ。',
        ];

        $response = $this->withSession([
            'booking_other.base_info.url_param' => $lp->url_param,
        ])->get("/booking/reservation/reservation_block?{$query}");
        $response->assertJsonPath('code', 404)
            ->assertExactJson($expected);
    }

    /** @test */
    public function getReservationBlock_異常系_LPに紐づくformのpublic_statusが0()
    {
        [
            'hotel' => $hotel,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

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

        $now = Carbon::today();
        $query = http_build_query([
            'hotel_id' => $hotel->id,
            'room_type_token' => 'token',
            'start_date' => $now->format('Y-m-d'),
            'end_date' => $now->addDays(1)->format('Y-m-d'),
        ]);
        $expected = [
            'code' => 404,
            'status' => 'FAIL',
            'message' => '申し訳ありません。アクセスされたURLからは現在ご予約を受け付けておりません。恐れ入りますが、別のページからお手続きくださいませ。',
        ];

        $response = $this->withSession([
            'booking_other.base_info.url_param' => $lp->url_param,
        ])->get("/booking/reservation/reservation_block?{$query}");
        $response->assertJsonPath('code', 404)
            ->assertExactJson($expected);
    }

    /** @test */
    public function getReservationBlock_異常系_room_type_tokenと紐づく部屋タイプがセッションに存在しない()
    {
        [
            'hotel' => $hotel,
            'hotelRoomTypes' => $hotelRoomTypes,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $form = factory(Form::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'public_status' => 1,
            'is_deadline' => 0,
            'is_room_type' => 1,
            'room_type_ids' => $hotelRoomTypes->pluck('id')->toArray(),
            'is_special_price' => 0,
        ]);
        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);

        $roomTypeToken = 'token';
        $today = Carbon::today();
        $query = http_build_query([
            'hotel_id' => $hotel->id,
            'room_type_token' => $roomTypeToken,
            'start_date' => $today->format('Y-m-d'),
            'end_date' => $today->addDays(1)->format('Y-m-d'),
        ]);
        $expected = [
            'code' => 404,
            'status' => 'FAIL',
            'message' => '申し訳ありません。指定された部屋タイプは有効ではございません。恐れ入りますが、別のページからお手続きくださいませ。',
        ];

        $response = $this->withSession([
            'booking_other.base_info.url_param' => $lp->url_param,
        ])->get("/booking/reservation/reservation_block?{$query}");
        $response->assertJsonPath('code', 404)
            ->assertExactJson($expected);
    }

    /** @test */
    public function getReservationBlock_異常系_room_type_tokenと紐づく部屋タイプがformで限定された部屋タイプ一覧に存在しない()
    {
        [
            'hotel' => $hotel,
            'hotelRoomTypes' => $hotelRoomTypes,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        // forms.room_type_idsに存在しない部屋タイプ
        $excludeHotelRoomType = factory(HotelRoomType::class)->create([
            'hotel_id' => $hotel->id,
        ]);
        $form = factory(Form::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'public_status' => 1,
            'is_deadline' => 0,
            'is_room_type' => 1,
            'room_type_ids' => $hotelRoomTypes->pluck('id')->toArray(),
            'is_special_price' => 0,
        ]);
        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);

        $roomTypeId = $excludeHotelRoomType->id;
        $roomTypeToken = 'token';
        $today = Carbon::today();
        $query = http_build_query([
            'hotel_id' => $hotel->id,
            'room_type_token' => $roomTypeToken,
            'start_date' => $today->format('Y-m-d'),
            'end_date' => $today->addDays(1)->format('Y-m-d'),
        ]);
        $expected = [
            'code' => 404,
            'status' => 'FAIL',
            'message' => '申し訳ありません。指定された部屋タイプは有効ではございません。恐れ入りますが、別のページからお手続きくださいませ。',
        ];

        $response = $this->withSession([
            'booking_other.room_type.' . $roomTypeToken => ['room_type_id' => $roomTypeId],
            'booking_other.base_info.url_param' => $lp->url_param,
        ])->get("/booking/reservation/reservation_block?{$query}");
        $response->assertJsonPath('code', 404)
            ->assertExactJson($expected);
    }

    /** @test */
    public function inputBookingInfo_正常系_特別価格手動入力あり()
    {
        [
            'hotel' => $hotel,
            'hotelRoomTypes' => $hotelRoomTypes,
            'cancelPolicy' => $cancelPolicy,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $handInputPrice = 10000;
        $form = $this->_特別価格手動入力ありのフォーム作成(
            $hotel,
            $hotelRoomTypes,
            $cancelPolicy,
            $handInputPrice
        );

        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);

        // 予約枠の作成
        $roomTypeId = $hotelRoomTypes[0]->id;
        $tommorow = Carbon::tomorrow()->format('Y-m-d');
        $reservationBlock = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 1,
            'date' => $tommorow,
            'price' => 1000,
            'room_num' => 5,
            'reserved_num' => 3
        ]);

        $reserveBlockId = $reservationBlock->id;
        $reserveBlockToken = 'block_token';

        // ApiClientをモックに差し替え
        $this->_createMockApiClientDoGetRequest();

        $response = $this->withSession([
            'booking_other.reservation_block.' . $reserveBlockToken . '.reservation_block_id' => $reserveBlockId,
            'booking_other.base_info.hotel_id' => $hotel->id,
            'booking_other.base_info.url_param' => $lp->url_param,
        ])->post('/booking/reservation/info/input', [
            'selected_blocks' => [
                [
                    'reservation_block_token' => $reserveBlockToken,
                    'person_num' => [2, 3],
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertExactJson([
                'code' => 200,
                'status' => 'OK',
                'data' => null,
                'message' => 'SUCCESS',
            ])
            ->assertSessionHas('booking_other.redirect_params')
            ->assertSessionHas('booking_other.payment_status', 0);
    }

    /** @test */
    public function inputBookingInfo_異常系_セッションが存在しない()
    {
        $expected = [
            'code' => 400,
            'status' => 'FAIL',
            'message' => '申し訳ありません。予期せぬエラーが発生しました。お時間をおいて再度お試しくださいませ。',
        ];
        $response = $this->post("/booking/reservation/info/input");
        $response->assertStatus(200)
            ->assertExactJson($expected);
    }

    /** @test */
    public function inputBookingInfo_異常系_予約可能な部屋タイプが存在しない()
    {
        [
            'hotel' => $hotel,
            'hotelRoomTypes' => $hotelRoomTypes,
            'cancelPolicy' => $cancelPolicy,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $handInputPrice = 10000;
        $form = $this->_特別価格手動入力ありのフォーム作成(
            $hotel,
            $hotelRoomTypes,
            $cancelPolicy,
            $handInputPrice
        );

        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);

        $reserveBlockId = -1;
        $reserveBlockToken = 'block_token';

        $response = $this->withSession([
            'booking_other.reservation_block.' . $reserveBlockToken . '.reservation_block_id' => $reserveBlockId,
            'booking_other.base_info.hotel_id' => $hotel->id,
            'booking_other.base_info.url_param' => $lp->url_param,
        ])->post('/booking/reservation/info/input', [
            'selected_blocks' => [
                [
                    'reservation_block_token' => $reserveBlockToken,
                    'person_num' => [2, 3],
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('code', 400)
            ->assertJsonPath('message', '申し訳ございません、ご予約のお手続き中にご選択された予約枠が満室となりました。大変恐れ入りますが、再度日時をご選択くださいませ。');
    }

    /** @test */
    public function inputBookingInfo_異常系_予約可能な部屋数を超えた()
    {
        [
            'hotel' => $hotel,
            'hotelRoomTypes' => $hotelRoomTypes,
            'cancelPolicy' => $cancelPolicy,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $handInputPrice = 10000;
        $form = $this->_特別価格手動入力ありのフォーム作成(
            $hotel,
            $hotelRoomTypes,
            $cancelPolicy,
            $handInputPrice
        );

        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);

        // 予約枠の作成
        $roomTypeId = $hotelRoomTypes[0]->id;
        $tommorow = Carbon::tomorrow()->format('Y-m-d');
        $reservationBlock = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 1,
            'date' => $tommorow,
            'price' => 1000,
            'room_num' => 5,
            'reserved_num' => 4
        ]);

        $reserveBlockId = $reservationBlock->id;
        $reserveBlockToken = 'block_token';

        // ApiClientをモックに差し替え
        $this->_createMockApiClientDoGetRequest();

        $response = $this->withSession([
            'booking_other.reservation_block.' . $reserveBlockToken . '.reservation_block_id' => $reserveBlockId,
            'booking_other.base_info.hotel_id' => $hotel->id,
            'booking_other.base_info.url_param' => $lp->url_param,
        ])->post('/booking/reservation/info/input', [
            'selected_blocks' => [
                [
                    'reservation_block_token' => $reserveBlockToken,
                    'person_num' => [2, 3],
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('code', 400)
            ->assertJsonPath('message', '申し訳ございません、ご予約のお手続き中にご選択された予約枠が満室となりました。大変恐れ入りますが、再度日時をご選択くださいませ。');
    }

    /** @test */
    public function inputBookingInfo_異常系_CRMからのデータ取得失敗()
    {
        [
            'hotel' => $hotel,
            'hotelRoomTypes' => $hotelRoomTypes,
            'cancelPolicy' => $cancelPolicy,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $handInputPrice = 10000;
        $form = $this->_特別価格手動入力ありのフォーム作成(
            $hotel,
            $hotelRoomTypes,
            $cancelPolicy,
            $handInputPrice
        );

        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);

        // 予約枠の作成
        $roomTypeId = $hotelRoomTypes[0]->id;
        $tommorow = Carbon::tomorrow()->format('Y-m-d');
        $reservationBlock = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 1,
            'date' => $tommorow,
            'price' => 1000,
            'room_num' => 5,
            'reserved_num' => 3
        ]);

        $reserveBlockId = $reservationBlock->id;
        $reserveBlockToken = 'block_token';

        // ApiClientをモックに差し替え
        $isInvalid = true;
        $this->_createMockApiClientDoGetRequest($isInvalid);

        $response = $this->withSession([
            'booking_other.reservation_block.' . $reserveBlockToken . '.reservation_block_id' => $reserveBlockId,
            'booking_other.base_info.hotel_id' => $hotel->id,
            'booking_other.base_info.url_param' => $lp->url_param,
        ])->post('/booking/reservation/info/input', [
            'selected_blocks' => [
                [
                    'reservation_block_token' => $reserveBlockToken,
                    'person_num' => [2, 3],
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('code', 400)
            ->assertJsonPath('message', '予期せぬエラーが発生しました。恐れ入りますが、お時間をおいて再度お試しくださいませ。');
    }

    private function _getDummyBaseCustomerItems(): array
    {
        return [
            [
                'id' => 8,
                'base_id' => -1,
                'name' => '氏名',
                'data_type' => 8,
                'is_required' => 1,
                'sort_num' => 1,
                'is_reservation_item' => 1,
            ],
            [
                'id' => 9,
                'base_id' => -1,
                'name' => '電話番号',
                'data_type' => 9,
                'is_required' => 1,
                'sort_num' => 2,
                'is_reservation_item' => 1,
            ],
            [
                'id' => 10,
                'base_id' => -1,
                'name' => 'メールアドレス',
                'data_type' => 10,
                'is_required' => 1,
                'sort_num' => 3,
                'is_reservation_item' => 1,
            ],
            [
                'id' => 11,
                'base_id' => -1,
                'name' => '住所',
                'data_type' => 11,
                'is_required' => 1,
                'sort_num' => 4,
                'is_reservation_item' => 1,
            ],
            [
                'id' => 7,
                'base_id' => -1,
                'name' => '性別',
                'data_type' => 7,
                'is_required' => 0,
                'sort_num' => 5,
                'is_reservation_item' => 1,
            ],
            [
                'id' => 1,
                'base_id' => -1,
                'name' => '短文テキスト',
                'data_type' => 1,
                'is_required' => 0,
                'sort_num' => 6,
                'is_reservation_item' => 1,
            ],
            [
                'id' => 2,
                'base_id' => -1,
                'name' => '長文テキスト',
                'data_type' => 2,
                'is_required' => 0,
                'sort_num' => 7,
                'is_reservation_item' => 1,
            ],
            [
                'id' => 3,
                'base_id' => -1,
                'name' => '数値',
                'data_type' => 3,
                'is_required' => 0,
                'sort_num' => 8,
                'is_reservation_item' => 1,
            ],
            [
                'id' => 4,
                'base_id' => -1,
                'name' => '日付',
                'data_type' => 4,
                'is_required' => 0,
                'sort_num' => 9,
                'is_reservation_item' => 1,
            ],
            [
                'id' => 5,
                'base_id' => -1,
                'name' => '時間',
                'data_type' => 5,
                'is_required' => 0,
                'sort_num' => 10,
                'is_reservation_item' => 1,
            ],
            [
                'id' => 6,
                'base_id' => -1,
                'name' => '日付＋時間',
                'data_type' => 6,
                'is_required' => 0,
                'sort_num' => 11,
                'is_reservation_item' => 1,
            ],
            [
                'id' => 12,
                'base_id' => -1,
                'name' => '部屋タイプ',
                'data_type' => 12,
                'is_required' => 0,
                'sort_num' => 12,
                'is_reservation_item' => 1,
            ],
            [
                'id' => 13,
                'base_id' => -1,
                'name' => 'チェックイン日',
                'data_type' => 13,
                'is_required' => 0,
                'sort_num' => 13,
                'is_reservation_item' => 1,
            ],
            [
                'id' => 14,
                'base_id' => -1,
                'name' => '予約開始時間',
                'data_type' => 14,
                'is_required' => 0,
                'sort_num' => 14,
                'is_reservation_item' => 1,
            ],
            [
                'id' => 15,
                'base_id' => -1,
                'name' => '予約終了時間',
                'data_type' => 15,
                'is_required' => 0,
                'sort_num' => 15,
                'is_reservation_item' => 1,
            ],
        ];
    }

    /** @test */
    public function renderInputBookingInfo_正常系_予約登録_1部屋()
    {
        [
            'hotel' => $hotel,
            'hotelRoomTypes' => $hotelRoomTypes,
            'cancelPolicy' => $cancelPolicy,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $form = factory(Form::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'public_status' => 1,
            'is_sale_period' => 1,
            'sale_period_start' => Carbon::yesterday()->format('Y-m-d h:i:s'),
            'sale_period_end' => Carbon::tomorrow()->format('Y-m-d h:i:s'),
            'is_room_type' => 0,
            'room_type_ids' => [],
            'cancel_policy_id' => $cancelPolicy->id,
            'is_deadline' => 0,
            'is_special_price' => 0,
            'prepay' => random_int(0, 2),
        ]);
        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);

        // 予約枠の作成
        $roomTypeId = $hotelRoomTypes[0]->id;
        $tommorow = Carbon::tomorrow()->format('Y-m-d');
        $reservationBlock = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 1,
            'date' => $tommorow,
            'price' => 1000,
            'start_hour' => 20,
            'start_minute' => 0,
            'end_hour' => 22,
            'end_minute' => 30,
        ]);

        $reserveBlockId = $reservationBlock->id;
        $reserveBlockToken = 'block_token';
        $lpUrlParam = $lp->url_param;

        $isNotExistsEmail = random_int(0, 1) == true;
        $baseCustomerItems = $this->_getDummyBaseCustomerItems();
        // baseCustomerItemsにメールアドレス(data_type=10)の項目が存在しないパターン
        if ($isNotExistsEmail) {
            $baseCustomerItems = array_filter($baseCustomerItems, function ($value) {
                return $value['data_type'] != 10;
            });
        }
        $startTime = sprintf('%02d:%02d', $reservationBlock->start_hour, $reservationBlock->start_minute);
        $endTime = sprintf('%02d:%02d', $reservationBlock->end_hour, $reservationBlock->end_minute);
        $checkinDate = $reservationBlock->date;
        $roomType = $hotelRoomTypes[0];
        $roomType['date'] = $checkinDate;
        $roomType['start_time'] = $startTime;
        $roomType['end_time'] = $endTime;

        $response = $this->withSession([
            'booking_other.redirect_params' => [
                'hotel' => $hotel,
                'roomAmount' => [
                    'sum' => $reservationBlock->price,
                ],
                'checkinDate' => $checkinDate,
                'startTime' => $startTime,
                'endTime' => $endTime,
                'roomTypes' => [
                    $roomType
                ],
                'form' => $form,
                'title' => '予約情報入力',
                'currentPage' => 3,
                'lineGuestInfo' => [],
                'baseCustomerItems' => $baseCustomerItems,
                'businessType' => random_int(3, 4),
                'isNotExistsEmail' => $isNotExistsEmail,
            ],
            'booking_other.reservation_block.' . $reserveBlockToken . '.reservation_block_id' => $reserveBlockId,
            'booking_other.base_info.hotel_id' => $hotel->id,
            'booking_other.base_info.url_param' => $lpUrlParam,
        ])->get('/booking/reservation/info/input/render');

        $response->assertStatus(200)
            ->assertViewIs('user.booking.other.input');

        // チェックイン日時と利用金額の表示をチェック
        $response->assertSeeInOrder([
            'チェックイン', $checkinDate . ' ' . $startTime,
            'チェックアウト', $checkinDate . ' ' . $endTime,
            '合計（消費税込み）', '¥' . number_format($reservationBlock->price),
        ]);

        $expectedSort = array_map(function ($value) {
            return $value['name'];
        }, $baseCustomerItems);
        $expectedRequired = [];
        foreach ($baseCustomerItems as $item) {
            if ($item['is_required'] == 1) {
                $expectedRequired[] = '<span class="required">※必須</span>';
            }
            $expectedRequired[] = "data-required=\"{$item['is_required']}\"";
        }
        if ($isNotExistsEmail) {
            $expectedSort[] = 'メールアドレス';
            $expectedRequired[] = '<span class="required">※必須</span>';
            $expectedRequired[] = 'data-required="1"';
        }

        // 入力項目のソート順と必須属性のチェック
        $response->assertSeeInOrder($expectedSort)
            ->assertSeeInOrder($expectedRequired);

        // 決済方法のボタン表示チェック
        if ($form->prepay == 0) {
            $response->assertSeeInOrder(['現地決済で予約', '事前決済で予約']);
        } else if ($form->prepay == 1) {
            $response->assertSee('現地決済で予約')
                ->assertDontSee('事前決済で予約');
        } else if ($form->prepay == 2) {
            $response->assertDontSee('現地決済で予約')
                ->assertSee('事前決済で予約');
        }
    }

    /** @test */
    public function renderInputBookingInfo_正常系_予約登録_複数部屋()
    {
        [
            'hotel' => $hotel,
            'hotelRoomTypes' => $hotelRoomTypes,
            'cancelPolicy' => $cancelPolicy,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $form = factory(Form::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'public_status' => 1,
            'is_sale_period' => 1,
            'sale_period_start' => Carbon::yesterday()->format('Y-m-d h:i:s'),
            'sale_period_end' => Carbon::tomorrow()->format('Y-m-d h:i:s'),
            'is_room_type' => 0,
            'room_type_ids' => [],
            'cancel_policy_id' => $cancelPolicy->id,
            'is_deadline' => 0,
            'is_special_price' => 0,
            'prepay' => random_int(0, 2),
        ]);
        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);

        // 予約枠の作成
        $roomTypeId = $hotelRoomTypes[0]->id;
        $tommorow = Carbon::tomorrow()->format('Y-m-d');
        $reservationBlock = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 1,
            'date' => $tommorow,
            'price' => 1000,
            'start_hour' => 20,
            'start_minute' => 0,
            'end_hour' => 22,
            'end_minute' => 30,
            'room_num' => $hotelRoomTypes[0]->room_num,
        ]);

        $reserveBlockId = $reservationBlock->id;
        $reserveBlockToken = 'block_token';
        $lpUrlParam = $lp->url_param;

        $isNotExistsEmail = random_int(0, 1) == true;
        $baseCustomerItems = $this->_getDummyBaseCustomerItems();
        // baseCustomerItemsにメールアドレス(data_type=10)の項目が存在しないパターン
        if ($isNotExistsEmail) {
            $baseCustomerItems = array_filter($baseCustomerItems, function ($value) {
                return $value['data_type'] != 10;
            });
        }
        $startTime = sprintf('%02d:%02d', $reservationBlock->start_hour, $reservationBlock->start_minute);
        $endTime = sprintf('%02d:%02d', $reservationBlock->end_hour, $reservationBlock->end_minute);
        $checkinDate = $reservationBlock->date;
        $roomType = [
            'reservation_block_id' => $reservationBlock->id,
            'reservation_block_token' => $reserveBlockToken,
            'room_type_id' => $reservationBlock->roomType->id,
            'room_name' => $reservationBlock->roomType->name,
            'date' => $reservationBlock->date,
            'price' => $reservationBlock->price,
            'amount' => $reservationBlock->price,
            'start_time' => $reservationBlock->getStartTime(),
            'end_time' => $reservationBlock->getEndTime(),
            'room_num' => $reservationBlock->room_num,
            'reserved_num' => $reservationBlock->reserved_num,
        ];
        $roomTypes = [
            array_merge($roomType, ['person_num' => 2]),
            array_merge($roomType, ['person_num' => 1]),
        ];
        $roomAmountSum = $reservationBlock->price * count($roomTypes);
        $response = $this->withSession([
            'booking_other.redirect_params' => [
                'hotel' => $hotel,
                'roomAmount' => [
                    'sum' => $roomAmountSum,
                ],
                'checkinDate' => $checkinDate,
                'startTime' => $startTime,
                'endTime' => $endTime,
                'roomTypes' => $roomTypes,
                'form' => $form,
                'title' => '予約情報入力',
                'currentPage' => 3,
                'lineGuestInfo' => [],
                'baseCustomerItems' => $baseCustomerItems,
                'businessType' => random_int(3, 4),
                'isNotExistsEmail' => $isNotExistsEmail,
            ],
            'booking_other.reservation_block.' . $reserveBlockToken . '.reservation_block_id' => $reserveBlockId,
            'booking_other.base_info.hotel_id' => $hotel->id,
            'booking_other.base_info.url_param' => $lpUrlParam,
        ])->get('/booking/reservation/info/input/render');

        $response->assertStatus(200)
            ->assertViewIs('user.booking.other.input');

        // チェックイン日時と利用金額の表示をチェック
        $response->assertSeeInOrder([
            'チェックイン', $checkinDate . ' ' . $startTime,
            'チェックアウト', $checkinDate . ' ' . $endTime,
            '合計（消費税込み）', '¥' . number_format($roomAmountSum),
        ]);

        $expectedSort = array_map(function ($value) {
            return $value['name'];
        }, $baseCustomerItems);
        $expectedRequired = [];
        foreach ($baseCustomerItems as $item) {
            if ($item['is_required'] == 1) {
                $expectedRequired[] = '<span class="required">※必須</span>';
            }
            $expectedRequired[] = "data-required=\"{$item['is_required']}\"";
        }
        if ($isNotExistsEmail) {
            $expectedSort[] = 'メールアドレス';
            $expectedRequired[] = '<span class="required">※必須</span>';
            $expectedRequired[] = 'data-required="1"';
        }

        // 入力項目のソート順と必須属性のチェック
        $response->assertSeeInOrder($expectedSort)
            ->assertSeeInOrder($expectedRequired);

        // 決済方法のボタン表示チェック
        if ($form->prepay == 0) {
            $response->assertSeeInOrder(['現地決済で予約', '事前決済で予約']);
        } else if ($form->prepay == 1) {
            $response->assertSee('現地決済で予約')
                ->assertDontSee('事前決済で予約');
        } else if ($form->prepay == 2) {
            $response->assertDontSee('現地決済で予約')
                ->assertSee('事前決済で予約');
        }
    }

    /** @test */
    public function renderInputBookingInfo_正常系_予約更新_1部屋()
    {
        [
            'hotel' => $hotel,
            'hotelRoomTypes' => $hotelRoomTypes,
            'cancelPolicy' => $cancelPolicy,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $form = factory(Form::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'public_status' => 1,
            'is_sale_period' => 1,
            'sale_period_start' => Carbon::yesterday()->format('Y-m-d h:i:s'),
            'sale_period_end' => Carbon::tomorrow()->format('Y-m-d h:i:s'),
            'is_room_type' => 0,
            'room_type_ids' => [],
            'cancel_policy_id' => $cancelPolicy->id,
            'is_deadline' => 0,
            'is_special_price' => 0,
            'prepay' => random_int(0, 2),
        ]);
        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);

        // 予約枠の作成
        $roomTypeId = $hotelRoomTypes[0]->id;
        $tommorow = Carbon::tomorrow()->format('Y-m-d');
        $reservationBlock = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 1,
            'date' => $tommorow,
            'price' => 1000,
            'start_hour' => 20,
            'start_minute' => 0,
            'end_hour' => 22,
            'end_minute' => 30,
            'room_num' => $hotelRoomTypes[0]->room_num,
        ]);
        $startTime = sprintf('%02d:%02d', $reservationBlock->start_hour, $reservationBlock->start_minute);
        $endTime = sprintf('%02d:%02d', $reservationBlock->end_hour, $reservationBlock->end_minute);
        $checkinDate = $reservationBlock->date;

        // 予約の作成
        $reservation = $this->_予約の作成($reservationBlock, $hotel);

        // 予約入力項目の作成
        $inputBaseCustomerItemValues = $this->_createBaseCutomerItemValues($reservation);
        $reservation['base_customer_item_values'] = $inputBaseCustomerItemValues;

        $reserveBlockId = $reservationBlock->id;
        $reserveBlockToken = 'block_token';
        $lpUrlParam = $lp->url_param;

        $isNotExistsEmail = random_int(0, 1) == true;
        $baseCustomerItems = $this->_getDummyBaseCustomerItems();
        // baseCustomerItemsにメールアドレス(data_type=10)の項目が存在しないパターン
        if ($isNotExistsEmail) {
            $baseCustomerItems = array_filter($baseCustomerItems, function ($value) {
                return $value['data_type'] != 10;
            });
        }
        $roomType = $hotelRoomTypes[0];
        $roomType['date'] = $checkinDate;
        $roomType['start_time'] = $startTime;
        $roomType['end_time'] = $endTime;

        $response = $this->withSession([
            'booking_other.redirect_params' => [
                'hotel' => $hotel,
                'roomAmount' => [
                    'sum' => $reservationBlock->price,
                ],
                'checkinDate' => $checkinDate,
                'startTime' => $startTime,
                'endTime' => $endTime,
                'roomTypes' => [
                    $roomType
                ],
                'form' => $form,
                'title' => '予約情報入力',
                'currentPage' => 3,
                'lineGuestInfo' => [],
                'baseCustomerItems' => $baseCustomerItems,
                'businessType' => random_int(3, 4),
                'isNotExistsEmail' => $isNotExistsEmail,
                'reservation' => $reservation,
            ],
            'booking_other.reservation_block.' . $reserveBlockToken . '.reservation_block_id' => $reserveBlockId,
            'booking_other.base_info.hotel_id' => $hotel->id,
            'booking_other.base_info.url_param' => $lpUrlParam,
        ])->get('/booking/reservation/info/input/render');

        $response->assertStatus(200)
            ->assertViewIs('user.booking.other.update_input');

        // チェックイン日時と利用金額の表示をチェック
        $response->assertSeeInOrder([
            'チェックイン', $checkinDate . ' ' . $startTime,
            'チェックアウト', $checkinDate . ' ' . $endTime,
            '合計（消費税込み）', '¥' . number_format($reservationBlock->price),
        ]);

        $expectedSort = array_map(function ($value) {
            return $value['name'];
        }, $baseCustomerItems);
        $expectedRequired = [];
        foreach ($baseCustomerItems as $item) {
            if ($item['is_required'] == 1) {
                $expectedRequired[] = '<span class="required">※必須</span>';
            }
            $expectedRequired[] = "data-required=\"{$item['is_required']}\"";
        }
        if ($isNotExistsEmail) {
            $expectedSort[] = 'メールアドレス';
            $expectedRequired[] = '<span class="required">※必須</span>';
            $expectedRequired[] = 'data-required="1"';
        }

        // 入力項目のソート順と必須属性のチェック
        $response->assertSeeInOrder($expectedSort)
            ->assertSeeInOrder($expectedRequired)
            ->assertSee('変更を確定');
    }

    /** @test */
    public function renderInputBookingInfo_正常系_予約更新_複数部屋()
    {
        [
            'hotel' => $hotel,
            'hotelRoomTypes' => $hotelRoomTypes,
            'cancelPolicy' => $cancelPolicy,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $form = factory(Form::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'public_status' => 1,
            'is_sale_period' => 1,
            'sale_period_start' => Carbon::yesterday()->format('Y-m-d h:i:s'),
            'sale_period_end' => Carbon::tomorrow()->format('Y-m-d h:i:s'),
            'is_room_type' => 0,
            'room_type_ids' => [],
            'cancel_policy_id' => $cancelPolicy->id,
            'is_deadline' => 0,
            'is_special_price' => 0,
            'prepay' => random_int(0, 2),
        ]);
        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);

        // 予約枠の作成
        $roomTypeId = $hotelRoomTypes[0]->id;
        $tommorow = Carbon::tomorrow()->format('Y-m-d');
        $reservationBlock = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 1,
            'date' => $tommorow,
            'price' => 1000,
            'start_hour' => 20,
            'start_minute' => 0,
            'end_hour' => 22,
            'end_minute' => 30,
            'room_num' => $hotelRoomTypes[0]->room_num,
        ]);
        $startTime = sprintf('%02d:%02d', $reservationBlock->start_hour, $reservationBlock->start_minute);
        $endTime = sprintf('%02d:%02d', $reservationBlock->end_hour, $reservationBlock->end_minute);
        $checkinDate = $reservationBlock->date;

        // 予約の作成
        $reservation = $this->_予約の作成($reservationBlock, $hotel);

        // 予約入力項目の作成
        $inputBaseCustomerItemValues = $this->_createBaseCutomerItemValues($reservation);
        $reservation['base_customer_item_values'] = $inputBaseCustomerItemValues;

        $reserveBlockId = $reservationBlock->id;
        $reserveBlockToken = 'block_token';
        $lpUrlParam = $lp->url_param;

        $isNotExistsEmail = random_int(0, 1) == true;
        $baseCustomerItems = $this->_getDummyBaseCustomerItems();
        // baseCustomerItemsにメールアドレス(data_type=10)の項目が存在しないパターン
        if ($isNotExistsEmail) {
            $baseCustomerItems = array_filter($baseCustomerItems, function ($value) {
                return $value['data_type'] != 10;
            });
        }
        $roomType = [
            'reservation_block_id' => $reservationBlock->id,
            'reservation_block_token' => $reserveBlockToken,
            'room_type_id' => $reservationBlock->roomType->id,
            'room_name' => $reservationBlock->roomType->name,
            'date' => $reservationBlock->date,
            'price' => $reservationBlock->price,
            'amount' => $reservationBlock->price,
            'start_time' => $reservationBlock->getStartTime(),
            'end_time' => $reservationBlock->getEndTime(),
            'room_num' => $reservationBlock->room_num,
            'reserved_num' => $reservationBlock->reserved_num,
        ];
        $roomTypes = [
            array_merge($roomType, ['person_num' => 2]),
            array_merge($roomType, ['person_num' => 1]),
        ];
        $roomAmountSum = $reservationBlock->price * count($roomTypes);
        $response = $this->withSession([
            'booking_other.redirect_params' => [
                'hotel' => $hotel,
                'roomAmount' => [
                    'sum' => $roomAmountSum,
                ],
                'checkinDate' => $checkinDate,
                'startTime' => $startTime,
                'endTime' => $endTime,
                'roomTypes' => $roomTypes,
                'form' => $form,
                'title' => '予約情報入力',
                'currentPage' => 3,
                'lineGuestInfo' => [],
                'baseCustomerItems' => $baseCustomerItems,
                'businessType' => random_int(3, 4),
                'isNotExistsEmail' => $isNotExistsEmail,
                'reservation' => $reservation,
            ],
            'booking_other.reservation_block.' . $reserveBlockToken . '.reservation_block_id' => $reserveBlockId,
            'booking_other.base_info.hotel_id' => $hotel->id,
            'booking_other.base_info.url_param' => $lpUrlParam,
        ])->get('/booking/reservation/info/input/render');

        $response->assertStatus(200)
            ->assertViewIs('user.booking.other.update_input');

        // チェックイン日時と利用金額の表示をチェック
        $response->assertSeeInOrder([
            'チェックイン', $checkinDate . ' ' . $startTime,
            'チェックアウト', $checkinDate . ' ' . $endTime,
            '合計（消費税込み）', '¥' . number_format($roomAmountSum),
        ]);

        $expectedSort = array_map(function ($value) {
            return $value['name'];
        }, $baseCustomerItems);
        $expectedRequired = [];
        foreach ($baseCustomerItems as $item) {
            if ($item['is_required'] == 1) {
                $expectedRequired[] = '<span class="required">※必須</span>';
            }
            $expectedRequired[] = "data-required=\"{$item['is_required']}\"";
        }
        if ($isNotExistsEmail) {
            $expectedSort[] = 'メールアドレス';
            $expectedRequired[] = '<span class="required">※必須</span>';
            $expectedRequired[] = 'data-required="1"';
        }

        // 入力項目のソート順と必須属性のチェック
        $response->assertSeeInOrder($expectedSort)
            ->assertSeeInOrder($expectedRequired)
            ->assertSee('変更を確定');
    }

    private function _createBaseCutomerItemValues(Reservation $reservation): array
    {
        $inputValues = [
            [
                'reservation_id' => $reservation->id,
                'base_customer_item_id' => 1,
                'name' => '短文テキスト',
                'data_type' => 1,
                'value' => 'AAAA'
            ],
            [
                'reservation_id' => $reservation->id,
                'base_customer_item_id' => 2,
                'name' => '長文テキスト',
                'data_type' => 2,
                'value' => 'AAAABBBB'
            ],
            [
                'reservation_id' => $reservation->id,
                'base_customer_item_id' => 3,
                'name' => '数値',
                'data_type' => 3,
                'value' => '123'
            ],
            [
                'reservation_id' => $reservation->id,
                'base_customer_item_id' => 4,
                'name' => '日付',
                'data_type' => 4,
                'value' => '2022-01-01'
            ],
            [
                'reservation_id' => $reservation->id,
                'base_customer_item_id' => 5,
                'name' => '時間',
                'data_type' => 5,
                'value' => '10:30:00'
            ],
            [
                'reservation_id' => $reservation->id,
                'base_customer_item_id' => 6,
                'name' => '日付+時間',
                'data_type' => 6,
                'value' => '2022-01-01 10:30:00'
            ],
            [
                'reservation_id' => $reservation->id,
                'base_customer_item_id' => 7,
                'name' => '性別',
                'data_type' => 7,
                'value' => strval(random_int(1, 3))
            ],
            [
                'reservation_id' => $reservation->id,
                'base_customer_item_id' => 8,
                'name' => '氏名',
                'data_type' => 8,
                'value' => 'サンプル ですよ'
            ],
            [
                'reservation_id' => $reservation->id,
                'base_customer_item_id' => 9,
                'name' => '電話番号',
                'data_type' => 9,
                'value' => '01234567890'
            ],
            [
                'reservation_id' => $reservation->id,
                'base_customer_item_id' => 10,
                'name' => 'メールアドレス',
                'data_type' => 10,
                'value' => 'client@shareg.com'
            ],
            [
                'reservation_id' => $reservation->id,
                'base_customer_item_id' => 11,
                'name' => '住所',
                'data_type' => 11,
                'value' => '東京都江東区'
            ],
            [
                'reservation_id' => $reservation->id,
                'base_customer_item_id' => 12,
                'name' => '部屋タイプ',
                'data_type' => 12,
                'value' => '部屋タイプ'
            ],
        ];
        $baseCustomerItemValues = array_map(function ($value) {
            return factory(BaseCustomerItemValue::class)->create($value);
        }, $inputValues);

        $values = BaseCustomerItemValue::whereIn('id', array_column($baseCustomerItemValues, 'id'))->get();
        return array_column(
            $values->toArray(),
            null,
            'base_customer_item_id'
        );
    }

    /** @test */
    public function renderInputBookingInfo_異常系_リダイレクト用のセッションデータが存在しない()
    {
        [
            'hotel' => $hotel,
            'hotelRoomTypes' => $hotelRoomTypes,
            'cancelPolicy' => $cancelPolicy,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $form = factory(Form::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'public_status' => 1,
            'is_sale_period' => 1,
            'sale_period_start' => Carbon::yesterday()->format('Y-m-d h:i:s'),
            'sale_period_end' => Carbon::tomorrow()->format('Y-m-d h:i:s'),
            'is_room_type' => 1,
            'room_type_ids' => $hotelRoomTypes->pluck('id')->toArray(),
            'cancel_policy_id' => $cancelPolicy->id,
            'is_deadline' => 0,
            'is_special_price' => 0,
        ]);
        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);

        $url = config('app.url');
        $lpUrlParam = $lp->url_param;

        $response = $this->withSession([
            'booking_other.redirect_params' => [],
            'booking_other.base_info.hotel_id' => $hotel->id,
            'booking_other.base_info.url_param' => $lpUrlParam,
        ])->get('/booking/reservation/info/input/render');
        $response->assertHeader('location', "{$url}/page/reservation/search_panel?url_param={$lpUrlParam}")
            ->assertSessionHas('error', '申し訳ありません。予期せぬエラーが発生しました。お時間をおいて再度お試しくださいませ。');
    }

    /** @test */
    public function renderAdminInputBookingInfo_正常系_予約登録_1部屋()
    {
        [
            'hotel' => $hotel,
            'hotelRoomTypes' => $hotelRoomTypes,
            'cancelPolicy' => $cancelPolicy,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $form = factory(Form::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'public_status' => 1,
            'is_sale_period' => 1,
            'sale_period_start' => Carbon::yesterday()->format('Y-m-d h:i:s'),
            'sale_period_end' => Carbon::tomorrow()->format('Y-m-d h:i:s'),
            'is_room_type' => 0,
            'room_type_ids' => [],
            'cancel_policy_id' => $cancelPolicy->id,
            'is_deadline' => 0,
            'is_special_price' => 0,
            'prepay' => random_int(0, 2),
        ]);
        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);

        // 予約枠の作成
        $roomTypeId = $hotelRoomTypes[0]->id;
        $tommorow = Carbon::tomorrow()->format('Y-m-d');
        $reservationBlock = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 1,
            'date' => $tommorow,
            'price' => 1000,
            'start_hour' => 20,
            'start_minute' => 0,
            'end_hour' => 22,
            'end_minute' => 30,
        ]);

        $reserveBlockId = $reservationBlock->id;
        $reserveBlockToken = 'block_token';
        $lpUrlParam = $lp->url_param;

        $isNotExistsEmail = random_int(0, 1) == true;
        $baseCustomerItems = $this->_getDummyBaseCustomerItems();
        // baseCustomerItemsにメールアドレス(data_type=10)の項目が存在しないパターン
        if ($isNotExistsEmail) {
            $baseCustomerItems = array_filter($baseCustomerItems, function ($value) {
                return $value['data_type'] != 10;
            });
        }
        $startTime = sprintf('%02d:%02d', $reservationBlock->start_hour, $reservationBlock->start_minute);
        $endTime = sprintf('%02d:%02d', $reservationBlock->end_hour, $reservationBlock->end_minute);
        $checkinDate = $reservationBlock->date;
        $roomType = $hotelRoomTypes[0];
        $roomType['date'] = $checkinDate;
        $roomType['start_time'] = $startTime;
        $roomType['end_time'] = $endTime;

        $response = $this->withSession([
            'booking_other.redirect_params' => [
                'hotel' => $hotel,
                'roomAmount' => [
                    'sum' => $reservationBlock->price,
                ],
                'checkinDate' => $checkinDate,
                'startTime' => $startTime,
                'endTime' => $endTime,
                'roomTypes' => [
                    $roomType
                ],
                'form' => $form,
                'title' => '予約情報入力',
                'currentPage' => 3,
                'lineGuestInfo' => [],
                'baseCustomerItems' => $baseCustomerItems,
                'businessType' => random_int(3, 4),
                'isNotExistsEmail' => $isNotExistsEmail,
            ],
            'booking_other.reservation_block.' . $reserveBlockToken . '.reservation_block_id' => $reserveBlockId,
            'booking_other.base_info.hotel_id' => $hotel->id,
            'booking_other.base_info.url_param' => $lpUrlParam,
        ])->get('/booking/reservation/info/input/admin/render');

        $response->assertStatus(200)
            ->assertViewIs('user.booking.other.admin-input');

        // チェックイン日時と利用金額の表示をチェック
        $response->assertSeeInOrder([
            'チェックイン', $checkinDate . ' ' . $startTime,
            'チェックアウト', $checkinDate . ' ' . $endTime,
            '合計（消費税込み）', '¥' . number_format($reservationBlock->price),
        ]);

        $expectedSort = array_map(function ($value) {
            return $value['name'];
        }, $baseCustomerItems);
        $expectedRequired = [];
        foreach ($baseCustomerItems as $item) {
            if ($item['is_required'] == 1) {
                $expectedRequired[] = '<span class="required">※必須</span>';
            }
            $expectedRequired[] = "data-required=\"{$item['is_required']}\"";
        }
        if ($isNotExistsEmail) {
            $expectedSort[] = 'メールアドレス';
            $expectedRequired[] = '<span class="required">※必須</span>';
            $expectedRequired[] = 'data-required="1"';
        }

        // 入力項目のソート順と必須属性のチェック
        $response->assertSeeInOrder($expectedSort)
            ->assertSeeInOrder($expectedRequired);

        // 決済方法のボタン表示チェック
        if ($form->prepay == 0) {
            $response->assertSeeInOrder(['現地決済で予約', '事前決済で予約']);
        } else if ($form->prepay == 1) {
            $response->assertSee('現地決済で予約')
                ->assertDontSee('事前決済で予約');
        } else if ($form->prepay == 2) {
            $response->assertDontSee('現地決済で予約')
                ->assertSee('事前決済で予約');
        }
    }

    /** @test */
    public function renderAdminInputBookingInfo_正常系_予約登録_複数部屋()
    {
        [
            'hotel' => $hotel,
            'hotelRoomTypes' => $hotelRoomTypes,
            'cancelPolicy' => $cancelPolicy,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $form = factory(Form::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'public_status' => 1,
            'is_sale_period' => 1,
            'sale_period_start' => Carbon::yesterday()->format('Y-m-d h:i:s'),
            'sale_period_end' => Carbon::tomorrow()->format('Y-m-d h:i:s'),
            'is_room_type' => 0,
            'room_type_ids' => [],
            'cancel_policy_id' => $cancelPolicy->id,
            'is_deadline' => 0,
            'is_special_price' => 0,
            'prepay' => random_int(0, 2),
        ]);
        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);

        // 予約枠の作成
        $roomTypeId = $hotelRoomTypes[0]->id;
        $tommorow = Carbon::tomorrow()->format('Y-m-d');
        $reservationBlock = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 1,
            'date' => $tommorow,
            'price' => 1000,
            'start_hour' => 20,
            'start_minute' => 0,
            'end_hour' => 22,
            'end_minute' => 30,
            'room_num' => $hotelRoomTypes[0]->room_num,
        ]);

        $reserveBlockId = $reservationBlock->id;
        $reserveBlockToken = 'block_token';
        $lpUrlParam = $lp->url_param;

        $isNotExistsEmail = random_int(0, 1) == true;
        $baseCustomerItems = $this->_getDummyBaseCustomerItems();
        // baseCustomerItemsにメールアドレス(data_type=10)の項目が存在しないパターン
        if ($isNotExistsEmail) {
            $baseCustomerItems = array_filter($baseCustomerItems, function ($value) {
                return $value['data_type'] != 10;
            });
        }
        $startTime = sprintf('%02d:%02d', $reservationBlock->start_hour, $reservationBlock->start_minute);
        $endTime = sprintf('%02d:%02d', $reservationBlock->end_hour, $reservationBlock->end_minute);
        $checkinDate = $reservationBlock->date;
        $roomType = [
            'reservation_block_id' => $reservationBlock->id,
            'reservation_block_token' => $reserveBlockToken,
            'room_type_id' => $reservationBlock->roomType->id,
            'room_name' => $reservationBlock->roomType->name,
            'date' => $reservationBlock->date,
            'price' => $reservationBlock->price,
            'amount' => $reservationBlock->price,
            'start_time' => $reservationBlock->getStartTime(),
            'end_time' => $reservationBlock->getEndTime(),
            'room_num' => $reservationBlock->room_num,
            'reserved_num' => $reservationBlock->reserved_num,
        ];
        $roomTypes = [
            array_merge($roomType, ['person_num' => 2]),
            array_merge($roomType, ['person_num' => 1]),
        ];
        $roomAmountSum = $reservationBlock->price * count($roomTypes);
        $response = $this->withSession([
            'booking_other.redirect_params' => [
                'hotel' => $hotel,
                'roomAmount' => [
                    'sum' => $roomAmountSum,
                ],
                'checkinDate' => $checkinDate,
                'startTime' => $startTime,
                'endTime' => $endTime,
                'roomTypes' => $roomTypes,
                'form' => $form,
                'title' => '予約情報入力',
                'currentPage' => 3,
                'lineGuestInfo' => [],
                'baseCustomerItems' => $baseCustomerItems,
                'businessType' => random_int(3, 4),
                'isNotExistsEmail' => $isNotExistsEmail,
            ],
            'booking_other.reservation_block.' . $reserveBlockToken . '.reservation_block_id' => $reserveBlockId,
            'booking_other.base_info.hotel_id' => $hotel->id,
            'booking_other.base_info.url_param' => $lpUrlParam,
        ])->get('/booking/reservation/info/input/admin/render');

        $response->assertStatus(200)
            ->assertViewIs('user.booking.other.admin-input');

        // チェックイン日時と利用金額の表示をチェック
        $response->assertSeeInOrder([
            'チェックイン', $checkinDate . ' ' . $startTime,
            'チェックアウト', $checkinDate . ' ' . $endTime,
            '合計（消費税込み）', '¥' . number_format($roomAmountSum),
        ]);

        $expectedSort = array_map(function ($value) {
            return $value['name'];
        }, $baseCustomerItems);
        $expectedRequired = [];
        foreach ($baseCustomerItems as $item) {
            if ($item['is_required'] == 1) {
                $expectedRequired[] = '<span class="required">※必須</span>';
            }
            $expectedRequired[] = "data-required=\"{$item['is_required']}\"";
        }
        if ($isNotExistsEmail) {
            $expectedSort[] = 'メールアドレス';
            $expectedRequired[] = '<span class="required">※必須</span>';
            $expectedRequired[] = 'data-required="1"';
        }

        // 入力項目のソート順と必須属性のチェック
        $response->assertSeeInOrder($expectedSort)
            ->assertSeeInOrder($expectedRequired);

        // 決済方法のボタン表示チェック
        if ($form->prepay == 0) {
            $response->assertSeeInOrder(['現地決済で予約', '事前決済で予約']);
        } else if ($form->prepay == 1) {
            $response->assertSee('現地決済で予約')
                ->assertDontSee('事前決済で予約');
        } else if ($form->prepay == 2) {
            $response->assertDontSee('現地決済で予約')
                ->assertSee('事前決済で予約');
        }
    }

    /** @test */
    public function renderAdminInputBookingInfo_正常系_予約更新_1部屋()
    {
        [
            'hotel' => $hotel,
            'hotelRoomTypes' => $hotelRoomTypes,
            'cancelPolicy' => $cancelPolicy,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $form = factory(Form::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'public_status' => 1,
            'is_sale_period' => 1,
            'sale_period_start' => Carbon::yesterday()->format('Y-m-d h:i:s'),
            'sale_period_end' => Carbon::tomorrow()->format('Y-m-d h:i:s'),
            'is_room_type' => 0,
            'room_type_ids' => [],
            'cancel_policy_id' => $cancelPolicy->id,
            'is_deadline' => 0,
            'is_special_price' => 0,
            'prepay' => random_int(0, 2),
        ]);
        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);

        // 予約枠の作成
        $roomTypeId = $hotelRoomTypes[0]->id;
        $tommorow = Carbon::tomorrow()->format('Y-m-d');
        $reservationBlock = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 1,
            'date' => $tommorow,
            'price' => 1000,
            'start_hour' => 20,
            'start_minute' => 0,
            'end_hour' => 22,
            'end_minute' => 30,
            'room_num' => $hotelRoomTypes[0]->room_num,
        ]);
        $startTime = sprintf('%02d:%02d', $reservationBlock->start_hour, $reservationBlock->start_minute);
        $endTime = sprintf('%02d:%02d', $reservationBlock->end_hour, $reservationBlock->end_minute);
        $checkinDate = $reservationBlock->date;

        // 予約の作成
        $reservation = $this->_予約の作成($reservationBlock, $hotel);

        // 予約入力項目の作成
        $inputBaseCustomerItemValues = $this->_createBaseCutomerItemValues($reservation);
        $reservation['base_customer_item_values'] = $inputBaseCustomerItemValues;

        $reserveBlockId = $reservationBlock->id;
        $reserveBlockToken = 'block_token';
        $lpUrlParam = $lp->url_param;

        $isNotExistsEmail = random_int(0, 1) == true;
        $baseCustomerItems = $this->_getDummyBaseCustomerItems();
        // baseCustomerItemsにメールアドレス(data_type=10)の項目が存在しないパターン
        if ($isNotExistsEmail) {
            $baseCustomerItems = array_filter($baseCustomerItems, function ($value) {
                return $value['data_type'] != 10;
            });
        }
        $roomType = $hotelRoomTypes[0];
        $roomType['date'] = $checkinDate;
        $roomType['start_time'] = $startTime;
        $roomType['end_time'] = $endTime;

        $response = $this->withSession([
            'booking_other.redirect_params' => [
                'hotel' => $hotel,
                'roomAmount' => [
                    'sum' => $reservationBlock->price,
                ],
                'checkinDate' => $checkinDate,
                'startTime' => $startTime,
                'endTime' => $endTime,
                'roomTypes' => [
                    $roomType
                ],
                'form' => $form,
                'title' => '予約情報入力',
                'currentPage' => 3,
                'lineGuestInfo' => [],
                'baseCustomerItems' => $baseCustomerItems,
                'businessType' => random_int(3, 4),
                'isNotExistsEmail' => $isNotExistsEmail,
                'reservation' => $reservation,
            ],
            'booking_other.reservation_block.' . $reserveBlockToken . '.reservation_block_id' => $reserveBlockId,
            'booking_other.base_info.hotel_id' => $hotel->id,
            'booking_other.base_info.url_param' => $lpUrlParam,
        ])->get('/booking/reservation/info/input/admin/render');

        $response->assertStatus(200)
            ->assertViewIs('user.booking.other.update_input');

        // チェックイン日時と利用金額の表示をチェック
        $response->assertSeeInOrder([
            'チェックイン', $checkinDate . ' ' . $startTime,
            'チェックアウト', $checkinDate . ' ' . $endTime,
            '合計（消費税込み）', '¥' . number_format($reservationBlock->price),
        ]);

        $expectedSort = array_map(function ($value) {
            return $value['name'];
        }, $baseCustomerItems);
        $expectedRequired = [];
        foreach ($baseCustomerItems as $item) {
            if ($item['is_required'] == 1) {
                $expectedRequired[] = '<span class="required">※必須</span>';
            }
            $expectedRequired[] = "data-required=\"{$item['is_required']}\"";
        }
        if ($isNotExistsEmail) {
            $expectedSort[] = 'メールアドレス';
            $expectedRequired[] = '<span class="required">※必須</span>';
            $expectedRequired[] = 'data-required="1"';
        }

        // 入力項目のソート順と必須属性のチェック
        $response->assertSeeInOrder($expectedSort)
            ->assertSeeInOrder($expectedRequired)
            ->assertSee('変更を確定');
    }

    /** @test */
    public function renderAdminInputBookingInfo_正常系_予約更新_複数部屋()
    {
        [
            'hotel' => $hotel,
            'hotelRoomTypes' => $hotelRoomTypes,
            'cancelPolicy' => $cancelPolicy,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $form = factory(Form::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'public_status' => 1,
            'is_sale_period' => 1,
            'sale_period_start' => Carbon::yesterday()->format('Y-m-d h:i:s'),
            'sale_period_end' => Carbon::tomorrow()->format('Y-m-d h:i:s'),
            'is_room_type' => 0,
            'room_type_ids' => [],
            'cancel_policy_id' => $cancelPolicy->id,
            'is_deadline' => 0,
            'is_special_price' => 0,
            'prepay' => random_int(0, 2),
        ]);
        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);

        // 予約枠の作成
        $roomTypeId = $hotelRoomTypes[0]->id;
        $tommorow = Carbon::tomorrow()->format('Y-m-d');
        $reservationBlock = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 1,
            'date' => $tommorow,
            'price' => 1000,
            'start_hour' => 20,
            'start_minute' => 0,
            'end_hour' => 22,
            'end_minute' => 30,
            'room_num' => $hotelRoomTypes[0]->room_num,
        ]);
        $startTime = sprintf('%02d:%02d', $reservationBlock->start_hour, $reservationBlock->start_minute);
        $endTime = sprintf('%02d:%02d', $reservationBlock->end_hour, $reservationBlock->end_minute);
        $checkinDate = $reservationBlock->date;

        // 予約の作成
        $reservation = $this->_予約の作成($reservationBlock, $hotel);

        // 予約入力項目の作成
        $inputBaseCustomerItemValues = $this->_createBaseCutomerItemValues($reservation);
        $reservation['base_customer_item_values'] = $inputBaseCustomerItemValues;

        $reserveBlockId = $reservationBlock->id;
        $reserveBlockToken = 'block_token';
        $lpUrlParam = $lp->url_param;

        $isNotExistsEmail = random_int(0, 1) == true;
        $baseCustomerItems = $this->_getDummyBaseCustomerItems();
        // baseCustomerItemsにメールアドレス(data_type=10)の項目が存在しないパターン
        if ($isNotExistsEmail) {
            $baseCustomerItems = array_filter($baseCustomerItems, function ($value) {
                return $value['data_type'] != 10;
            });
        }
        $roomType = [
            'reservation_block_id' => $reservationBlock->id,
            'reservation_block_token' => $reserveBlockToken,
            'room_type_id' => $reservationBlock->roomType->id,
            'room_name' => $reservationBlock->roomType->name,
            'date' => $reservationBlock->date,
            'price' => $reservationBlock->price,
            'amount' => $reservationBlock->price,
            'start_time' => $reservationBlock->getStartTime(),
            'end_time' => $reservationBlock->getEndTime(),
            'room_num' => $reservationBlock->room_num,
            'reserved_num' => $reservationBlock->reserved_num,
        ];
        $roomTypes = [
            array_merge($roomType, ['person_num' => 2]),
            array_merge($roomType, ['person_num' => 1]),
        ];
        $roomAmountSum = $reservationBlock->price * count($roomTypes);
        $response = $this->withSession([
            'booking_other.redirect_params' => [
                'hotel' => $hotel,
                'roomAmount' => [
                    'sum' => $roomAmountSum,
                ],
                'checkinDate' => $checkinDate,
                'startTime' => $startTime,
                'endTime' => $endTime,
                'roomTypes' => $roomTypes,
                'form' => $form,
                'title' => '予約情報入力',
                'currentPage' => 3,
                'lineGuestInfo' => [],
                'baseCustomerItems' => $baseCustomerItems,
                'businessType' => random_int(3, 4),
                'isNotExistsEmail' => $isNotExistsEmail,
                'reservation' => $reservation,
            ],
            'booking_other.reservation_block.' . $reserveBlockToken . '.reservation_block_id' => $reserveBlockId,
            'booking_other.base_info.hotel_id' => $hotel->id,
            'booking_other.base_info.url_param' => $lpUrlParam,
        ])->get('/booking/reservation/info/input/admin/render');

        $response->assertStatus(200)
            ->assertViewIs('user.booking.other.update_input');

        // チェックイン日時と利用金額の表示をチェック
        $response->assertSeeInOrder([
            'チェックイン', $checkinDate . ' ' . $startTime,
            'チェックアウト', $checkinDate . ' ' . $endTime,
            '合計（消費税込み）', '¥' . number_format($roomAmountSum),
        ]);

        $expectedSort = array_map(function ($value) {
            return $value['name'];
        }, $baseCustomerItems);
        $expectedRequired = [];
        foreach ($baseCustomerItems as $item) {
            if ($item['is_required'] == 1) {
                $expectedRequired[] = '<span class="required">※必須</span>';
            }
            $expectedRequired[] = "data-required=\"{$item['is_required']}\"";
        }
        if ($isNotExistsEmail) {
            $expectedSort[] = 'メールアドレス';
            $expectedRequired[] = '<span class="required">※必須</span>';
            $expectedRequired[] = 'data-required="1"';
        }

        // 入力項目のソート順と必須属性のチェック
        $response->assertSeeInOrder($expectedSort)
            ->assertSeeInOrder($expectedRequired)
            ->assertSee('変更を確定');
    }

    /** @test */
    public function renderAdminInputBookingInfo_異常系_リダイレクト用のセッションデータが存在しない()
    {
        [
            'hotel' => $hotel,
            'hotelRoomTypes' => $hotelRoomTypes,
            'cancelPolicy' => $cancelPolicy,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $form = factory(Form::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'public_status' => 1,
            'is_sale_period' => 1,
            'sale_period_start' => Carbon::yesterday()->format('Y-m-d h:i:s'),
            'sale_period_end' => Carbon::tomorrow()->format('Y-m-d h:i:s'),
            'is_room_type' => 1,
            'room_type_ids' => $hotelRoomTypes->pluck('id')->toArray(),
            'cancel_policy_id' => $cancelPolicy->id,
            'is_deadline' => 0,
            'is_special_price' => 0,
        ]);
        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);

        $url = config('app.url');
        $lpUrlParam = $lp->url_param;

        $response = $this->withSession([
            'booking_other.redirect_params' => [],
            'booking_other.base_info.hotel_id' => $hotel->id,
            'booking_other.base_info.url_param' => $lpUrlParam,
        ])->get('/booking/reservation/info/input/admin/render');
        $response->assertHeader('location', $url)
            ->assertSessionHas('error', '申し訳ありません。予期せぬエラーが発生しました。お時間をおいて再度お試しくださいませ。');
    }

    public function createPaymentProvider()
    {
        $nextYear = Carbon::today()->addYears()->format('Y');
        return [
            '現地決済・事前決済どちらも許可(現地決済)' => [
                [
                    'prepay' => 0,
                    'cardInfo' => [],
                ],
            ],
            '現地決済・事前決済どちらも許可(事前決済)' => [
                [
                    'prepay' => 0,
                    'cardInfo' => [
                        'payment_method' => 1,
                        'card_number' => '4242424242424242',
                        'expiration_month' => '01',
                        'expiration_year' => $nextYear,
                        'cvc' => 999,
                    ],
                ],
            ],
            '現地決済' => [
                [
                    'prepay' => 1,
                    'cardInfo' => [],
                ],
            ],
            '事前決済' => [
                [
                    'prepay' => 2,
                    'cardInfo' => [
                        'payment_method' => 1,
                        'card_number' => '4242424242424242',
                        'expiration_month' => '01',
                        'expiration_year' => $nextYear,
                        'cvc' => 999,
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider createPaymentProvider
     * @test
     */
    public function saveReservationData_正常系_1部屋($input)
    {
        [
            'hotel' => $hotel,
            'hotelRoomTypes' => $hotelRoomTypes,
            'cancelPolicy' => $cancelPolicy,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $form = factory(Form::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'public_status' => 1,
            'is_sale_period' => 1,
            'sale_period_start' => Carbon::yesterday()->format('Y-m-d h:i:s'),
            'sale_period_end' => Carbon::tomorrow()->format('Y-m-d h:i:s'),
            'is_room_type' => 0,
            'room_type_ids' => [],
            'cancel_policy_id' => $cancelPolicy->id,
            'is_deadline' => 0,
            'is_special_price' => 0,
            'prepay' => $input['prepay'],
        ]);
        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);

        // 予約枠の作成
        $roomTypeId = $hotelRoomTypes[0]->id;
        $roomTypeName = $hotelRoomTypes[0]->name;
        $tommorow = Carbon::tomorrow()->format('Y-m-d');
        $reservationBlock = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 1,
            'date' => $tommorow,
            'price' => 1000,
            'start_hour' => 20,
            'start_minute' => 0,
            'end_hour' => 22,
            'end_minute' => 30,
        ]);

        $reserveBlockId = $reservationBlock->id;
        $lpUrlParam = $lp->url_param;
        $baseCustomerItems = $this->_getDummyBaseCustomerItems();

        $this->_createMockApiClientDoRequest();
        $this->_createMockStripePrePay();

        $selectedRooms = [
            [
                'reservation_block_id' => $reserveBlockId,
                'room_type_id' => $reservationBlock->room_type_id,
                'room_name' => $roomTypeName,
                'amount' => $reservationBlock->price,
                'date' => $reservationBlock->date,
                'start_time' => $reservationBlock->getStartTime(),
                'end_time' => $reservationBlock->getEndTime(),
                'person_num' => 2,
            ],
        ];
        $response = $this->withSession([
            'booking_other.base_info.hotel_id' => $hotel->id,
            'booking_other.base_info.url_param' => $lpUrlParam,
            'booking_other.base_customer_items' => $baseCustomerItems,
            'booking_other.selected_rooms' => $selectedRooms,
            'booking_other.room_amount' => [
                'sum' => $reservationBlock->price,
            ],
            'booking_other.reservation_info.session_time' => Carbon::now()->format('Y-m-d H:i'),
        ])->post('/booking/reservation/confirm', array_merge([
            'item_8' => 'サンプル太郎', // 氏名
            'item_9' => '00000000000', // 電話番号
            'item_10' => 'client@shareg.com', // メールアドレス
            'item_10_confirm' => 'client@shareg.com', // メールアドレス確認用
            'item_11' => '日本', // 住所
        ], $input['cardInfo']));

        $response->assertStatus(200)
            ->assertViewIs('user.booking.other.complete')
            ->assertSessionDoesntHaveErrors()
            ->assertSessionMissing('booking_other')
            ->assertSeeInOrder([
                '予約が完了しました！',
                'ご入力のメールアドレスに確認メールをお送りしました。予約情報はそちらのメールをご確認ください。',
                '予約情報確認',
            ]);

        // 1部屋分のレコードがあることをチェック
        $this->assertDatabaseHas('reserved_reservation_blocks', [
            'reservation_block_id' => $reserveBlockId,
            'person_num' => $selectedRooms[0]['person_num'],
            'price' => $reservationBlock->price,
            'date' => $reservationBlock->date,
            'start_hour' => $reservationBlock->start_hour,
            'start_minute' => $reservationBlock->start_minute,
            'end_hour' => $reservationBlock->end_hour,
            'end_minute' => $reservationBlock->end_minute,
            'deleted_at' => null,
        ]);
    }

    /**
     * @dataProvider createPaymentProvider
     * @test
     */
    public function saveReservationData_正常系_複数部屋($input)
    {
        [
            'hotel' => $hotel,
            'hotelRoomTypes' => $hotelRoomTypes,
            'cancelPolicy' => $cancelPolicy,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $form = factory(Form::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'public_status' => 1,
            'is_sale_period' => 1,
            'sale_period_start' => Carbon::yesterday()->format('Y-m-d h:i:s'),
            'sale_period_end' => Carbon::tomorrow()->format('Y-m-d h:i:s'),
            'is_room_type' => 0,
            'room_type_ids' => [],
            'cancel_policy_id' => $cancelPolicy->id,
            'is_deadline' => 0,
            'is_special_price' => 0,
            'prepay' => $input['prepay'],
        ]);
        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);

        // 予約枠の作成
        $roomTypeId = $hotelRoomTypes[0]->id;
        $roomTypeName = $hotelRoomTypes[0]->name;
        $tommorow = Carbon::tomorrow()->format('Y-m-d');
        $reservationBlock = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 1,
            'date' => $tommorow,
            'price' => 1000,
            'start_hour' => 20,
            'start_minute' => 0,
            'end_hour' => 22,
            'end_minute' => 30,
            'room_num' => $hotelRoomTypes[0]->room_num,
        ]);

        $reserveBlockId = $reservationBlock->id;
        $lpUrlParam = $lp->url_param;
        $baseCustomerItems = $this->_getDummyBaseCustomerItems();

        $this->_createMockApiClientDoRequest();
        $this->_createMockStripePrePay();

        $selectedRoom = [
            'reservation_block_id' => $reserveBlockId,
            'room_type_id' => $reservationBlock->room_type_id,
            'room_name' => $roomTypeName,
            'amount' => $reservationBlock->price,
            'date' => $reservationBlock->date,
            'start_time' => $reservationBlock->getStartTime(),
            'end_time' => $reservationBlock->getEndTime(),
            'person_num' => 2,
        ];
        $selectedRooms = [
            array_merge($selectedRoom, ['person_num' => 2]),
            array_merge($selectedRoom, ['person_num' => 1]),
        ];
        $roomAmountSum = $reservationBlock->price * count($selectedRooms);

        $response = $this->withSession([
            'booking_other.base_info.hotel_id' => $hotel->id,
            'booking_other.base_info.url_param' => $lpUrlParam,
            'booking_other.base_customer_items' => $baseCustomerItems,
            'booking_other.selected_rooms' => $selectedRooms,
            'booking_other.room_amount' => [
                'sum' => $roomAmountSum,
            ],
            'booking_other.reservation_info.session_time' => Carbon::now()->format('Y-m-d H:i'),
        ])->post('/booking/reservation/confirm', array_merge([
            'item_8' => 'サンプル太郎', // 氏名
            'item_9' => '00000000000', // 電話番号
            'item_10' => 'client@shareg.com', // メールアドレス
            'item_10_confirm' => 'client@shareg.com', // メールアドレス確認用
            'item_11' => '日本', // 住所
        ], $input['cardInfo']));

        $response->assertStatus(200)
            ->assertViewIs('user.booking.other.complete')
            ->assertSessionDoesntHaveErrors()
            ->assertSessionMissing('booking_other')
            ->assertSeeInOrder([
                '予約が完了しました！',
                'ご入力のメールアドレスに確認メールをお送りしました。予約情報はそちらのメールをご確認ください。',
                '予約情報確認',
            ]);

        // 2部屋分のレコードがあることをチェック
        $this->assertDatabaseHas('reserved_reservation_blocks', [
            'reservation_block_id' => $reserveBlockId,
            'person_num' => $selectedRooms[0]['person_num'],
            'price' => $reservationBlock->price,
            'date' => $reservationBlock->date,
            'start_hour' => $reservationBlock->start_hour,
            'start_minute' => $reservationBlock->start_minute,
            'end_hour' => $reservationBlock->end_hour,
            'end_minute' => $reservationBlock->end_minute,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('reserved_reservation_blocks', [
            'reservation_block_id' => $reserveBlockId,
            'person_num' => $selectedRooms[1]['person_num'],
            'price' => $reservationBlock->price,
            'date' => $reservationBlock->date,
            'start_hour' => $reservationBlock->start_hour,
            'start_minute' => $reservationBlock->start_minute,
            'end_hour' => $reservationBlock->end_hour,
            'end_minute' => $reservationBlock->end_minute,
            'deleted_at' => null,
        ]);
    }

    public function createSaveReservationValidationProvider()
    {
        $baseCustomerItems = array_column($this->_getDummyBaseCustomerItems(), null, 'id');
        $notExistsEmailDataTypeErrors = [
            'email' => 'メールアドレスは必ず指定してください。',
            'email_confirm' => 'メールアドレス確認用は必ず指定してください。',
        ];
        $dataType1 = $baseCustomerItems[1]; // 短文テキスト
        $dataType2 = $baseCustomerItems[2]; // 長文テキスト
        $dataType3 = $baseCustomerItems[3]; // 数値
        $dataType4 = $baseCustomerItems[4]; // 日付
        $dataType5 = $baseCustomerItems[5]; // 時間
        $dataType6 = $baseCustomerItems[6]; // 日付+時間
        $dataType7 = $baseCustomerItems[7]; // 性別
        $dataType8 = $baseCustomerItems[8]; // 氏名
        $dataType9 = $baseCustomerItems[9]; // 電話番号
        $dataType10 = $baseCustomerItems[10]; // メールアドレス
        $dataType11 = $baseCustomerItems[11]; // 住所
        $dataType12 = $baseCustomerItems[12]; // 部屋タイプ名
        $dataType13 = $baseCustomerItems[13]; // チェックイン日
        $dataType14 = $baseCustomerItems[14]; // 予約開始時間
        $dataType15 = $baseCustomerItems[15]; // 予約終了時間

        return [
            'data_type=1のis_requiredが0で値が50文字を超える' => [
                [
                    'values' => [
                        "item_{$dataType1['id']}" => '012345678901234567890123456789012345678901234567890',
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType1, ['is_required' => 0],
                    )],
                ],
                array_merge([
                    "item_{$dataType1['id']}" => "{$dataType1['name']}は、50文字以下で指定してください。",
                ], $notExistsEmailDataTypeErrors),
            ],
            'data_type=1のis_requiredが1で値が空' => [
                [
                    'values' => [
                        "item_{$dataType1['id']}" => '',
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType1, ['is_required' => 1],
                    )],
                ],
                array_merge([
                    "item_{$dataType1['id']}" => "{$dataType1['name']}は必ず指定してください。",
                ], $notExistsEmailDataTypeErrors),
            ],
            'data_type=1のis_requiredが1で値がnull' => [
                [
                    'values' => [
                        "item_{$dataType1['id']}" => null,
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType1, ['is_required' => 1],
                    )],
                ],
                array_merge([
                    "item_{$dataType1['id']}" => "{$dataType1['name']}は必ず指定してください。",
                ], $notExistsEmailDataTypeErrors),
            ],
            'data_type=1のis_reservation_itemが0' => [
                [
                    'values' => [
                        "item_{$dataType1['id']}" => '01234567890123456789012345678901234567890123456789',
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType1, ['is_reservation_item' => 0],
                    )],
                ],
                $notExistsEmailDataTypeErrors,
            ],
            'data_type=2のis_requiredが0で値が1000文字を超える' => [
                [
                    'values' => [
                        "item_{$dataType2['id']}" => str_repeat('0123456789', 100) . '0',
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType2, ['is_required' => 0],
                    )],
                ],
                array_merge([
                    "item_{$dataType2['id']}" => "{$dataType2['name']}は、1000文字以下で指定してください。",
                ], $notExistsEmailDataTypeErrors),
            ],
            'data_type=2のis_requiredが1で値が空' => [
                [
                    'values' => [
                        "item_{$dataType2['id']}" => '',
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType2, ['is_required' => 1],
                    )],
                ],
                array_merge([
                    "item_{$dataType2['id']}" => "{$dataType2['name']}は必ず指定してください。",
                ], $notExistsEmailDataTypeErrors),
            ],
            'data_type=2のis_requiredが1で値がnull' => [
                [
                    'values' => [
                        "item_{$dataType2['id']}" => null,
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType2, ['is_required' => 1],
                    )],
                ],
                array_merge([
                    "item_{$dataType2['id']}" => "{$dataType2['name']}は必ず指定してください。",
                ], $notExistsEmailDataTypeErrors),
            ],
            'data_type=2のis_reservation_itemが0' => [
                [
                    'values' => [
                        "item_{$dataType2['id']}" => '01234567890123456789012345678901234567890123456789',
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType2, ['is_reservation_item' => 0],
                    )],
                ],
                $notExistsEmailDataTypeErrors,
            ],
            'data_type=3のis_requiredが0で値が50桁を超える' => [
                [
                    'values' => [
                        // 指数表記になるので文字列指定
                        "item_{$dataType3['id']}" => '123456789012345678901234567890123456789012345678900',
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType3, ['is_required' => 0],
                    )],
                ],
                array_merge([
                    "item_{$dataType3['id']}" => "{$dataType3['name']}は1桁から50桁の間で指定してください。",
                ], $notExistsEmailDataTypeErrors),
            ],
            'data_type=3の値が数値ではない' => [
                [
                    'values' => [
                        "item_{$dataType3['id']}" => 'a',
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType3, ['is_required' => 0],
                    )],
                ],
                array_merge([
                    "item_{$dataType3['id']}" => "{$dataType3['name']}は1桁から50桁の間で指定してください。",
                ], $notExistsEmailDataTypeErrors),
            ],
            'data_type=3のis_requiredが1で値が空' => [
                [
                    'values' => [
                        "item_{$dataType3['id']}" => '',
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType3, ['is_required' => 1],
                    )],
                ],
                array_merge([
                    "item_{$dataType3['id']}" => "{$dataType3['name']}は必ず指定してください。",
                ], $notExistsEmailDataTypeErrors),
            ],
            'data_type=3のis_requiredが1で値がnull' => [
                [
                    'values' => [
                        "item_{$dataType3['id']}" => null,
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType3, ['is_required' => 1],
                    )],
                ],
                array_merge([
                    "item_{$dataType3['id']}" => "{$dataType3['name']}は必ず指定してください。",
                ], $notExistsEmailDataTypeErrors),
            ],
            'data_type=3のis_reservation_itemが0' => [
                [
                    'values' => [
                        "item_{$dataType3['id']}" => '12345678901234567890123456789012345678901234567890',
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType3, ['is_reservation_item' => 0],
                    )],
                ],
                $notExistsEmailDataTypeErrors,
            ],
            'data_type=4の値がY-m-d形式ではない' => [
                [
                    'values' => [
                        "item_{$dataType4['id']}" => '20000101',
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType4, ['is_required' => 0],
                    )],
                ],
                array_merge([
                    "item_{$dataType4['id']}" => "{$dataType4['name']}はY-m-d形式で指定してください。",
                ], $notExistsEmailDataTypeErrors),
            ],
            'data_type=4のis_requiredが1で値が空' => [
                [
                    'values' => [
                        "item_{$dataType4['id']}" => '',
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType4, ['is_required' => 1],
                    )],
                ],
                array_merge([
                    "item_{$dataType4['id']}" => "{$dataType4['name']}は必ず指定してください。",
                ], $notExistsEmailDataTypeErrors),
            ],
            'data_type=4のis_requiredが1で値がnull' => [
                [
                    'values' => [
                        "item_{$dataType4['id']}" => null,
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType4, ['is_required' => 1],
                    )],
                ],
                array_merge([
                    "item_{$dataType4['id']}" => "{$dataType4['name']}は必ず指定してください。",
                ], $notExistsEmailDataTypeErrors),
            ],
            'data_type=4のis_reservation_itemが0' => [
                [
                    'values' => [
                        "item_{$dataType4['id']}" => '2000-01-01',
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType4, ['is_reservation_item' => 0],
                    )],
                ],
                $notExistsEmailDataTypeErrors,
            ],
            'data_type=5の値がH:i:s形式ではない' => [
                [
                    'values' => [
                        "item_{$dataType5['id']}" => '012050',
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType5, ['is_required' => 0],
                    )],
                ],
                array_merge([
                    "item_{$dataType5['id']}" => "{$dataType5['name']}はH:i:s形式で指定してください。",
                ], $notExistsEmailDataTypeErrors),
            ],
            'data_type=5のis_requiredが1で値が空' => [
                [
                    'values' => [
                        "item_{$dataType5['id']}" => '',
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType5, ['is_required' => 1],
                    )],
                ],
                array_merge([
                    "item_{$dataType5['id']}" => "{$dataType5['name']}は必ず指定してください。",
                ], $notExistsEmailDataTypeErrors),
            ],
            'data_type=5のis_requiredが1で値がnull' => [
                [
                    'values' => [
                        "item_{$dataType5['id']}" => null,
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType5, ['is_required' => 1],
                    )],
                ],
                array_merge([
                    "item_{$dataType5['id']}" => "{$dataType5['name']}は必ず指定してください。",
                ], $notExistsEmailDataTypeErrors),
            ],
            'data_type=5のis_reservation_itemが0' => [
                [
                    'values' => [
                        "item_{$dataType5['id']}" => '11:20:40',
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType5, ['is_reservation_item' => 0],
                    )],
                ],
                $notExistsEmailDataTypeErrors,
            ],
            'data_type=6の値がH:i:s形式ではない' => [
                [
                    'values' => [
                        "item_{$dataType6['id']}" => '012050',
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType6,
                        ['is_required' => 0],
                    )],
                ],
                array_merge([
                    "item_{$dataType6['id']}" => "{$dataType6['name']}はY-m-d\TH:i:s形式で指定してください。",
                ], $notExistsEmailDataTypeErrors),
            ],
            'data_type=6のis_requiredが1で値が空' => [
                [
                    'values' => [
                        "item_{$dataType6['id']}" => '',
                    ],
                    'base_customer_items' => [array_merge(
                        $baseCustomerItems[6],
                        ['is_required' => 1],
                    )],
                ],
                array_merge([
                    "item_{$dataType6['id']}" => "{$dataType6['name']}は必ず指定してください。",
                ], $notExistsEmailDataTypeErrors),
            ],
            'data_type=6のis_requiredが1で値がnull' => [
                [
                    'values' => [
                        "item_{$dataType6['id']}" => null,
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType6,
                        ['is_required' => 1],
                    )],
                ],
                array_merge([
                    "item_{$dataType6['id']}" => "{$dataType6['name']}は必ず指定してください。",
                ], $notExistsEmailDataTypeErrors),
            ],
            'data_type=6のis_reservation_itemが0' => [
                [
                    'values' => [
                        "item_{$dataType6['id']}" => '2021-01-01T11:20:40',
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType6,
                        ['is_reservation_item' => 0],
                    )],
                ],
                $notExistsEmailDataTypeErrors,
            ],
            'data_type=7の値が整数ではない' => [
                [
                    'values' => [
                        "item_{$dataType7['id']}" => 'a',
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType7,
                        ['is_required' => 0],
                    )],
                ],
                array_merge([
                    "item_{$dataType7['id']}" => "{$dataType7['name']}は整数で指定してください。",
                ], $notExistsEmailDataTypeErrors),
            ],
            'data_type=7の値が1未満' => [
                [
                    'values' => [
                        "item_{$dataType7['id']}" => 0,
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType7,
                        ['is_required' => 0],
                    )],
                ],
                array_merge([
                    "item_{$dataType7['id']}" => "{$dataType7['name']}には、1以上の数字を指定してください。",
                ], $notExistsEmailDataTypeErrors),
            ],
            'data_type=7の値が3を超える' => [
                [
                    'values' => [
                        "item_{$dataType7['id']}" => 4,
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType7,
                        ['is_required' => 0],
                    )],
                ],
                array_merge([
                    "item_{$dataType7['id']}" => "{$dataType7['name']}には、3以下の数字を指定してください。",
                ], $notExistsEmailDataTypeErrors),
            ],
            'data_type=7のis_requiredが1で値が空' => [
                [
                    'values' => [
                        "item_{$dataType7['id']}" => '',
                    ],
                    'base_customer_items' => [array_merge(
                        $baseCustomerItems[7],
                        ['is_required' => 1],
                    )],
                ],
                array_merge([
                    "item_{$dataType7['id']}" => "{$dataType7['name']}は必ず指定してください。",
                ], $notExistsEmailDataTypeErrors),
            ],
            'data_type=7のis_requiredが1で値がnull' => [
                [
                    'values' => [
                        "item_{$dataType7['id']}" => null,
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType7,
                        ['is_required' => 1],
                    )],
                ],
                array_merge([
                    "item_{$dataType7['id']}" => "{$dataType7['name']}は必ず指定してください。",
                ], $notExistsEmailDataTypeErrors),
            ],
            'data_type=7のis_reservation_itemが0' => [
                [
                    'values' => [
                        "item_{$dataType7['id']}" => 123,
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType7,
                        ['is_reservation_item' => 0],
                    )],
                ],
                $notExistsEmailDataTypeErrors,
            ],
            'data_type=8のis_requiredが0で値が100文字を超える' => [
                [
                    'values' => [
                        "item_{$dataType8['id']}" => str_repeat('0123456789', 10) . '0',
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType8, ['is_required' => 0],
                    )],
                ],
                array_merge([
                    "item_{$dataType8['id']}" => "{$dataType8['name']}は、100文字以下で指定してください。",
                ], $notExistsEmailDataTypeErrors),
            ],
            'data_type=8のis_requiredが1で値が空' => [
                [
                    'values' => [
                        "item_{$dataType8['id']}" => '',
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType8, ['is_required' => 1],
                    )],
                ],
                array_merge([
                    "item_{$dataType8['id']}" => "{$dataType8['name']}は必ず指定してください。",
                ], $notExistsEmailDataTypeErrors),
            ],
            'data_type=8のis_requiredが1で値がnull' => [
                [
                    'values' => [
                        "item_{$dataType8['id']}" => null,
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType8, ['is_required' => 1],
                    )],
                ],
                array_merge([
                    "item_{$dataType8['id']}" => "{$dataType8['name']}は必ず指定してください。",
                ], $notExistsEmailDataTypeErrors),
            ],
            'data_type=8のis_reservation_itemが0' => [
                [
                    'values' => [
                        "item_{$dataType8['id']}" => '01234567890123456789012345678901234567890123456789',
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType8, ['is_reservation_item' => 0],
                    )],
                ],
                $notExistsEmailDataTypeErrors,
            ],
            'data_type=9のis_requiredが0で値が11文字を超える' => [
                [
                    'values' => [
                        "item_{$dataType9['id']}" => str_repeat('0123456789', 1) . '01',
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType9, ['is_required' => 0],
                    )],
                ],
                array_merge([
                    "item_{$dataType9['id']}" => "{$dataType9['name']}は、10文字から、11文字の間で指定してください。",
                ], $notExistsEmailDataTypeErrors),
            ],
            'data_type=9のis_requiredが0で値が10文字未満' => [
                [
                    'values' => [
                        "item_{$dataType9['id']}" => str_repeat('012345678', 1),
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType9, ['is_required' => 0],
                    )],
                ],
                array_merge([
                    "item_{$dataType9['id']}" => "{$dataType9['name']}は、10文字から、11文字の間で指定してください。",
                ], $notExistsEmailDataTypeErrors),
            ],
            'data_type=9のis_requiredが1で値が空' => [
                [
                    'values' => [
                        "item_{$dataType9['id']}" => '',
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType9, ['is_required' => 1],
                    )],
                ],
                array_merge([
                    "item_{$dataType9['id']}" => "{$dataType9['name']}は必ず指定してください。",
                ], $notExistsEmailDataTypeErrors),
            ],
            'data_type=9のis_requiredが1で値がnull' => [
                [
                    'values' => [
                        "item_{$dataType9['id']}" => null,
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType9, ['is_required' => 1],
                    )],
                ],
                array_merge([
                    "item_{$dataType9['id']}" => "{$dataType9['name']}は必ず指定してください。",
                ], $notExistsEmailDataTypeErrors),
            ],
            'data_type=9のis_reservation_itemが0' => [
                [
                    'values' => [
                        "item_{$dataType9['id']}" => '012345678901',
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType9, ['is_reservation_item' => 0],
                    )],
                ],
                $notExistsEmailDataTypeErrors,
            ],
            'data_type=10のis_requiredが0で値が64文字を超える' => [
                [
                    'values' => [
                        "item_{$dataType10['id']}" => str_repeat('0123456789', 6) . '@a.ab',
                        "item_{$dataType10['id']}_confirm" => str_repeat('0123456789', 6) . '@a.ab',
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType10, ['is_required' => 0],
                    )],
                ],
                [
                    "item_{$dataType10['id']}" => "{$dataType10['name']}は、64文字以下で指定してください。",
                    "item_{$dataType10['id']}_confirm" => "{$dataType10['name']}確認用は、64文字以下で指定してください。",
                ],
            ],
            'data_type=10のis_requiredが0で値がメールアドレス形式でない' => [
                [
                    'values' => [
                        "item_{$dataType10['id']}" => str_repeat('0123456789', 5) . 'a.b',
                        "item_{$dataType10['id']}_confirm" => str_repeat('0123456789', 5) . 'a.b',
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType10, ['is_required' => 0],
                    )],
                ],
                [
                    "item_{$dataType10['id']}" => "{$dataType10['name']}には、有効なメールアドレスを指定してください。",
                    "item_{$dataType10['id']}_confirm" => "{$dataType10['name']}確認用には、有効なメールアドレスを指定してください。",
                ],
            ],
            'data_type=10のis_requiredが0で値が確認用メールアドレスの値と一致しない' => [
                [
                    'values' => [
                        "item_{$dataType10['id']}" => str_repeat('0123456789', 5) . '@a.b',
                        "item_{$dataType10['id']}_confirm" => str_repeat('0123456789', 5) . '@a.c',
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType10, ['is_required' => 0],
                    )],
                ],
                [
                    "item_{$dataType10['id']}_confirm" => "{$dataType10['name']}確認用と{$dataType10['name']}には同じ値を指定してください。",
                ],
            ],
            'data_type=10のis_requiredが1で値が空' => [
                [
                    'values' => [
                        "item_{$dataType10['id']}" => '',
                        "item_{$dataType10['id']}_confirm" => '',
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType10, ['is_required' => 1],
                    )],
                ],
                [
                    "item_{$dataType10['id']}" => "{$dataType10['name']}は必ず指定してください。",
                    "item_{$dataType10['id']}_confirm" => "{$dataType10['name']}確認用は必ず指定してください。",
                ],
            ],
            'data_type=10のis_requiredが1で値がnull' => [
                [
                    'values' => [
                        "item_{$dataType10['id']}" => null,
                        "item_{$dataType10['id']}_confirm" => null,
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType10, ['is_required' => 1],
                    )],
                ],
                [
                    "item_{$dataType10['id']}" => "{$dataType10['name']}は必ず指定してください。",
                    "item_{$dataType10['id']}_confirm" => "{$dataType10['name']}確認用は必ず指定してください。",
                ],
            ],
            'data_type=10のis_reservation_itemが0' => [
                [
                    'values' => [
                        "item_{$dataType10['id']}" => 'client@shareg.com',
                        "item_{$dataType10['id']}_confirm" => 'client@shareg.com',
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType10, ['is_reservation_item' => 0],
                    )],
                ],
                $notExistsEmailDataTypeErrors,
            ],
            'data_type=11のis_requiredが0で値が255文字を超える' => [
                [
                    'values' => [
                        "item_{$dataType11['id']}" => str_repeat('0123456789', 25) . '012345',
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType11, ['is_required' => 0],
                    )],
                ],
                array_merge([
                    "item_{$dataType11['id']}" => "{$dataType11['name']}は、255文字以下で指定してください。",
                ], $notExistsEmailDataTypeErrors),
            ],
            'data_type=11のis_requiredが1で値が空' => [
                [
                    'values' => [
                        "item_{$dataType11['id']}" => '',
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType11, ['is_required' => 1],
                    )],
                ],
                array_merge([
                    "item_{$dataType11['id']}" => "{$dataType11['name']}は必ず指定してください。",
                ], $notExistsEmailDataTypeErrors),
            ],
            'data_type=11のis_requiredが1で値がnull' => [
                [
                    'values' => [
                        "item_{$dataType11['id']}" => null,
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType11, ['is_required' => 1],
                    )],
                ],
                array_merge([
                    "item_{$dataType11['id']}" => "{$dataType11['name']}は必ず指定してください。",
                ], $notExistsEmailDataTypeErrors),
            ],
            'data_type=11のis_reservation_itemが0' => [
                [
                    'values' => [
                        "item_{$dataType11['id']}" => '01234567890123456789012345678901234567890123456789',
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType11, ['is_reservation_item' => 0],
                    )],
                ],
                $notExistsEmailDataTypeErrors,
            ],
            'data_type=12のis_requiredが0で値が255文字を超える' => [
                [
                    'values' => [
                        "item_{$dataType12['id']}" => str_repeat('0123456789', 25) . '012345',
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType12, ['is_required' => 0],
                    )],
                ],
                array_merge([
                    "item_{$dataType12['id']}" => "{$dataType12['name']}は、255文字以下で指定してください。",
                ], $notExistsEmailDataTypeErrors),
            ],
            'data_type=12のis_requiredが1で値が空' => [
                [
                    'values' => [
                        "item_{$dataType12['id']}" => '',
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType12, ['is_required' => 1],
                    )],
                ],
                array_merge([
                    "item_{$dataType12['id']}" => "{$dataType12['name']}は必ず指定してください。",
                ], $notExistsEmailDataTypeErrors),
            ],
            'data_type=12のis_requiredが1で値がnull' => [
                [
                    'values' => [
                        "item_{$dataType12['id']}" => null,
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType12, ['is_required' => 1],
                    )],
                ],
                array_merge([
                    "item_{$dataType12['id']}" => "{$dataType12['name']}は必ず指定してください。",
                ], $notExistsEmailDataTypeErrors),
            ],
            'data_type=12のis_reservation_itemが0' => [
                [
                    'values' => [
                        "item_{$dataType12['id']}" => '01234567890123456789012345678901234567890123456789',
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType12, ['is_reservation_item' => 0],
                    )],
                ],
                $notExistsEmailDataTypeErrors,
            ],
            'data_type=13の値がY-m-d形式ではない' => [
                [
                    'values' => [
                        "item_{$dataType13['id']}" => '20000101',
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType13, ['is_required' => 0],
                    )],
                ],
                array_merge([
                    "item_{$dataType13['id']}" => "{$dataType13['name']}はY-m-d形式で指定してください。",
                ], $notExistsEmailDataTypeErrors),
            ],
            'data_type=13のis_requiredが1で値が空' => [
                [
                    'values' => [
                        "item_{$dataType13['id']}" => '',
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType13, ['is_required' => 1],
                    )],
                ],
                array_merge([
                    "item_{$dataType13['id']}" => "{$dataType13['name']}は必ず指定してください。",
                ], $notExistsEmailDataTypeErrors),
            ],
            'data_type=13のis_requiredが1で値がnull' => [
                [
                    'values' => [
                        "item_{$dataType13['id']}" => null,
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType13, ['is_required' => 1],
                    )],
                ],
                array_merge([
                    "item_{$dataType13['id']}" => "{$dataType13['name']}は必ず指定してください。",
                ], $notExistsEmailDataTypeErrors),
            ],
            'data_type=13のis_reservation_itemが0' => [
                [
                    'values' => [
                        "item_{$dataType13['id']}" => '2000-01-01',
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType13, ['is_reservation_item' => 0],
                    )],
                ],
                $notExistsEmailDataTypeErrors,
            ],
            'data_type=14のis_requiredが0で値が正規表現にマッチしない' => [
                [
                    'values' => [
                        "item_{$dataType14['id']}" => '012345',
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType14, ['is_required' => 0],
                    )],
                ],
                array_merge([
                    "item_{$dataType14['id']}" => "{$dataType14['name']}には時刻を指定してください。",
                ], $notExistsEmailDataTypeErrors),
            ],
            'data_type=14のis_requiredが1で値が空' => [
                [
                    'values' => [
                        "item_{$dataType14['id']}" => '',
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType14, ['is_required' => 1],
                    )],
                ],
                array_merge([
                    "item_{$dataType14['id']}" => "{$dataType14['name']}は必ず指定してください。",
                ], $notExistsEmailDataTypeErrors),
            ],
            'data_type=14のis_requiredが1で値がnull' => [
                [
                    'values' => [
                        "item_{$dataType14['id']}" => null,
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType14, ['is_required' => 1],
                    )],
                ],
                array_merge([
                    "item_{$dataType14['id']}" => "{$dataType14['name']}は必ず指定してください。",
                ], $notExistsEmailDataTypeErrors),
            ],
            'data_type=14のis_reservation_itemが0' => [
                [
                    'values' => [
                        "item_{$dataType14['id']}" => '01234567890123456789012345678901234567890123456789',
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType14, ['is_reservation_item' => 0],
                    )],
                ],
                $notExistsEmailDataTypeErrors,
            ],
            'data_type=15のis_requiredが0で値が正規表現にマッチしない' => [
                [
                    'values' => [
                        "item_{$dataType15['id']}" => '012345',
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType15, ['is_required' => 0],
                    )],
                ],
                array_merge([
                    "item_{$dataType15['id']}" => "{$dataType15['name']}には時刻を指定してください。",
                ], $notExistsEmailDataTypeErrors),
            ],
            'data_type=15のis_requiredが1で値が空' => [
                [
                    'values' => [
                        "item_{$dataType15['id']}" => '',
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType15, ['is_required' => 1],
                    )],
                ],
                array_merge([
                    "item_{$dataType15['id']}" => "{$dataType15['name']}は必ず指定してください。",
                ], $notExistsEmailDataTypeErrors),
            ],
            'data_type=15のis_requiredが1で値がnull' => [
                [
                    'values' => [
                        "item_{$dataType15['id']}" => null,
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType15, ['is_required' => 1],
                    )],
                ],
                array_merge([
                    "item_{$dataType15['id']}" => "{$dataType15['name']}は必ず指定してください。",
                ], $notExistsEmailDataTypeErrors),
            ],
            'data_type=15のis_reservation_itemが0' => [
                [
                    'values' => [
                        "item_{$dataType15['id']}" => '01234567890123456789012345678901234567890123456789',
                    ],
                    'base_customer_items' => [array_merge(
                        $dataType15, ['is_reservation_item' => 0],
                    )],
                ],
                $notExistsEmailDataTypeErrors,
            ],
        ];
    }

    /**
     * @dataProvider createSaveReservationValidationProvider
     * @test
     */
    public function saveReservationData_異常系_validationError($input, $expect)
    {
        $response = $this->withSession([
            'booking_other.base_customer_items' => $input['base_customer_items'],
        ])->post('/booking/reservation/confirm', $input['values']);

        $response->assertStatus(302)
            ->assertSessionHasErrors($expect);
    }

    /** @test */
    public function saveReservationData_異常系_部屋の選択情報がセッションに存在しない()
    {
        $response = $this->post('/booking/reservation/confirm', [
            'email' => 'client@shareg.com', // メールアドレス
            'email_confirm' => 'client@shareg.com', // メールアドレス確認用
        ]);

        $response->assertStatus(200)
            ->assertViewIs('user.booking.other.search_panel')
            ->assertViewHas('notReserve', 1)
            ->assertViewHas('attentionMessage', '申し訳ありません。予期せぬエラーが発生しました。お時間をおいて再度お試しくださいませ。');
    }

    /** @test */
    public function saveReservationData_異常系_セッション有効切れ()
    {
        $paymentProvider = $this->createPaymentProvider();
        $input = $paymentProvider['現地決済・事前決済どちらも許可(事前決済)'][0];

        [
            'hotel' => $hotel,
            'hotelRoomTypes' => $hotelRoomTypes,
            'cancelPolicy' => $cancelPolicy,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $form = factory(Form::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'public_status' => 1,
            'is_sale_period' => 1,
            'sale_period_start' => Carbon::yesterday()->format('Y-m-d h:i:s'),
            'sale_period_end' => Carbon::tomorrow()->format('Y-m-d h:i:s'),
            'is_room_type' => 0,
            'room_type_ids' => [],
            'cancel_policy_id' => $cancelPolicy->id,
            'is_deadline' => 0,
            'is_special_price' => 0,
            'prepay' => $input['prepay'],
        ]);
        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);

        $lpUrlParam = $lp->url_param;
        $baseCustomerItems = $this->_getDummyBaseCustomerItems();

        $sessionTimeoutMinute = 15;
        $sessionTime = Carbon::now()->subMinutes($sessionTimeoutMinute)->format('Y-m-d H:i');
        $response = $this->withSession([
            'booking_other.base_info.hotel_id' => $hotel->id,
            'booking_other.base_info.url_param' => $lpUrlParam,
            'booking_other.base_customer_items' => $baseCustomerItems,
            'booking_other.reservation_info.session_time' => $sessionTime,
        ])->post('/booking/reservation/confirm', array_merge([
            'item_8' => 'サンプル太郎', // 氏名
            'item_9' => '00000000000', // 電話番号
            'item_10' => 'client@shareg.com', // メールアドレス
            'item_10_confirm' => 'client@shareg.com', // メールアドレス確認用
            'item_11' => '日本', // 住所
        ], $input['cardInfo']));


        $url = config('app.url');
        $response->assertStatus(302)
            ->assertHeader('location', "{$url}/page/reservation/search_panel?url_param={$lpUrlParam}")
            ->assertSessionHas('error', '一定時間操作がありませんでした。画面を再読み込みして再度お試しください。');
    }

    /** @test */
    public function saveReservationData_異常系_部屋が未選択()
    {
        $paymentProvider = $this->createPaymentProvider();
        $input = $paymentProvider['現地決済・事前決済どちらも許可(事前決済)'][0];

        [
            'hotel' => $hotel,
            'hotelRoomTypes' => $hotelRoomTypes,
            'cancelPolicy' => $cancelPolicy,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $form = factory(Form::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'public_status' => 1,
            'is_sale_period' => 1,
            'sale_period_start' => Carbon::yesterday()->format('Y-m-d h:i:s'),
            'sale_period_end' => Carbon::tomorrow()->format('Y-m-d h:i:s'),
            'is_room_type' => 0,
            'room_type_ids' => [],
            'cancel_policy_id' => $cancelPolicy->id,
            'is_deadline' => 0,
            'is_special_price' => 0,
            'prepay' => $input['prepay'],
        ]);
        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);

        $lpUrlParam = $lp->url_param;
        $baseCustomerItems = $this->_getDummyBaseCustomerItems();

        $response = $this->withSession([
            'booking_other.base_info.hotel_id' => $hotel->id,
            'booking_other.base_info.url_param' => $lpUrlParam,
            'booking_other.base_customer_items' => $baseCustomerItems,
            'booking_other.selected_rooms' => [],
            'booking_other.reservation_info.session_time' => Carbon::now()->format('Y-m-d H:i'),
        ])->post('/booking/reservation/confirm', array_merge([
            'item_8' => 'サンプル太郎', // 氏名
            'item_9' => '00000000000', // 電話番号
            'item_10' => 'client@shareg.com', // メールアドレス
            'item_10_confirm' => 'client@shareg.com', // メールアドレス確認用
            'item_11' => '日本', // 住所
        ], $input['cardInfo']));

        $response->assertStatus(302)
            ->assertSessionHas('error', '予期せぬエラーが発生しました。');
    }

    /** @test */
    public function saveReservationData_異常系_予約直前に予約枠がis_availableが0で更新された()
    {
        $paymentProvider = $this->createPaymentProvider();
        $input = $paymentProvider['現地決済・事前決済どちらも許可(事前決済)'][0];

        [
            'hotel' => $hotel,
            'hotelRoomTypes' => $hotelRoomTypes,
            'cancelPolicy' => $cancelPolicy,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $form = factory(Form::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'public_status' => 1,
            'is_sale_period' => 1,
            'sale_period_start' => Carbon::yesterday()->format('Y-m-d h:i:s'),
            'sale_period_end' => Carbon::tomorrow()->format('Y-m-d h:i:s'),
            'is_room_type' => 0,
            'room_type_ids' => [],
            'cancel_policy_id' => $cancelPolicy->id,
            'is_deadline' => 0,
            'is_special_price' => 0,
            'prepay' => $input['prepay'],
        ]);
        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);

        // 予約枠の作成
        $roomTypeId = $hotelRoomTypes[0]->id;
        $roomTypeName = $hotelRoomTypes[0]->name;
        $tommorow = Carbon::tomorrow()->format('Y-m-d');
        $reservationBlock = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 0,
            'date' => $tommorow,
            'price' => 1000,
            'start_hour' => 20,
            'start_minute' => 0,
            'end_hour' => 22,
            'end_minute' => 30,
        ]);

        $reserveBlockId = $reservationBlock->id;
        $lpUrlParam = $lp->url_param;
        $baseCustomerItems = $this->_getDummyBaseCustomerItems();

        $response = $this->withSession([
            'booking_other.base_info.hotel_id' => $hotel->id,
            'booking_other.base_info.url_param' => $lpUrlParam,
            'booking_other.base_customer_items' => $baseCustomerItems,
            'booking_other.selected_rooms' => [
                [
                    'reservation_block_id' => $reserveBlockId,
                    'room_type_id' => $reservationBlock->room_type_id,
                    'room_name' => $roomTypeName,
                    'amount' => $reservationBlock->price,
                    'date' => $reservationBlock->date,
                    'start_time' => $reservationBlock->getStartTime(),
                    'end_time' => $reservationBlock->getEndTime(),
                    'person_num' => 2,
                ],
            ],
            'booking_other.room_amount' => [
                'sum' => $reservationBlock->price,
            ],
            'booking_other.reservation_info.session_time' => Carbon::now()->format('Y-m-d H:i'),
        ])->post('/booking/reservation/confirm', array_merge([
            'item_8' => 'サンプル太郎', // 氏名
            'item_9' => '00000000000', // 電話番号
            'item_10' => 'client@shareg.com', // メールアドレス
            'item_10_confirm' => 'client@shareg.com', // メールアドレス確認用
            'item_11' => '日本', // 住所
        ], $input['cardInfo']));

        $response->assertStatus(302)
            ->assertSessionHas('error', '申し訳ございません、ご予約のお手続き中にご選択された予約枠が満室となりました。大変恐れ入りますが、再度日時をご選択くださいませ。');
    }

    /** @test */
    public function saveReservationData_異常系_予約直前に料金が0で更新された()
    {
        $paymentProvider = $this->createPaymentProvider();
        $input = $paymentProvider['現地決済・事前決済どちらも許可(事前決済)'][0];

        [
            'hotel' => $hotel,
            'hotelRoomTypes' => $hotelRoomTypes,
            'cancelPolicy' => $cancelPolicy,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $form = factory(Form::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'public_status' => 1,
            'is_sale_period' => 1,
            'sale_period_start' => Carbon::yesterday()->format('Y-m-d h:i:s'),
            'sale_period_end' => Carbon::tomorrow()->format('Y-m-d h:i:s'),
            'is_room_type' => 0,
            'room_type_ids' => [],
            'cancel_policy_id' => $cancelPolicy->id,
            'is_deadline' => 0,
            'is_special_price' => 0,
            'prepay' => $input['prepay'],
        ]);
        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);

        // 予約枠の作成
        $roomTypeId = $hotelRoomTypes[0]->id;
        $roomTypeName = $hotelRoomTypes[0]->name;
        $tommorow = Carbon::tomorrow()->format('Y-m-d');
        $reservationBlock = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 1,
            'date' => $tommorow,
            'price' => 0,
            'start_hour' => 20,
            'start_minute' => 0,
            'end_hour' => 22,
            'end_minute' => 30,
        ]);

        $reserveBlockId = $reservationBlock->id;
        $lpUrlParam = $lp->url_param;
        $baseCustomerItems = $this->_getDummyBaseCustomerItems();

        $response = $this->withSession([
            'booking_other.base_info.hotel_id' => $hotel->id,
            'booking_other.base_info.url_param' => $lpUrlParam,
            'booking_other.base_customer_items' => $baseCustomerItems,
            'booking_other.selected_rooms' => [
                [
                    'reservation_block_id' => $reserveBlockId,
                    'room_type_id' => $reservationBlock->room_type_id,
                    'room_name' => $roomTypeName,
                    'amount' => $reservationBlock->price,
                    'date' => $reservationBlock->date,
                    'start_time' => $reservationBlock->getStartTime(),
                    'end_time' => $reservationBlock->getEndTime(),
                    'person_num' => 2,
                ],
            ],
            'booking_other.room_amount' => [
                'sum' => $reservationBlock->price,
            ],
            'booking_other.reservation_info.session_time' => Carbon::now()->format('Y-m-d H:i'),
        ])->post('/booking/reservation/confirm', array_merge([
            'item_8' => 'サンプル太郎', // 氏名
            'item_9' => '00000000000', // 電話番号
            'item_10' => 'client@shareg.com', // メールアドレス
            'item_10_confirm' => 'client@shareg.com', // メールアドレス確認用
            'item_11' => '日本', // 住所
        ], $input['cardInfo']));

        $response->assertStatus(302)
            ->assertSessionHas('error', '申し訳ございません、ご予約のお手続き中にご選択されたお部屋情報が更新されました。大変恐れ入りますが、再度お部屋をご選択くださいませ。');
    }

    /** @test */
    public function saveReservationData_異常系_Stripeによる事前決済に失敗した()
    {
        $paymentProvider = $this->createPaymentProvider();
        $input = $paymentProvider['現地決済・事前決済どちらも許可(事前決済)'][0];

        [
            'hotel' => $hotel,
            'hotelRoomTypes' => $hotelRoomTypes,
            'cancelPolicy' => $cancelPolicy,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $form = factory(Form::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'public_status' => 1,
            'is_sale_period' => 1,
            'sale_period_start' => Carbon::yesterday()->format('Y-m-d h:i:s'),
            'sale_period_end' => Carbon::tomorrow()->format('Y-m-d h:i:s'),
            'is_room_type' => 0,
            'room_type_ids' => [],
            'cancel_policy_id' => $cancelPolicy->id,
            'is_deadline' => 0,
            'is_special_price' => 0,
            'prepay' => $input['prepay'],
        ]);
        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);

        // 予約枠の作成
        $roomTypeId = $hotelRoomTypes[0]->id;
        $roomTypeName = $hotelRoomTypes[0]->name;
        $tommorow = Carbon::tomorrow()->format('Y-m-d');
        $reservationBlock = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 1,
            'date' => $tommorow,
            'price' => 1000,
            'start_hour' => 20,
            'start_minute' => 0,
            'end_hour' => 22,
            'end_minute' => 30,
        ]);

        $reserveBlockId = $reservationBlock->id;
        $lpUrlParam = $lp->url_param;
        $baseCustomerItems = $this->_getDummyBaseCustomerItems();

        $this->_createMockApiClientDoRequest();
        $stripeErrorMessage = 'Stripe決済用Mockのエラーメッセージです。';
        $this->_createMockStripePrePay($stripeErrorMessage);

        $response = $this->withSession([
            'booking_other.base_info.hotel_id' => $hotel->id,
            'booking_other.base_info.url_param' => $lpUrlParam,
            'booking_other.base_customer_items' => $baseCustomerItems,
            'booking_other.selected_rooms' => [
                [
                    'reservation_block_id' => $reserveBlockId,
                    'room_type_id' => $reservationBlock->room_type_id,
                    'room_name' => $roomTypeName,
                    'amount' => $reservationBlock->price,
                    'date' => $reservationBlock->date,
                    'start_time' => $reservationBlock->getStartTime(),
                    'end_time' => $reservationBlock->getEndTime(),
                    'person_num' => 2,
                ],
            ],
            'booking_other.room_amount' => [
                'sum' => $reservationBlock->price,
            ],
            'booking_other.reservation_info.session_time' => Carbon::now()->format('Y-m-d H:i'),
        ])->post('/booking/reservation/confirm', array_merge([
            'item_8' => 'サンプル太郎', // 氏名
            'item_9' => '00000000000', // 電話番号
            'item_10' => 'client@shareg.com', // メールアドレス
            'item_10_confirm' => 'client@shareg.com', // メールアドレス確認用
            'item_11' => '日本', // 住所
        ], $input['cardInfo']));

        $response->assertStatus(302)
            ->assertSessionHas('error', $stripeErrorMessage);
    }

    /** @test */
    public function saveReservationData_異常系_予約入力項目の保存に失敗した()
    {
        $paymentProvider = $this->createPaymentProvider();
        $input = $paymentProvider['現地決済・事前決済どちらも許可(事前決済)'][0];

        [
            'hotel' => $hotel,
            'hotelRoomTypes' => $hotelRoomTypes,
            'cancelPolicy' => $cancelPolicy,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $form = factory(Form::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'public_status' => 1,
            'is_sale_period' => 1,
            'sale_period_start' => Carbon::yesterday()->format('Y-m-d h:i:s'),
            'sale_period_end' => Carbon::tomorrow()->format('Y-m-d h:i:s'),
            'is_room_type' => 0,
            'room_type_ids' => [],
            'cancel_policy_id' => $cancelPolicy->id,
            'is_deadline' => 0,
            'is_special_price' => 0,
            'prepay' => $input['prepay'],
        ]);
        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);

        // 予約枠の作成
        $roomTypeId = $hotelRoomTypes[0]->id;
        $roomTypeName = $hotelRoomTypes[0]->name;
        $tommorow = Carbon::tomorrow()->format('Y-m-d');
        $reservationBlock = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 1,
            'date' => $tommorow,
            'price' => 1000,
            'start_hour' => 20,
            'start_minute' => 0,
            'end_hour' => 22,
            'end_minute' => 30,
        ]);

        $reserveBlockId = $reservationBlock->id;
        $lpUrlParam = $lp->url_param;
        $baseCustomerItems = $this->_getDummyBaseCustomerItems();

        $this->_createMockApiClientDoRequest();
        $this->_createMockStripePrePay();
        $this->_createMockSaveBaseCustomerItemValuesFailure();

        $response = $this->withSession([
            'booking_other.base_info.hotel_id' => $hotel->id,
            'booking_other.base_info.url_param' => $lpUrlParam,
            'booking_other.base_customer_items' => $baseCustomerItems,
            'booking_other.selected_rooms' => [
                [
                    'reservation_block_id' => $reserveBlockId,
                    'room_type_id' => $reservationBlock->room_type_id,
                    'room_name' => $roomTypeName,
                    'amount' => $reservationBlock->price,
                    'date' => $reservationBlock->date,
                    'start_time' => $reservationBlock->getStartTime(),
                    'end_time' => $reservationBlock->getEndTime(),
                    'person_num' => 2,
                ],
            ],
            'booking_other.room_amount' => [
                'sum' => $reservationBlock->price,
            ],
            'booking_other.reservation_info.session_time' => Carbon::now()->format('Y-m-d H:i'),
        ])->post('/booking/reservation/confirm', array_merge([
            'item_8' => 'サンプル太郎', // 氏名
            'item_9' => '00000000000', // 電話番号
            'item_10' => 'client@shareg.com', // メールアドレス
            'item_10_confirm' => 'client@shareg.com', // メールアドレス確認用
            'item_11' => '日本', // 住所
        ], $input['cardInfo']));

        $response->assertStatus(302)
            ->assertSessionHas('error', '予期せぬエラーが発生しました。恐れ入りますが、お時間をおいて再度お試しくださいませ。');
    }

    /** @test */
    public function saveReservationData_異常系_CRMとの予約データの同期に失敗した()
    {
        $paymentProvider = $this->createPaymentProvider();
        $input = $paymentProvider['現地決済・事前決済どちらも許可(事前決済)'][0];

        [
            'hotel' => $hotel,
            'hotelRoomTypes' => $hotelRoomTypes,
            'cancelPolicy' => $cancelPolicy,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $form = factory(Form::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'public_status' => 1,
            'is_sale_period' => 1,
            'sale_period_start' => Carbon::yesterday()->format('Y-m-d h:i:s'),
            'sale_period_end' => Carbon::tomorrow()->format('Y-m-d h:i:s'),
            'is_room_type' => 0,
            'room_type_ids' => [],
            'cancel_policy_id' => $cancelPolicy->id,
            'is_deadline' => 0,
            'is_special_price' => 0,
            'prepay' => $input['prepay'],
        ]);
        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);

        // 予約枠の作成
        $roomTypeId = $hotelRoomTypes[0]->id;
        $roomTypeName = $hotelRoomTypes[0]->name;
        $tommorow = Carbon::tomorrow()->format('Y-m-d');
        $reservationBlock = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 1,
            'date' => $tommorow,
            'price' => 1000,
            'start_hour' => 20,
            'start_minute' => 0,
            'end_hour' => 22,
            'end_minute' => 30,
        ]);

        $reserveBlockId = $reservationBlock->id;
        $lpUrlParam = $lp->url_param;
        $baseCustomerItems = $this->_getDummyBaseCustomerItems();

        $this->_createMockApiClientDoRequest();
        $this->_createMockStripePrePay();
        $this->_createMockSavePmsReservationDataFailure();

        $response = $this->withSession([
            'booking_other.base_info.hotel_id' => $hotel->id,
            'booking_other.base_info.url_param' => $lpUrlParam,
            'booking_other.base_customer_items' => $baseCustomerItems,
            'booking_other.selected_rooms' => [
                [
                    'reservation_block_id' => $reserveBlockId,
                    'room_type_id' => $reservationBlock->room_type_id,
                    'room_name' => $roomTypeName,
                    'amount' => $reservationBlock->price,
                    'date' => $reservationBlock->date,
                    'start_time' => $reservationBlock->getStartTime(),
                    'end_time' => $reservationBlock->getEndTime(),
                    'person_num' => 2,
                ],
            ],
            'booking_other.room_amount' => [
                'sum' => $reservationBlock->price,
            ],
            'booking_other.reservation_info.session_time' => Carbon::now()->format('Y-m-d H:i'),
        ])->post('/booking/reservation/confirm', array_merge([
            'item_8' => 'サンプル太郎', // 氏名
            'item_9' => '00000000000', // 電話番号
            'item_10' => 'client@shareg.com', // メールアドレス
            'item_10_confirm' => 'client@shareg.com', // メールアドレス確認用
            'item_11' => '日本', // 住所
        ], $input['cardInfo']));

        $response->assertStatus(302)
            ->assertSessionHas('error', '予期せぬエラーが発生しました。恐れ入りますが、お時間をおいて再度お試しくださいませ。');
    }

    /** @test */
    public function updateReservationData_正常系_1部屋()
    {
        $paymentProvider = $this->createPaymentProvider();
        $input = $paymentProvider['現地決済・事前決済どちらも許可(事前決済)'][0];

        [
            'hotel' => $hotel,
            'hotelRoomTypes' => $hotelRoomTypes,
            'cancelPolicy' => $cancelPolicy,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $form = factory(Form::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'public_status' => 1,
            'is_sale_period' => 1,
            'sale_period_start' => Carbon::yesterday()->format('Y-m-d h:i:s'),
            'sale_period_end' => Carbon::tomorrow()->format('Y-m-d h:i:s'),
            'is_room_type' => 0,
            'room_type_ids' => [],
            'cancel_policy_id' => $cancelPolicy->id,
            'is_deadline' => 0,
            'is_special_price' => 0,
            'prepay' => $input['prepay'],
        ]);
        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);

        // 予約枠の作成
        $roomTypeId = $hotelRoomTypes[0]->id;
        $roomTypeName = $hotelRoomTypes[0]->name;
        $tommorow = Carbon::tomorrow()->format('Y-m-d');

        // 更新前の予約枠
        $reservationBlock = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 1,
            'date' => $tommorow,
            'price' => 1000,
            'start_hour' => 20,
            'start_minute' => 0,
            'end_hour' => 22,
            'end_minute' => 30,
        ]);

        // 予約の作成
        $reservation = $this->_予約の作成($reservationBlock, $hotel);

        // 更新後の予約枠
        $reservationBlock2 = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 1,
            'date' => $tommorow,
            'price' => 1000,
            'start_hour' => 23,
            'start_minute' => 0,
            'end_hour' => 23,
            'end_minute' => 30,
        ]);

        $reserveBlockId = $reservationBlock2->id;
        $lpUrlParam = $lp->url_param;
        $baseCustomerItems = $this->_getDummyBaseCustomerItems();

        $this->_createMockApiClientDoRequest();
        $this->_createMockStripePrePay();

        $selectedRooms = [
            [
                'reservation_block_id' => $reserveBlockId,
                'room_type_id' => $reservationBlock2->room_type_id,
                'room_name' => $roomTypeName,
                'amount' => $reservationBlock2->price,
                'date' => $reservationBlock2->date,
                'start_time' => $reservationBlock2->getStartTime(),
                'end_time' => $reservationBlock2->getEndTime(),
                'person_num' => 2,
            ],
        ];
        $response = $this->withSession([
            'booking_other.base_info.hotel_id' => $hotel->id,
            'booking_other.base_info.url_param' => $lpUrlParam,
            'booking_other.base_customer_items' => $baseCustomerItems,
            'booking_other.selected_rooms' => $selectedRooms,
            'booking_other.room_amount' => [
                'sum' => $reservationBlock2->price,
            ],
            'booking_other.reservation_info.session_time' => Carbon::now()->format('Y-m-d H:i'),
            'booking_confirm.change_info.reservation_id' => $reservation->id,
        ])->post('/booking/reservation/update', array_merge([
            'item_8' => 'サンプル太郎', // 氏名
            'item_9' => '00000000000', // 電話番号
            'item_10' => 'client@shareg.com', // メールアドレス
            'item_10_confirm' => 'client@shareg.com', // メールアドレス確認用
            'item_11' => '日本', // 住所
        ], $input['cardInfo']));

        $response->assertStatus(200)
            ->assertViewIs('user.booking.other.complete')
            ->assertSessionDoesntHaveErrors()
            ->assertSessionMissing('booking_other')
            ->assertSeeInOrder([
                '予約が完了しました！',
                'ご入力のメールアドレスに確認メールをお送りしました。予約情報はそちらのメールをご確認ください。',
                '予約情報確認',
            ]);

        // 1部屋分のレコードがあることをチェック
        $this->assertDatabaseHas('reserved_reservation_blocks', [
            'reservation_id' => $reservation->id,
            'reservation_block_id' => $reserveBlockId,
            'person_num' => $selectedRooms[0]['person_num'],
            'price' => $reservationBlock2->price,
            'date' => $reservationBlock2->date,
            'start_hour' => $reservationBlock2->start_hour,
            'start_minute' => $reservationBlock2->start_minute,
            'end_hour' => $reservationBlock2->end_hour,
            'end_minute' => $reservationBlock2->end_minute,
            'deleted_at' => null,
        ]);
        // 更新前の予約に紐づく、予約済みの予約枠はソフトデリートされている
        $this->assertSoftDeleted('reserved_reservation_blocks', [
            'reservation_id' => $reservation->id,
            'reservation_block_id' => $reservationBlock->id,
            'price' => $reservationBlock->price,
            'date' => $reservationBlock->date,
            'start_hour' => $reservationBlock->start_hour,
            'start_minute' => $reservationBlock->start_minute,
            'end_hour' => $reservationBlock->end_hour,
            'end_minute' => $reservationBlock->end_minute,
        ]);
    }

    /** @test */
    public function updateReservationData_正常系_複数部屋()
    {
        $paymentProvider = $this->createPaymentProvider();
        $input = $paymentProvider['現地決済・事前決済どちらも許可(事前決済)'][0];

        [
            'hotel' => $hotel,
            'hotelRoomTypes' => $hotelRoomTypes,
            'cancelPolicy' => $cancelPolicy,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $form = factory(Form::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'public_status' => 1,
            'is_sale_period' => 1,
            'sale_period_start' => Carbon::yesterday()->format('Y-m-d h:i:s'),
            'sale_period_end' => Carbon::tomorrow()->format('Y-m-d h:i:s'),
            'is_room_type' => 0,
            'room_type_ids' => [],
            'cancel_policy_id' => $cancelPolicy->id,
            'is_deadline' => 0,
            'is_special_price' => 0,
            'prepay' => $input['prepay'],
        ]);
        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);

        // 予約枠の作成
        $roomTypeId = $hotelRoomTypes[0]->id;
        $roomTypeName = $hotelRoomTypes[0]->name;
        $tommorow = Carbon::tomorrow()->format('Y-m-d');
        $reservationBlock = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 1,
            'date' => $tommorow,
            'price' => 1000,
            'start_hour' => 20,
            'start_minute' => 0,
            'end_hour' => 22,
            'end_minute' => 30,
            'room_num' => $hotelRoomTypes[0]->room_num,
        ]);

        // 予約の作成
        $reservation = $this->_予約の作成($reservationBlock, $hotel);

        $reserveBlockId = $reservationBlock->id;
        $lpUrlParam = $lp->url_param;
        $baseCustomerItems = $this->_getDummyBaseCustomerItems();

        $this->_createMockApiClientDoRequest();
        $this->_createMockStripePrePay();

        $selectedRoom = [
            'reservation_block_id' => $reserveBlockId,
            'room_type_id' => $reservationBlock->room_type_id,
            'room_name' => $roomTypeName,
            'amount' => $reservationBlock->price,
            'date' => $reservationBlock->date,
            'start_time' => $reservationBlock->getStartTime(),
            'end_time' => $reservationBlock->getEndTime(),
            'person_num' => 2,
        ];
        $selectedRooms = [
            array_merge($selectedRoom, ['person_num' => 2]),
            array_merge($selectedRoom, ['person_num' => 1]),
        ];
        $roomAmountSum = $reservationBlock->price * count($selectedRooms);

        $response = $this->withSession([
            'booking_other.base_info.hotel_id' => $hotel->id,
            'booking_other.base_info.url_param' => $lpUrlParam,
            'booking_other.base_customer_items' => $baseCustomerItems,
            'booking_other.selected_rooms' => $selectedRooms,
            'booking_other.room_amount' => [
                'sum' => $roomAmountSum,
            ],
            'booking_other.reservation_info.session_time' => Carbon::now()->format('Y-m-d H:i'),
            'booking_confirm.change_info.reservation_id' => $reservation->id,
        ])->post('/booking/reservation/update', array_merge([
            'item_8' => 'サンプル太郎', // 氏名
            'item_9' => '00000000000', // 電話番号
            'item_10' => 'client@shareg.com', // メールアドレス
            'item_10_confirm' => 'client@shareg.com', // メールアドレス確認用
            'item_11' => '日本', // 住所
        ], $input['cardInfo']));

        $response->assertStatus(200)
            ->assertViewIs('user.booking.other.complete')
            ->assertSessionDoesntHaveErrors()
            ->assertSessionMissing('booking_other')
            ->assertSeeInOrder([
                '予約が完了しました！',
                'ご入力のメールアドレスに確認メールをお送りしました。予約情報はそちらのメールをご確認ください。',
                '予約情報確認',
            ]);

        // 2部屋分のレコードがあることをチェック
        $this->assertDatabaseHas('reserved_reservation_blocks', [
            'reservation_id' => $reservation->id,
            'reservation_block_id' => $reserveBlockId,
            'person_num' => $selectedRooms[0]['person_num'],
            'price' => $reservationBlock->price,
            'date' => $reservationBlock->date,
            'start_hour' => $reservationBlock->start_hour,
            'start_minute' => $reservationBlock->start_minute,
            'end_hour' => $reservationBlock->end_hour,
            'end_minute' => $reservationBlock->end_minute,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('reserved_reservation_blocks', [
            'reservation_id' => $reservation->id,
            'reservation_block_id' => $reserveBlockId,
            'person_num' => $selectedRooms[1]['person_num'],
            'price' => $reservationBlock->price,
            'date' => $reservationBlock->date,
            'start_hour' => $reservationBlock->start_hour,
            'start_minute' => $reservationBlock->start_minute,
            'end_hour' => $reservationBlock->end_hour,
            'end_minute' => $reservationBlock->end_minute,
            'deleted_at' => null,
        ]);
        // 更新前の予約に紐づく、予約済みの予約枠はソフトデリートされている
        $this->assertSoftDeleted('reserved_reservation_blocks', [
            'reservation_id' => $reservation->id,
            'reservation_block_id' => $reserveBlockId,
            'price' => $reservationBlock->price,
            'date' => $reservationBlock->date,
            'start_hour' => $reservationBlock->start_hour,
            'start_minute' => $reservationBlock->start_minute,
            'end_hour' => $reservationBlock->end_hour,
            'end_minute' => $reservationBlock->end_minute,
        ]);
    }

    /**
     * @test
     */
    public function updateReservationData_異常系_validationError()
    {
        $this->assertTrue(true, 'saveReservationDataと同じ内容なので省略。');
    }

    /** @test */
    public function updateReservationData_異常系_部屋の選択情報がセッションに存在しない()
    {
        $response = $this->post('/booking/reservation/update', [
            'email' => 'client@shareg.com', // メールアドレス
            'email_confirm' => 'client@shareg.com', // メールアドレス確認用
        ]);

        $response->assertStatus(200)
            ->assertViewIs('user.booking.other.search_panel')
            ->assertViewHas('notReserve', 1)
            ->assertViewHas('attentionMessage', '申し訳ありません。予期せぬエラーが発生しました。お時間をおいて再度お試しくださいませ。');
    }

    /** @test */
    public function updateReservationData_異常系_セッション有効切れ()
    {
        $paymentProvider = $this->createPaymentProvider();
        $input = $paymentProvider['現地決済・事前決済どちらも許可(事前決済)'][0];

        [
            'hotel' => $hotel,
            'hotelRoomTypes' => $hotelRoomTypes,
            'cancelPolicy' => $cancelPolicy,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $form = factory(Form::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'public_status' => 1,
            'is_sale_period' => 1,
            'sale_period_start' => Carbon::yesterday()->format('Y-m-d h:i:s'),
            'sale_period_end' => Carbon::tomorrow()->format('Y-m-d h:i:s'),
            'is_room_type' => 0,
            'room_type_ids' => [],
            'cancel_policy_id' => $cancelPolicy->id,
            'is_deadline' => 0,
            'is_special_price' => 0,
            'prepay' => $input['prepay'],
        ]);
        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);

        $lpUrlParam = $lp->url_param;
        $baseCustomerItems = $this->_getDummyBaseCustomerItems();

        $sessionTimeoutMinute = 15;
        $sessionTime = Carbon::now()->subMinutes($sessionTimeoutMinute)->format('Y-m-d H:i');
        $response = $this->withSession([
            'booking_other.base_info.hotel_id' => $hotel->id,
            'booking_other.base_info.url_param' => $lpUrlParam,
            'booking_other.base_customer_items' => $baseCustomerItems,
            'booking_other.reservation_info.session_time' => $sessionTime,
        ])->post('/booking/reservation/update', array_merge([
            'item_8' => 'サンプル太郎', // 氏名
            'item_9' => '00000000000', // 電話番号
            'item_10' => 'client@shareg.com', // メールアドレス
            'item_10_confirm' => 'client@shareg.com', // メールアドレス確認用
            'item_11' => '日本', // 住所
        ], $input['cardInfo']));


        $url = config('app.url');
        $response->assertStatus(302)
            ->assertHeader('location', "{$url}/page/reservation/search_panel?url_param={$lpUrlParam}")
            ->assertSessionHas('error', '一定時間操作がありませんでした。画面を再読み込みして再度お試しください。');
    }

    /** @test */
    public function updateReservationData_異常系_部屋が未選択()
    {
        $paymentProvider = $this->createPaymentProvider();
        $input = $paymentProvider['現地決済・事前決済どちらも許可(事前決済)'][0];

        [
            'hotel' => $hotel,
            'hotelRoomTypes' => $hotelRoomTypes,
            'cancelPolicy' => $cancelPolicy,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $form = factory(Form::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'public_status' => 1,
            'is_sale_period' => 1,
            'sale_period_start' => Carbon::yesterday()->format('Y-m-d h:i:s'),
            'sale_period_end' => Carbon::tomorrow()->format('Y-m-d h:i:s'),
            'is_room_type' => 0,
            'room_type_ids' => [],
            'cancel_policy_id' => $cancelPolicy->id,
            'is_deadline' => 0,
            'is_special_price' => 0,
            'prepay' => $input['prepay'],
        ]);
        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);

        $lpUrlParam = $lp->url_param;
        $baseCustomerItems = $this->_getDummyBaseCustomerItems();

        $response = $this->withSession([
            'booking_other.base_info.hotel_id' => $hotel->id,
            'booking_other.base_info.url_param' => $lpUrlParam,
            'booking_other.base_customer_items' => $baseCustomerItems,
            'booking_other.selected_rooms' => [],
            'booking_other.reservation_info.session_time' => Carbon::now()->format('Y-m-d H:i'),
        ])->post('/booking/reservation/update', array_merge([
            'item_8' => 'サンプル太郎', // 氏名
            'item_9' => '00000000000', // 電話番号
            'item_10' => 'client@shareg.com', // メールアドレス
            'item_10_confirm' => 'client@shareg.com', // メールアドレス確認用
            'item_11' => '日本', // 住所
        ], $input['cardInfo']));

        $response->assertStatus(302)
            ->assertSessionHas('error', '予期せぬエラーが発生しました。');
    }

    /** @test */
    public function updateReservationData_異常系_予約直前に予約枠がis_availableが0で更新された()
    {
        $paymentProvider = $this->createPaymentProvider();
        $input = $paymentProvider['現地決済・事前決済どちらも許可(事前決済)'][0];

        [
            'hotel' => $hotel,
            'hotelRoomTypes' => $hotelRoomTypes,
            'cancelPolicy' => $cancelPolicy,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $form = factory(Form::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'public_status' => 1,
            'is_sale_period' => 1,
            'sale_period_start' => Carbon::yesterday()->format('Y-m-d h:i:s'),
            'sale_period_end' => Carbon::tomorrow()->format('Y-m-d h:i:s'),
            'is_room_type' => 0,
            'room_type_ids' => [],
            'cancel_policy_id' => $cancelPolicy->id,
            'is_deadline' => 0,
            'is_special_price' => 0,
            'prepay' => $input['prepay'],
        ]);
        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);

        // 予約枠の作成
        $roomTypeId = $hotelRoomTypes[0]->id;
        $roomTypeName = $hotelRoomTypes[0]->name;
        $tommorow = Carbon::tomorrow()->format('Y-m-d');
        $reservationBlock = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 0,
            'date' => $tommorow,
            'price' => 1000,
            'start_hour' => 20,
            'start_minute' => 0,
            'end_hour' => 22,
            'end_minute' => 30,
        ]);

        $reserveBlockId = $reservationBlock->id;
        $lpUrlParam = $lp->url_param;
        $baseCustomerItems = $this->_getDummyBaseCustomerItems();

        $response = $this->withSession([
            'booking_other.base_info.hotel_id' => $hotel->id,
            'booking_other.base_info.url_param' => $lpUrlParam,
            'booking_other.base_customer_items' => $baseCustomerItems,
            'booking_other.selected_rooms' => [
                [
                    'reservation_block_id' => $reserveBlockId,
                    'room_type_id' => $reservationBlock->room_type_id,
                    'room_name' => $roomTypeName,
                    'amount' => $reservationBlock->price,
                    'date' => $reservationBlock->date,
                    'start_time' => $reservationBlock->getStartTime(),
                    'end_time' => $reservationBlock->getEndTime(),
                    'person_num' => 2,
                ],
            ],
            'booking_other.room_amount' => [
                'sum' => $reservationBlock->price,
            ],
            'booking_other.reservation_info.session_time' => Carbon::now()->format('Y-m-d H:i'),
        ])->post('/booking/reservation/update', array_merge([
            'item_8' => 'サンプル太郎', // 氏名
            'item_9' => '00000000000', // 電話番号
            'item_10' => 'client@shareg.com', // メールアドレス
            'item_10_confirm' => 'client@shareg.com', // メールアドレス確認用
            'item_11' => '日本', // 住所
        ], $input['cardInfo']));

        $response->assertStatus(302)
            ->assertSessionHas('error', '申し訳ございません、ご予約のお手続き中にご選択された予約枠が満室となりました。大変恐れ入りますが、再度日時をご選択くださいませ。');
    }

    /** @test */
    public function updateReservationData_異常系_予約直前に料金が0で更新された()
    {
        $paymentProvider = $this->createPaymentProvider();
        $input = $paymentProvider['現地決済・事前決済どちらも許可(事前決済)'][0];

        [
            'hotel' => $hotel,
            'hotelRoomTypes' => $hotelRoomTypes,
            'cancelPolicy' => $cancelPolicy,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $form = factory(Form::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'public_status' => 1,
            'is_sale_period' => 1,
            'sale_period_start' => Carbon::yesterday()->format('Y-m-d h:i:s'),
            'sale_period_end' => Carbon::tomorrow()->format('Y-m-d h:i:s'),
            'is_room_type' => 0,
            'room_type_ids' => [],
            'cancel_policy_id' => $cancelPolicy->id,
            'is_deadline' => 0,
            'is_special_price' => 0,
            'prepay' => $input['prepay'],
        ]);
        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);

        // 予約枠の作成
        $roomTypeId = $hotelRoomTypes[0]->id;
        $roomTypeName = $hotelRoomTypes[0]->name;
        $tommorow = Carbon::tomorrow()->format('Y-m-d');
        $reservationBlock = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 1,
            'date' => $tommorow,
            'price' => 0,
            'start_hour' => 20,
            'start_minute' => 0,
            'end_hour' => 22,
            'end_minute' => 30,
        ]);

        $reserveBlockId = $reservationBlock->id;
        $lpUrlParam = $lp->url_param;
        $baseCustomerItems = $this->_getDummyBaseCustomerItems();

        $response = $this->withSession([
            'booking_other.base_info.hotel_id' => $hotel->id,
            'booking_other.base_info.url_param' => $lpUrlParam,
            'booking_other.base_customer_items' => $baseCustomerItems,
            'booking_other.selected_rooms' => [
                [
                    'reservation_block_id' => $reserveBlockId,
                    'room_type_id' => $reservationBlock->room_type_id,
                    'room_name' => $roomTypeName,
                    'amount' => $reservationBlock->price,
                    'date' => $reservationBlock->date,
                    'start_time' => $reservationBlock->getStartTime(),
                    'end_time' => $reservationBlock->getEndTime(),
                    'person_num' => 2,
                ],
            ],
            'booking_other.room_amount' => [
                'sum' => $reservationBlock->price,
            ],
            'booking_other.reservation_info.session_time' => Carbon::now()->format('Y-m-d H:i'),
        ])->post('/booking/reservation/update', array_merge([
            'item_8' => 'サンプル太郎', // 氏名
            'item_9' => '00000000000', // 電話番号
            'item_10' => 'client@shareg.com', // メールアドレス
            'item_10_confirm' => 'client@shareg.com', // メールアドレス確認用
            'item_11' => '日本', // 住所
        ], $input['cardInfo']));

        $response->assertStatus(302)
            ->assertSessionHas('error', '申し訳ございません、ご予約のお手続き中にご選択されたお部屋情報が更新されました。大変恐れ入りますが、再度お部屋をご選択くださいませ。');
    }

    /** @test */
    public function updateReservationData_異常系_Stripeによる事前決済に失敗した()
    {
        $paymentProvider = $this->createPaymentProvider();
        $input = $paymentProvider['現地決済・事前決済どちらも許可(事前決済)'][0];

        [
            'hotel' => $hotel,
            'hotelRoomTypes' => $hotelRoomTypes,
            'cancelPolicy' => $cancelPolicy,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $form = factory(Form::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'public_status' => 1,
            'is_sale_period' => 1,
            'sale_period_start' => Carbon::yesterday()->format('Y-m-d h:i:s'),
            'sale_period_end' => Carbon::tomorrow()->format('Y-m-d h:i:s'),
            'is_room_type' => 0,
            'room_type_ids' => [],
            'cancel_policy_id' => $cancelPolicy->id,
            'is_deadline' => 0,
            'is_special_price' => 0,
            'prepay' => $input['prepay'],
        ]);
        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);

        // 予約枠の作成
        $roomTypeId = $hotelRoomTypes[0]->id;
        $roomTypeName = $hotelRoomTypes[0]->name;
        $tommorow = Carbon::tomorrow()->format('Y-m-d');
        $reservationBlock = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 1,
            'date' => $tommorow,
            'price' => 1000,
            'start_hour' => 20,
            'start_minute' => 0,
            'end_hour' => 22,
            'end_minute' => 30,
        ]);

        // 予約の作成
        $reservation = $this->_予約の作成($reservationBlock, $hotel, 1);

        $reserveBlockId = $reservationBlock->id;
        $lpUrlParam = $lp->url_param;
        $baseCustomerItems = $this->_getDummyBaseCustomerItems();

        $this->_createMockApiClientDoRequest();
        $stripeErrorMessage = 'Stripe決済用Mockのエラーメッセージです。';
        $this->_createMockStripePrePay($stripeErrorMessage);

        $response = $this->withSession([
            'booking_other.base_info.hotel_id' => $hotel->id,
            'booking_other.base_info.url_param' => $lpUrlParam,
            'booking_other.base_customer_items' => $baseCustomerItems,
            'booking_other.selected_rooms' => [
                [
                    'reservation_block_id' => $reserveBlockId,
                    'room_type_id' => $reservationBlock->room_type_id,
                    'room_name' => $roomTypeName,
                    'amount' => $reservationBlock->price,
                    'date' => $reservationBlock->date,
                    'start_time' => $reservationBlock->getStartTime(),
                    'end_time' => $reservationBlock->getEndTime(),
                    'person_num' => 2,
                ],
            ],
            'booking_other.room_amount' => [
                'sum' => $reservationBlock->price,
            ],
            'booking_other.reservation_info.session_time' => Carbon::now()->format('Y-m-d H:i'),
            'booking_confirm.change_info.reservation_id' => $reservation->id,
        ])->post('/booking/reservation/update', array_merge([
            'item_8' => 'サンプル太郎', // 氏名
            'item_9' => '00000000000', // 電話番号
            'item_10' => 'client@shareg.com', // メールアドレス
            'item_10_confirm' => 'client@shareg.com', // メールアドレス確認用
            'item_11' => '日本', // 住所
        ], $input['cardInfo']));

        $response->assertStatus(302)
            ->assertSessionHas('error', $stripeErrorMessage);
    }

    /** @test */
    public function updateReservationData_異常系_予約入力項目の保存に失敗した()
    {
        $paymentProvider = $this->createPaymentProvider();
        $input = $paymentProvider['現地決済・事前決済どちらも許可(事前決済)'][0];

        [
            'hotel' => $hotel,
            'hotelRoomTypes' => $hotelRoomTypes,
            'cancelPolicy' => $cancelPolicy,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $form = factory(Form::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'public_status' => 1,
            'is_sale_period' => 1,
            'sale_period_start' => Carbon::yesterday()->format('Y-m-d h:i:s'),
            'sale_period_end' => Carbon::tomorrow()->format('Y-m-d h:i:s'),
            'is_room_type' => 0,
            'room_type_ids' => [],
            'cancel_policy_id' => $cancelPolicy->id,
            'is_deadline' => 0,
            'is_special_price' => 0,
            'prepay' => $input['prepay'],
        ]);
        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);

        // 予約枠の作成
        $roomTypeId = $hotelRoomTypes[0]->id;
        $roomTypeName = $hotelRoomTypes[0]->name;
        $tommorow = Carbon::tomorrow()->format('Y-m-d');
        $reservationBlock = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 1,
            'date' => $tommorow,
            'price' => 1000,
            'start_hour' => 20,
            'start_minute' => 0,
            'end_hour' => 22,
            'end_minute' => 30,
        ]);

        // 予約の作成
        $reservation = $this->_予約の作成($reservationBlock, $hotel, 1);

        $reserveBlockId = $reservationBlock->id;
        $lpUrlParam = $lp->url_param;
        $baseCustomerItems = $this->_getDummyBaseCustomerItems();

        $this->_createMockApiClientDoRequest();
        $this->_createMockStripePrePay();
        $this->_createMockSaveBaseCustomerItemValuesFailure();

        $response = $this->withSession([
            'booking_other.base_info.hotel_id' => $hotel->id,
            'booking_other.base_info.url_param' => $lpUrlParam,
            'booking_other.base_customer_items' => $baseCustomerItems,
            'booking_other.selected_rooms' => [
                [
                    'reservation_block_id' => $reserveBlockId,
                    'room_type_id' => $reservationBlock->room_type_id,
                    'room_name' => $roomTypeName,
                    'amount' => $reservationBlock->price,
                    'date' => $reservationBlock->date,
                    'start_time' => $reservationBlock->getStartTime(),
                    'end_time' => $reservationBlock->getEndTime(),
                    'person_num' => 2,
                ],
            ],
            'booking_other.room_amount' => [
                'sum' => $reservationBlock->price,
            ],
            'booking_other.reservation_info.session_time' => Carbon::now()->format('Y-m-d H:i'),
            'booking_confirm.change_info.reservation_id' => $reservation->id,
        ])->post('/booking/reservation/update', array_merge([
            'item_8' => 'サンプル太郎', // 氏名
            'item_9' => '00000000000', // 電話番号
            'item_10' => 'client@shareg.com', // メールアドレス
            'item_10_confirm' => 'client@shareg.com', // メールアドレス確認用
            'item_11' => '日本', // 住所
        ], $input['cardInfo']));

        $response->assertStatus(302)
            ->assertSessionHas('error', '予期せぬエラーが発生しました。恐れ入りますが、お時間をおいて再度お試しくださいませ。');
    }

    /** @test */
    public function updateReservationData_異常系_CRMとの予約データの同期に失敗した()
    {
        $paymentProvider = $this->createPaymentProvider();
        $input = $paymentProvider['現地決済・事前決済どちらも許可(事前決済)'][0];

        [
            'hotel' => $hotel,
            'hotelRoomTypes' => $hotelRoomTypes,
            'cancelPolicy' => $cancelPolicy,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        $form = factory(Form::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'public_status' => 1,
            'is_sale_period' => 1,
            'sale_period_start' => Carbon::yesterday()->format('Y-m-d h:i:s'),
            'sale_period_end' => Carbon::tomorrow()->format('Y-m-d h:i:s'),
            'is_room_type' => 0,
            'room_type_ids' => [],
            'cancel_policy_id' => $cancelPolicy->id,
            'is_deadline' => 0,
            'is_special_price' => 0,
            'prepay' => $input['prepay'],
        ]);
        $lp = factory(Lp::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'form_id' => $form->id,
        ]);

        // 予約枠の作成
        $roomTypeId = $hotelRoomTypes[0]->id;
        $roomTypeName = $hotelRoomTypes[0]->name;
        $tommorow = Carbon::tomorrow()->format('Y-m-d');
        $reservationBlock = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 1,
            'date' => $tommorow,
            'price' => 1000,
            'start_hour' => 20,
            'start_minute' => 0,
            'end_hour' => 22,
            'end_minute' => 30,
        ]);

        // 予約の作成
        $reservation = $this->_予約の作成($reservationBlock, $hotel, 1);

        $reserveBlockId = $reservationBlock->id;
        $lpUrlParam = $lp->url_param;
        $baseCustomerItems = $this->_getDummyBaseCustomerItems();

        $this->_createMockApiClientDoRequest();
        $this->_createMockStripePrePay();
        $this->_createMockSavePmsReservationDataFailure();

        $response = $this->withSession([
            'booking_other.base_info.hotel_id' => $hotel->id,
            'booking_other.base_info.url_param' => $lpUrlParam,
            'booking_other.base_customer_items' => $baseCustomerItems,
            'booking_other.selected_rooms' => [
                [
                    'reservation_block_id' => $reserveBlockId,
                    'room_type_id' => $reservationBlock->room_type_id,
                    'room_name' => $roomTypeName,
                    'amount' => $reservationBlock->price,
                    'date' => $reservationBlock->date,
                    'start_time' => $reservationBlock->getStartTime(),
                    'end_time' => $reservationBlock->getEndTime(),
                    'person_num' => 2,
                ],
            ],
            'booking_other.room_amount' => [
                'sum' => $reservationBlock->price,
            ],
            'booking_other.reservation_info.session_time' => Carbon::now()->format('Y-m-d H:i'),
            'booking_confirm.change_info.reservation_id' => $reservation->id,
        ])->post('/booking/reservation/update', array_merge([
            'item_8' => 'サンプル太郎', // 氏名
            'item_9' => '00000000000', // 電話番号
            'item_10' => 'client@shareg.com', // メールアドレス
            'item_10_confirm' => 'client@shareg.com', // メールアドレス確認用
            'item_11' => '日本', // 住所
        ], $input['cardInfo']));

        $response->assertStatus(302)
            ->assertSessionHas('error', '予期せぬエラーが発生しました。恐れ入りますが、お時間をおいて再度お試しくださいませ。');
    }

    /** @test */
    public function bookingShow_正常系_予約した部屋数が1部屋()
    {
        [
            'hotel' => $hotel,
            'hotelRoomTypes' => $hotelRoomTypes,
            'cancelPolicy' => $cancelPolicy,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        // 予約枠の作成
        $roomTypeId = $hotelRoomTypes[0]->id;
        $tommorow = Carbon::tomorrow()->format('Y-m-d');
        $reservationBlock = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 1,
            'date' => $tommorow,
            'price' => 1000,
            'start_hour' => 20,
            'start_minute' => 0,
            'end_hour' => 22,
            'end_minute' => 30,
        ]);

        // 予約の作成
        $accommodationPrice = $reservationBlock->price;
        $commissionRate = rand(10, 20);
        $commissionPrice = (int)($accommodationPrice * $commissionRate / 100);
        $reservation = factory(Reservation::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'accommodation_price' => $accommodationPrice,
            'commission_rate' => $commissionRate,
            'commission_price' => $commissionPrice,
            'reservation_code' => 'bc-wgqdh',
            'verify_token' => '61ea15e729ebdhozpcaln',
            'payment_method' => 1,
        ]);
        $reservedReservationBlock = factory(ReservedReservationBlock::class)->create([
            'reservation_id' => $reservation->id,
            'reservation_block_id' => $reservationBlock->id,
            'customer_id' => 0,
            'line_user_id' => 0,
            'price' => $reservationBlock->price,
            'date' => $reservationBlock->date,
            'start_hour' => $reservationBlock->start_hour,
            'start_minute' => $reservationBlock->start_minute,
            'end_hour' => $reservationBlock->end_hour,
            'end_minute' => $reservationBlock->end_minute,
        ]);
        $reservationCancelPolicy = factory(ReservationCancelPolicy::class)->create([
            'hotel_id' => $hotel->id,
            'cancel_policy_id' => $cancelPolicy->id,
            'reservation_id' => $reservation->id,
        ]);

        // 予約入力項目の取得と並び替え
        $baseCustomerItemValues = collect($this->_createBaseCutomerItemValues($reservation))
                    ->pluck(null, 'id')->toArray();
        $sortedItemValues = [];
        $items = $this->_getDummyBaseCustomerItems();
        foreach($items as $item) {
            if (array_key_exists($item['id'], $baseCustomerItemValues)) {
                $sortedItemValues[] = $baseCustomerItemValues[$item['id']];
            }
        }
        $this->_createMockApiClientDoGetRequest();
        // 利用日程
        $checkinoutDateTime = date('Y年n月j日 H:i', strtotime($reservation['checkin_time'])) . ' 〜 ' . date('Y年n月j日 H:i', strtotime($reservation['checkout_time']));

        $response = $this->get("/booking/reservation/show/{$reservation->verify_token}");
        $response->assertStatus(200)
            ->assertViewIs('user.booking.other.show')
            ->assertSeeInOrder(array_merge([
                '利用日程',
                $checkinoutDateTime,
                "1部屋目（大人{$reservedReservationBlock->person_num}人）"
            ], collect($sortedItemValues)->map(function($value) {
                if ($value['data_type'] != 7) {
                    return collect($value)->only(['name', 'value']);
                } else {
                    $gender = $value['value'];
                    $v = '';
                    if ($gender == 1) {
                        $v = '男性';
                    } elseif ($gender == 2) {
                        $v = '女性';
                    } else {
                        $v = 'その他';
                    }
                    return [$value['name'], $v];
                }
            })->flatten()->toArray()), [
                "予約後キャンセルした場合は、宿泊料金の{$reservationCancelPolicy->cancel_charge_rate}%のキャンセル料がかかります。",
                "無断でキャンセルした場合、{$reservationCancelPolicy->no_show_charge_rate}%のキャンセル料がかかります。",
            ]);
    }

    /** @test */
    public function bookingShow_正常系_予約した部屋数が複数部屋()
    {
        [
            'hotel' => $hotel,
            'hotelRoomTypes' => $hotelRoomTypes,
            'cancelPolicy' => $cancelPolicy,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        // 予約枠の作成
        $roomTypeId = $hotelRoomTypes[0]->id;
        $tommorow = Carbon::tomorrow()->format('Y-m-d');
        $reservationBlock = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 1,
            'date' => $tommorow,
            'price' => 1000,
            'start_hour' => 20,
            'start_minute' => 0,
            'end_hour' => 22,
            'end_minute' => 30,
        ]);

        // 予約の作成
        $accommodationPrice = $reservationBlock->price;
        $commissionRate = rand(10, 20);
        $commissionPrice = (int)($accommodationPrice * $commissionRate / 100);
        $reservation = factory(Reservation::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'accommodation_price' => $accommodationPrice,
            'commission_rate' => $commissionRate,
            'commission_price' => $commissionPrice,
            'reservation_code' => 'bc-wgqdh',
            'verify_token' => '61ea15e729ebdhozpcaln',
            'payment_method' => 1,
        ]);
        // 1部屋目
        $reservedReservationBlock = factory(ReservedReservationBlock::class)->create([
            'reservation_id' => $reservation->id,
            'reservation_block_id' => $reservationBlock->id,
            'customer_id' => 0,
            'line_user_id' => 0,
            'price' => $reservationBlock->price,
            'date' => $reservationBlock->date,
            'start_hour' => $reservationBlock->start_hour,
            'start_minute' => $reservationBlock->start_minute,
            'end_hour' => $reservationBlock->end_hour,
            'end_minute' => $reservationBlock->end_minute,
        ]);
        // 2部屋目
        $reservedReservationBlock2 = factory(ReservedReservationBlock::class)->create([
            'reservation_id' => $reservation->id,
            'reservation_block_id' => $reservationBlock->id,
            'customer_id' => 0,
            'line_user_id' => 0,
            'price' => $reservationBlock->price,
            'date' => $reservationBlock->date,
            'start_hour' => $reservationBlock->start_hour,
            'start_minute' => $reservationBlock->start_minute,
            'end_hour' => $reservationBlock->end_hour,
            'end_minute' => $reservationBlock->end_minute,
        ]);
        $reservationCancelPolicy = factory(ReservationCancelPolicy::class)->create([
            'hotel_id' => $hotel->id,
            'cancel_policy_id' => $cancelPolicy->id,
            'reservation_id' => $reservation->id,
        ]);

        // 予約入力項目の取得と並び替え
        $baseCustomerItemValues = collect($this->_createBaseCutomerItemValues($reservation))
                    ->pluck(null, 'id')->toArray();
        $sortedItemValues = [];
        $items = $this->_getDummyBaseCustomerItems();
        foreach($items as $item) {
            if (array_key_exists($item['id'], $baseCustomerItemValues)) {
                $sortedItemValues[] = $baseCustomerItemValues[$item['id']];
            }
        }
        $this->_createMockApiClientDoGetRequest();
        // 利用日程
        $checkinoutDateTime = date('Y年n月j日 H:i', strtotime($reservation['checkin_time'])) . ' 〜 ' . date('Y年n月j日 H:i', strtotime($reservation['checkout_time']));

        $response = $this->get("/booking/reservation/show/{$reservation->verify_token}");
        $response->assertStatus(200)
            ->assertViewIs('user.booking.other.show')
            ->assertSeeInOrder(array_merge([
                '利用日程',
                $checkinoutDateTime,
                "1部屋目（大人{$reservedReservationBlock->person_num}人）",
                "2部屋目（大人{$reservedReservationBlock2->person_num}人）",
            ], collect($sortedItemValues)->map(function($value) {
                if ($value['data_type'] != 7) {
                    return collect($value)->only(['name', 'value']);
                } else {
                    $gender = $value['value'];
                    $v = '';
                    if ($gender == 1) {
                        $v = '男性';
                    } elseif ($gender == 2) {
                        $v = '女性';
                    } else {
                        $v = 'その他';
                    }
                    return [$value['name'], $v];
                }
            })->flatten()->toArray()), [
                "予約後キャンセルした場合は、宿泊料金の{$reservationCancelPolicy->cancel_charge_rate}%のキャンセル料がかかります。",
                "無断でキャンセルした場合、{$reservationCancelPolicy->no_show_charge_rate}%のキャンセル料がかかります。",
            ]);
    }

    /** @test */
    public function bookingShow_異常系_CRMからのデータ取得失敗()
    {
        [
            'hotel' => $hotel,
            'hotelRoomTypes' => $hotelRoomTypes,
            'cancelPolicy' => $cancelPolicy,
        ] = $this->_施設_部屋タイプ_キャンセルポリシー作成();

        // 予約枠の作成
        $roomTypeId = $hotelRoomTypes[0]->id;
        $tommorow = Carbon::tomorrow()->format('Y-m-d');
        $reservationBlock = factory(ReservationBlock::class)->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomTypeId,
            'is_available' => 1,
            'date' => $tommorow,
            'price' => 1000,
            'start_hour' => 20,
            'start_minute' => 0,
            'end_hour' => 22,
            'end_minute' => 30,
        ]);

        // 予約の作成
        $accommodationPrice = $reservationBlock->price;
        $commissionRate = rand(10, 20);
        $commissionPrice = (int)($accommodationPrice * $commissionRate / 100);
        $reservation = factory(Reservation::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'accommodation_price' => $accommodationPrice,
            'commission_rate' => $commissionRate,
            'commission_price' => $commissionPrice,
            'reservation_code' => 'bc-wgqdh',
            'verify_token' => '61ea15e729ebdhozpcaln',
            'payment_method' => 1,
        ]);
        $reservedReservationBlock = factory(ReservedReservationBlock::class)->create([
            'reservation_id' => $reservation->id,
            'reservation_block_id' => $reservationBlock->id,
            'customer_id' => 0,
            'line_user_id' => 0,
            'price' => $reservationBlock->price,
            'date' => $reservationBlock->date,
            'start_hour' => $reservationBlock->start_hour,
            'start_minute' => $reservationBlock->start_minute,
            'end_hour' => $reservationBlock->end_hour,
            'end_minute' => $reservationBlock->end_minute,
        ]);
        $reservationCancelPolicy = factory(ReservationCancelPolicy::class)->create([
            'hotel_id' => $hotel->id,
            'cancel_policy_id' => $cancelPolicy->id,
            'reservation_id' => $reservation->id,
        ]);

        $isInvalid = true;
        $this->_createMockApiClientDoGetRequest($isInvalid);

        $response = $this->get("/booking/reservation/show/{$reservation->verify_token}");
        $response->assertStatus(200)
            ->assertViewIs('user.booking.other.show')
            ->assertSeeInOrder([
                '予約詳細',
                '予期せぬエラーが発生しました。恐れ入りますが、お時間をおいて再度お試しくださいませ。',
            ]);
    }

    private function _施設_部屋タイプ_キャンセルポリシー作成(): array
    {
        $hotel = factory(Hotel::class)->create([
            'client_id' => $this->client->id,
            'business_type' => random_int(3, 4),
            'checkin_start' => '00:01:00',
            'checkin_end' => '25:00:00',
            'checkout_end' => '27:00:00',
        ]);
        $hotelRoomTypes = factory(HotelRoomType::class, 3)->create([
            'hotel_id' => $hotel->id,
            'room_num' => 3,
        ]);
        $cancelPolicy = factory(CancelPolicy::class)->create([
            'hotel_id' => $hotel->id,
        ]);
        return compact('hotel', 'hotelRoomTypes', 'cancelPolicy');
    }

    private function _特別価格手動入力ありのフォーム作成(
        Hotel $hotel,
        Collection $hotelRoomTypes,
        CancelPolicy $cancelPolicy,
        int $handInputPrice
    ): Form {
        return factory(Form::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'public_status' => 1,
            'is_sale_period' => 1,
            'sale_period_start' => Carbon::yesterday()->format('Y-m-d h:i:s'),
            'sale_period_end' => Carbon::tomorrow()->format('Y-m-d h:i:s'),
            'is_room_type' => 0,
            'cancel_policy_id' => $cancelPolicy->id,
            'is_deadline' => 0,
            'is_special_price' => 1,
            // 特別価格の金額を手動で入力しますか？：はい
            'is_hand_input' => 1,
            'hand_input_room_prices' => [
                [
                    'price' => $handInputPrice,
                    'room_type_id' => $hotelRoomTypes[0]->id
                ],
            ],
        ]);
    }

    private function _予約の作成(
        ReservationBlock $reservationBlock,
        Hotel $hotel,
        int $paymentMethod = 0
    ): Reservation {
        $accommodationPrice = $reservationBlock->price;
        $commissionRate = rand(10, 20);
        $commissionPrice = (int)($accommodationPrice * $commissionRate / 100);
        $reservation = factory(Reservation::class)->create([
            'client_id' => $this->client->id,
            'hotel_id' => $hotel->id,
            'accommodation_price' => $accommodationPrice,
            'commission_rate' => $commissionRate,
            'commission_price' => $commissionPrice,
            'reservation_code' => 'bc-wgqdh',
            'verify_token' => '61ea15e729ebdhozpcaln',
            'payment_method' => $paymentMethod,
        ]);
        $reservedReservationBlock = factory(ReservedReservationBlock::class)->create([
            'reservation_id' => $reservation->id,
            'reservation_block_id' => $reservationBlock->id,
            'customer_id' => 0,
            'line_user_id' => 0,
            'price' => $reservationBlock->price,
            'date' => $reservationBlock->date,
            'start_hour' => $reservationBlock->start_hour,
            'start_minute' => $reservationBlock->start_minute,
            'end_hour' => $reservationBlock->end_hour,
            'end_minute' => $reservationBlock->end_minute,
        ]);
        return $reservation;
    }

    private function _formatExpectReservationBlock(ReservationBlock $block, array $params = []): array
    {
        $startTime = implode(':', array_slice(explode(':', $block->getStartTime()), 0, 2));
        $endTime = implode(':', array_slice(explode(':', $block->getEndTime()), 0, 2));
        return array_merge([
            'end_time' => $endTime,
            'is_available' => $block->is_available,
            'person_capacity' => $block->person_capacity,
            'price' => $block->price,
            'start_time' => $startTime,
            'room_num' => $block->room_num - $block->reserved_num,
        ], $params);
    }

    // CRMのリクエスト (GET /base_customer_items) のモック
    private function _createMockApiClientDoGetRequest(bool $isInvalid = false): void
    {
        $this->mock('overload:' . ApiClient::class, function ($mock) use ($isInvalid) {
            $mock->shouldReceive('getUrlParams')
                ->once()
                ->andReturn(http_build_query([]));
            $params = $isInvalid ? null : $this->_getDummyBaseCustomerItems();
            $mock->shouldReceive('doGetRequest')
                ->once()
                ->andReturn($params);
        });
    }

    // CRMのリクエスト (POST /save_reservation) のモック
    private function _createMockApiClientDoRequest(bool $isInvalid = false): void
    {
        $this->mock('overload:' . ApiClient::class, function ($mock) use ($isInvalid) {
            $mock->shouldReceive('getUrlParams')
                ->once()
                ->andReturn(http_build_query([]));
            $params = $isInvalid ? [
                'status' => false,
                'code' => 500,
                'msg' => 'ERROR',
                'data' => null,
            ] : [
                'status' => true,
                'code' => 200,
                'msg' => 'SUCCESS',
                'data' => [
                    'address' => '日本',
                    'checkin_date' => '2022-02-04',
                    'email' => 'client@shareg.com',
                    'end_time' => '24:00:00',
                    'name' => 'サンプル太郎',
                    'person_num' => '1',
                    'price' => '6000',
                    'start_time' => '23:30:00',
                    'tel' => '0000000000',
                    'be_reservation_id' => '838',
                    'base_id' => '135',
                    'reservation_status' => '1',
                    'line_user_ids' => '0',
                    'updated_at' => '2022-01-12 12:58:32',
                    'created_at' => '2022-01-12 12:58:32',
                    'id' => 1,
                ],
            ];
            $mock->shouldReceive('doRequest')
                ->once()
                ->andReturn((object)$params);
        });
    }

    // StripeService@bookingPrePay, manageDoAuthoryPayByCIdのパーシャルモック
    private function _createMockStripePrePay(?string $errorMessage = null): void
    {
        \Mockery::getConfiguration()->setInternalClassMethodParamMap(
            'StripeService',
            'bookingPrePay',
            array('&$cardData', '$amount', '$description', '$name', '$email', '$isFreeCancel', '$reserveId')
        );

        $this->mock(StripeService::class, function ($mock) use ($errorMessage) {
            $mock->shouldReceive('bookingPrePay')
                // ->once()
                ->with(
                    \Mockery::on(function(&$cardData) {
                        if (!is_array($cardData)) return false;
                        $cardData['stripe_customer_id'] = 'cus_abc123';
                        $cardData['stripe_payment_id'] = 'ch_ABC123';
                        $cardData['payment_status'] = 2;
                        return true;
                    }),
                    // $amount
                    \Mockery::any(),
                    // $description
                    \Mockery::any(),
                    // $name
                    \Mockery::any(),
                    // $email
                    \Mockery::any(),
                    // $isFreeCancel
                    \Mockery::any(),
                    // $reserveId
                    \Mockery::any()
                )
                ->andReturn(['res' => is_null($errorMessage), 'message' => $errorMessage]);
            $isNotError = is_null($errorMessage);
            $mock->shouldReceive('manageDoAuthoryPayByCId')
                // ->once()
                ->andReturn([
                    'res' => $isNotError,
                    'message' => $errorMessage,
                    'data' => $isNotError ? (object)[
                        'id' => 'cus_abc456',
                    ] : [],
                ]);
        })->makePartial();
    }

    // 予約入力項目の保存エラーのパーシャルモック
    private function _createMockSaveBaseCustomerItemValuesFailure(): void
    {
        $this->mock(OtherReserveService::class, function ($mock) {
            $mock->shouldReceive('saveBaseCustomerItemValues')
                ->once()
                ->andReturn(false);
        })->makePartial();
    }

    // CRM(PMS)とのデータ同期エラーのパーシャルモック
    private function _createMockSavePmsReservationDataFailure(): void
    {
        $this->mock(OtherReserveService::class, function ($mock) {
            $mock->shouldReceive('savePmsReservationData')
                ->once()
                ->andReturn((object)[
                    'status' => false,
                    'code' => 500
                ]);
        })->makePartial();
    }
}
