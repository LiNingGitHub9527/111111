<?php

use Illuminate\Database\Seeder;
use App\Models\LpCategory;

class LpCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (config('migrate.seeds.skip_lp_category')) {
            return;
        }
        $this->createLpCategory('クリスマス企画', 1);
        $this->createLpCategory('通常予約ページ', 1);
        $this->createLpCategory('ハロウィン企画', 1);
        $this->createLpCategory('温泉旅館', 0);
        $this->createLpCategory('ライフスタイルホテル', 1);
    }

    protected function createLpCategory($name, $isEffective)
    {
        $category = new LpCategory;
        $category->name = $name;
        $category->is_effective = $isEffective;
        $category->save();
    }
}
