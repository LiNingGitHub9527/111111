<?php

use Illuminate\Database\Seeder;
use App\Models\Form;

class FormSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(Form $form)
    {
        if (config('migrate.seeds.skip_form')) {
            return;
        }
        $insertData = $this->getInsertData();
        $form->insert($insertData);

    }

    public function getInsertData()
    {
        $insertData = [
            [
                'client_id' => 1,
                'hotel_id' => 1,
                'name' => 'Hotel Mei',
                'form_parts_ids' => "[1]",
                'is_deadline' => 0,
                'deadline_start' => new DateTime(),
                'deadline_end' => new DateTime(),
                'is_sale_period' => 0,
                'sale_period_start' => NULL,
                'sale_period_end' => NULL,
                'is_plan' => 0,
                'plan_ids' => "[1,2,3]",
                'is_room_type' => 0,
                'room_type_ids' => "[1,2,3,4]",
                'is_special_price' => 1,
                'is_hand_input' => 1,
                'hand_input_room_prices' => '[{"room_type_id": 1, "price": 2000},{"room_type_id": 2, "price": 3000}, {"room_type_id": 3, "price": 4000}]',
                'is_all_plan' => 0,
                'all_plan_price' => "[]",
                'special_plan_prices' => "[]",
                'custom_form_item_ids' => "[]",
                'all_special_plan_prices' => "[]",
                'public_status' => 1
            ],
            [
                'client_id' => 1,
                'hotel_id' => 1,
                'name' => 'Hotel Mei',
                'form_parts_ids' => "[1]",
                'is_deadline' => 0,
                'deadline_start' => new DateTime(),
                'deadline_end' => new DateTime(),
                'is_sale_period' => 0,
                'sale_period_start' => NULL,
                'sale_period_end' => NULL,
                'is_plan' => 0,
                'plan_ids' => "[1,2,3]",
                'is_room_type' => 0,
                'room_type_ids' => "[1,2,3,4]",
                'is_special_price' => 1,
                'is_hand_input' => 0,
                'hand_input_room_prices' => '[]',
                'is_all_plan' => 1,
                'all_plan_price' => '{"num": 2000, "unit": 1, "up_off": 1}', //unit 0: %, 1: 円, up_off 1:UP, 2:OFF
                'special_plan_prices' => "[]",
                'custom_form_item_ids' => "[]",
                'all_special_plan_prices' => "[]",
                'public_status' => 1
            ],
            [
                'client_id' => 1,
                'hotel_id' => 1,
                'name' => 'Hotel Mei',
                'form_parts_ids' => "[1]",
                'is_deadline' => 0,
                'deadline_start' => new DateTime(),
                'deadline_end' => new DateTime(),
                'is_sale_period' => 0,
                'sale_period_start' => NULL,
                'sale_period_end' => NULL,
                'is_plan' => 0,
                'plan_ids' => "[1,2,3]",
                'is_room_type' => 0,
                'room_type_ids' => "[1,2,3,4]",
                'is_special_price' => 1,
                'is_hand_input' => 0,
                'hand_input_room_prices' => '[]',
                'is_all_plan' => 0,
                'all_plan_price' => '[]', 
                'special_plan_prices' => '[
                        {"plan_id": 14, "num": 50, "unit": 0, "up_off": 2}
                    ]',
                'custom_form_item_ids' => "[]",
                'all_special_plan_prices' => "[]",
                'public_status' => 1
            ],
            [
                'client_id' => 1,
                'hotel_id' => 1,
                'name' => 'Hotel Mei',
                'form_parts_ids' => "[1]",
                'is_deadline' => 0,
                'deadline_start' => new DateTime(),
                'deadline_end' => new DateTime(),
                'is_sale_period' => 0,
                'sale_period_start' => NULL,
                'sale_period_end' => NULL,
                'is_plan' => 0,
                'plan_ids' => "[1,2,3]",
                'is_room_type' => 0,
                'room_type_ids' => "[1,2,3,4]",
                'is_special_price' => 1,
                'is_hand_input' => 0,
                'hand_input_room_prices' => '[]',
                'is_all_plan' => 0,
                'all_plan_price' => '[]', 
                'special_plan_prices' => '[
                        {"plan_id": 14, "num": 2500, "unit": 1, "up_off": 1}
                    ]',
                'custom_form_item_ids' => "[]",
                'all_special_plan_prices' => "[]",
                'public_status' => 1
            ],
        ];
        return $insertData;
    }
}
