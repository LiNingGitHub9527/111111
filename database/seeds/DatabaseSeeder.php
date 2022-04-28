<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(AdminSeeder::class);
        $this->call(RatePlanSeeder::class);
        $this->call(ComponentSeeder::class);
        $this->call(ImageSeeder::class);
        $this->call(LpCategorySeeder::class);
        $this->call(PlanSeeder::class);
        $this->call(CancelPolicySeeder::class);
        $this->call(LpSeeder::class);
        $this->call(FormSeeder::class);
        $this->call(ClientSeeder::class);
        $this->call(RoomStockSeeder::class);
        $this->call(PlanRoomTypeRateSeeder::class);
        $this->call(PlanRoomTypeRatePerClassSeeder::class);
        $this->call(LayoutSeeder::class);
        $this->call(OriginalLpSeeder::class);
        // $this->call(LayoutSeeder::class);
        // $this->call(OriginalLpSeeder::class);
        // $this->call(ClientSeeder::class);
        // $this->call(FormSeeder::class);
        $this->call(HotelNoteSeeder::class);
    }
}
