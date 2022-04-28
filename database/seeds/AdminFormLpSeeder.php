<?php

use App\Models\CancelPolicy;
use App\Models\Lp;
use App\Models\Form;
use Illuminate\Database\Seeder;

class AdminFormLpSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(Lp $lp, Form $form)
    {
        $form = Form::create([
            'client_id' => 0,
            'hotel_id' => 0,
            'name' => 'Admin',
            'form_parts_ids' => "[1]",
            'is_deadline' => 0,
            'deadline_start' => NULL,
            'deadline_end' => NULL,
            'is_sale_period' => 0,
            'sale_period_start' => NULL,
            'sale_period_end' => NULL,
            'is_plan' => 0,
            'plan_ids' => "[1,2,3]",
            'is_room_type' => 0,
            'room_type_ids' => "[1,2,3,4]",
            'is_special_price' => 0,
            'is_hand_input' => 0,
            'hand_input_room_prices' => '[]',
            'is_all_plan' => 0,
            'all_plan_price' => '[]', 
            'special_plan_prices' => '[]',
            'custom_form_item_ids' => "[]",
            'all_special_plan_prices' => "[]",
            'public_status' => 1,
            'prepay' => 0,
            'cancel_policy_id' => null
        ]);
        $lp->insert([
            'client_id' => 0,
            'hotel_id' => 0,
            'original_lp_id' => 0,
            'title' => 'Admin Lp',
            'cover_image' => 'staging/staff/20200709/202007091932535f06f25583e2f.jpg',
            'form_id' => $form->id,
            'public_status' => 1,
            'url_param' => 'AdminUrlParam',
            'created_at' => now(),
        ]);
    }
}
