<?php

use App\Models\Client;
use App\Models\Hotel;
use App\Models\HotelKidsPolicy;
use App\Models\HotelMonthFeeSummary;
use App\Models\HotelRoomType;
use App\Models\HotelRoomTypeBed;
use App\Models\HotelRoomTypeImage;
use App\Models\Image;
use App\Models\Reservation;
use App\Models\ReservationBranch;
use App\Models\ReservationPlan;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{

    protected function outLog($msg)
    {
        if (isset($this->command)) {
            $this->command->getOutput()->writeln($msg);
        }
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (config('migrate.seeds.skip_client')) {
            return;
        }

        $images = Image::where('client_id', 0)->limit(30)->get();

        //add default client
        $this->outLog("<info>Creating:</info> default client");
        $client = new Client();
        $client->fill([
            'company_name' => 'the first company',
            'address' => 'this is the company address',
            'tel' => '12-345-6789',
            'person_in_charge' => 'default client',
            'email' => 'client@shareg.com',
            'password' => bcrypt('pa@123456'),
        ]);
        $num = rand(1, 30);
        $client->hotel_num = $num;
        $client->save();

        $this->createHotels($client, $images);

        //add test 21 clients
        $this->outLog("<info>Creating:</info> test 21 clients");
        factory(Client::class, 21)->make()->each(function ($client) use ($images) {
            //add random 1 ~ 21 hotels for eveny client
            $num = rand(1, 21);
            $client->hotel_num = $num;
            $client->save();
            $this->createHotels($client, $images);
        });
    }

    protected function createHotels($client, $images)
    {
        $num = $client->hotel_num;
        if ($num > 0) {
            $this->outLog("<info>Creating:</info> Client({$client->id}) {$num} hotels");
            factory(Hotel::class, $num)->make()->each(function ($hotel) use ($client, $images, $num) {
                $hotel->client_id = $client->id;

                $hotel->tema_login_id = 'tema' . str_pad($hotel->id, 3, '0', STR_PAD_LEFT);
                $hotel->tema_login_password = 'pa123456';
                $hotel->bank_code = '123456'.$num;
                $hotel->branch_code = '123456_'.$num;
                $hotel->deposit_type = 1;
                $hotel->account_number = 'acc_num_'.$num;
                $hotel->recipient_name = "client{$num}";
                $hotel->save();

                $roomTypeIds = $this->createHotelRooms($hotel);

                $this->createHotelRoomBeds($hotel, $roomTypeIds);

                $this->createHotelRoomImages($hotel, $roomTypeIds, $images);

                $this->createHotelKidsPolicy($hotel, $roomTypeIds);

                $planIds = $this->createHotelPlans($hotel);
                $this->createHotelForms($hotel, $roomTypeIds, $planIds);

                if ($hotel->id == 1) {
                    $this->outLog("<info>Creating:</info> Client({$client->id}) Hotel($hotel->id) month fee summary");
                    $this->createHotelMonthFeeSummary($hotel, $roomTypeIds);
                }
            });
        }
    }

    protected function createHotelRooms($hotel)
    {
        if (config('migrate.seeds.skip_hotel_room_type')) {
            return [];
        }

        $names = [
            0 => 'スタンダード',
            1 => 'スタンダードソファ',
            2 => 'アトリウム',
            3 => 'デイユース | スタンダード',
            4 => 'デイユース | スタンダードソファ',
            5 => 'デイユース | アトリウム'
        ];
        $num = rand(3, 6);
        $roomTypeIds = [];
        for ($i = 0; $i < $num; $i++) {
            $hotelRoomType = new HotelRoomType;
            $hotelRoomType->hotel_id = $hotel->id;
            $hotelRoomType->name = '部屋名' . $names[$i];
            $hotelRoomType->room_num = rand(6, 30);
            $hotelRoomType->adult_num = rand(1, 4);
            $hotelRoomType->child_num = rand(0, 2);
            $hotelRoomType->room_size = rand(10, 30);
            $hotelRoomType->sort_num = $i;
            $hotelRoomType->save();
            $roomTypeIds[] = $hotelRoomType->id;
        }

        return $roomTypeIds;
    }

    protected function createHotelRoomBeds($hotel, $roomTypeIds)
    {
        if (config('migrate.seeds.skip_room_type_bed')) {
            return;
        }

        $data = [];
        $bedSizes = [5, 10, 11];
        foreach ($roomTypeIds as $roomTypeId) {
            $data[] = [
                'room_type_id' => $roomTypeId,
                'bed_size' => $bedSizes[array_rand($bedSizes)],
                'bed_num' => rand(1, 2),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        $hotelRoomTypeBed = new HotelRoomTypeBed();
        $hotelRoomTypeBed->batchInsert($data);
    }

    protected function createHotelRoomImages($hotel, $roomTypeIds, $images)
    {
        if (config('migrate.seeds.skip_room_type_image')) {
            return;
        }

        $data = [];
        foreach ($roomTypeIds as $roomTypeId) {
            $data[] = [
                'room_type_id' => $roomTypeId,
                'image' => $images->random()->src(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        $hotelRoomTypeImage = new HotelRoomTypeImage();
        $hotelRoomTypeImage->batchInsert($data);
    }

    protected function createHotelKidsPolicy($hotel, $roomTypeIds)
    {
        if (config('migrate.seeds.skip_hotel_kids_policy')) {
            return;
        }

        $data = [
            [
                'hotel_id' => $hotel->id,
                'age_start' => 0,
                'age_end' => 7,
                'is_forbidden' => 0,
                'is_all_room' => 0,
                'room_type_ids' => json_encode([$roomTypeIds[0], $roomTypeIds[1]]),
                'is_rate' => 1,
                'fixed_amount' => 5000,
                'rate' => NULL,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'hotel_id' => $hotel->id,
                'age_start' => 8,
                'age_end' => 12,
                'is_forbidden' => 0,
                'is_all_room' => 1,
                'room_type_ids' => NULL,
                'is_rate' => 2,
                'fixed_amount' => NULL,
                'rate' => 50,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        $hotelKidsPolicy = new HotelKidsPolicy();
        $hotelKidsPolicy->batchInsert($data);
    }

    protected function createHotelPlans($hotel)
    {
        return [];
    }

    protected function createHotelMonthFeeSummary($hotel, $roomTypeIds)
    {
        $month = now()->startOfMonth();
        $monthNums = rand(1, 6);
        $ratePlans = [
            1 => 0,
            2 => 5000,
            3 => 12000,
            4 => 30000,
        ];
        for ($i = 0; $i < $monthNums; $i++) {
            $mon = $month->format('Y-m-d');
            $days = $month->daysInMonth;

            $this->createHotelReservation($hotel, $mon, $days, $roomTypeIds);

            $fee = isset($ratePlans[$hotel->rate_plan_id]) ? $ratePlans[$hotel->rate_plan_id] : 0;
            $this->createHotelOneMonthFeeSummary($hotel, $mon, $fee);

            $month->subMonth();
        }
    }

    protected function createHotelOneMonthFeeSummary($hotel, $month, $fee)
    {
        $summary = new HotelMonthFeeSummary;
        $summary->hotel_id = $hotel->id;
        $summary->client_id = $hotel->client_id;
        $summary->month = $month;
        $summary->rate_plan_id = $hotel->rate_plan_id;
        $summary->monthly_fee = $fee;
        $summary->reservation_fee = rand(1000000, 4000000);
        $summary->reservation_num = rand(100, 400);
        $summary->save();
        return $summary;
    }

    protected function createHotelReservation($hotel, $month, $days, $roomTypeIds)
    {
        $num = rand(5, 30);

        factory(Reservation::class, $num)->make()->each(function ($reservation) use ($hotel, $month, $days, $roomTypeIds) {

            $branchNum = rand(1, 3);
            $day = rand(1, $days);
            $stayDays = rand(1, 3);

            $reservation->client_id = $hotel->client_id;
            $reservation->hotel_id = $hotel->id;
            $reservation->checkin_start = Carbon::parse($month)->addDays($day)->format('Y-m-d 12:00:00');
            $reservation->checkin_end = Carbon::parse($month)->addDays($day)->format('Y-m-d 23:59:00');
            $reservation->checkout_end = Carbon::parse($month)->addDays($day + $stayDays)->format('Y-m-d 14:00:00');
            $reservation->reservation_date = Carbon::parse($month)->addDays($day - rand(1, 7))->addHours(rand(8, 23))->addMinutes(rand(0, 59))->format('Y-m-d H:i:s');
            $reservation->reservation_status = rand(0, 2);

            $reservation->address = '';
            $reservation->save();

            $allPrice = 0;
            $allRoomNum = 0;
            $allAdultNum = 0;
            $allChildNum = 0;

            for ($j = 1; $j <= $branchNum; $j++) {
                $price = rand(10000, 50000);
                $roomNum = rand(1, 5);
                $adultNum = rand(1, 3);
                $childNum = rand(0, 2);

                $roomTypeId = $roomTypeIds[array_rand($roomTypeIds)];
                $reservationBranch = new ReservationBranch;
                $reservationBranch->reservation_id = $reservation->id;
                $reservationBranch->reservation_branch_num = $j;
                $reservationBranch->plan_id = 1;
                $reservationBranch->room_type_id = $roomTypeId;
                $reservationBranch->reservation_status = $reservation->reservation_status;
                $reservationBranch->reservation_date_time = $reservation->reservation_date;
                if ($reservationBranch->reservation_status == 1 || $reservationBranch->reservation_status == 2) {
                    //キャンセル日時
                    $reservationBranch->cancel_date_time = Carbon::parse($reservationBranch->reservation_date_time)->addDays(rand(3, 6))->format('Y-m-d H:i:s');
                } else {
                    $reservationBranch->tema_reservation_type = rand(0, 1);
                    if ($reservationBranch->tema_reservation_type == 1) {
                        //変更日時
                        $reservationBranch->change_date_time = Carbon::parse($reservationBranch->reservation_date_time)->addDays(rand(3, 6))->format('Y-m-d H:i:s');
                    }
                }
                $reservationBranch->accommodation_price = $price;
                $reservationBranch->room_num = $roomNum;
                $reservationBranch->save();

                for ($i = 0; $i < $stayDays; $i++) {
                    $reservationPlan = new ReservationPlan;
                    $reservationPlan->reservation_id = $reservation->id;
                    $reservationPlan->reservation_branch_id = $reservationBranch->id;
                    $reservationPlan->room_number = $roomNum;
                    $reservationPlan->adult_num = $adultNum;
                    $reservationPlan->child_num = $childNum;
                    $reservationPlan->amount = 0;
                    $reservationPlan->date = Carbon::parse($month)->addDays($day + $i)->format('Y-m-d');
                    $reservationPlan->save();
                }

                $allRoomNum += $roomNum;
                $allAdultNum += $adultNum;
                $allChildNum += $childNum;
                $allPrice += $price;
            }

            $reservation->room_num = $allRoomNum;
            $reservation->adult_num = $allAdultNum;
            $reservation->child_num = $allChildNum;
            $reservation->accommodation_price = $allPrice;
            $reservation->reservation_code = 'RESN-' . base_convert($reservation->id, 10, 32);
            $reservation->save();
        });
    }

    protected function createHotelForms($hotel, $roomTypeIds, $planIds)
    {
        $num = rand(1, 5);
        factory(\App\Models\Form::class, $num)->make()->each(function ($form) use ($hotel, $roomTypeIds, $planIds) {
            $day = 1;
            $month = 1;
            $clientId = $hotel->client_id;
            $hotelId = $hotel->id;
            $formPartsIds = [random_int(1, 5)];

            $isDeadline = $form->is_deadline;
            $deadlineStart = null;
            $deadlineEnd = null;
            if ($isDeadline == 1) {
                $deadlineStart = Carbon::parse($hotel->created_at)->addDays($day);
                $deadlineEnd = Carbon::parse($hotel->created_at)->addMonths($month);
            }

            $isSalePeriod = $form->is_sale_period;
            $salePeriodStart = null;
            $salePeriodEnd = null;
            if ($isSalePeriod == 1) {
                $salePeriodStart = Carbon::parse($hotel->created_at)->addDays($day);
                $salePeriodEnd = Carbon::parse($hotel->created_at)->addMonths($month);
            }
            $isPlan = $form->is_plan;
            $pIds = [];
            if ($isPlan == 1) {
                $pIds = $planIds;
            }

            $isRoomType = $form->is_room_type;
            $rtIds = [];
            if ($isRoomType == 1) {
                $rtIds = $roomTypeIds;
            }

            $handInputRoomPrices = $form->hand_input_room_prices;
            if (!empty($handInputRoomPrices)) {
                $handInputRoomPrices = [];
                foreach ($rtIds as $id) {
                    $handInputRoomPrice = [
                        'room_type_id' => $id,
                        'price' => random_int(100, 200),
                    ];
                    $handInputRoomPrices[] = $handInputRoomPrice;
                }
            } else {
                $handInputRoomPrices = [];
            }

            $allPlanPrice = $form->all_plan_price;
            if (!empty($allPlanPrice)) {
                $unit = rand(1, 2);
                if ($unit == 1) {
                    $num = random_int(100, 200);
                } else {
                    $num = random_int(1, 30);
                }
                $allPlanPrice = [
                    'num' => $num,
                    'unit' => $unit,
                    'up_off' => random_int(1, 2)
                ];
            } else {
                $allPlanPrice = new stdClass();
            }

            $specialPlanPrices = $form->special_plan_prices;
            if (!empty($specialPlanPrices)) {
                $specialPlanPrices = [];
                foreach ($planIds as $planId) {
                    $unit = rand(0, 1);
                    if ($unit == 1) {
                        $num = random_int(100, 200);
                    } else {
                        $num = random_int(1, 30);
                    }
                    $specialPlanPrice = [
                        'plan_id' => $planId,
                        'num' => $num,
                        'unit' => $unit,
                        'up_off' => random_int(1, 2)
                    ];
                    $specialPlanPrices[] = $specialPlanPrice;
                }
            } else {
                $specialPlanPrices = [];
            }

            $customFormItemIds = [];
            $publicStatus = random_int(0, 1);

            $f = [
                'client_id' => $clientId,
                'hotel_id' => $hotelId,
                'form_parts_ids' => $formPartsIds,
                'deadline_start' => $deadlineStart,
                'deadline_end' => $deadlineEnd,
                'sale_period_start' => $salePeriodStart,
                'sale_period_end' => $salePeriodEnd,
                'plan_ids' => $pIds,
                'room_type_ids' => $rtIds,
                'hand_input_room_prices' => $handInputRoomPrices,
                'all_plan_price' => $allPlanPrice,
                'special_plan_prices' => $specialPlanPrices,
                'custom_form_item_ids' => $customFormItemIds,
                'all_special_plan_prices' => "[]",
                'public_status' => $publicStatus,
            ];
            $form->fill($f);
            $form->save();
        });
    }
}
