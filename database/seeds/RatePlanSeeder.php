<?php

use Illuminate\Database\Seeder;
use App\Models\RatePlan;

class RatePlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (config('migrate.seeds.skip_rateplan')) {
            return;
        }
        
        $this->createRatePlan('無料', 0, 1);
        $this->createRatePlan('料金プラン名1(¥5,000)', 5000, 1);
        $this->createRatePlan('料金プラン名2(¥12,000)', 12000, 1);
        $this->createRatePlan('料金プラン名3(¥30,000)', 30000, 1);
    }

    protected function createRatePlan($name, $fee, $isEffective)
    {
        $ratePlan = new RatePlan;
        $ratePlan->name = $name;
        $ratePlan->fee = $fee;
        $ratePlan->is_effective = $isEffective;
        $ratePlan->save();
    }
}
