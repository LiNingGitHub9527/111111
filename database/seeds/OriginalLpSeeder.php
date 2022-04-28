<?php

use App\Models\Image;
use App\Models\Layout;
use App\Models\OriginalLp;
use App\Models\OriginalLpLayout;
use App\Models\OriginalLpLayoutCondition;
use Illuminate\Database\Seeder;

class OriginalLpSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (config('migrate.seeds.skip_original_lp')) {
            return;
        }

        //add test 30 priginal lp
        $layouts = Layout::where('public_status', 1)->limit(30)->get();
        $images = Image::where('client_id', 0)->limit(30)->get();
        $keyIndex = 0;
        factory(OriginalLp::class, 30)->make()->each(function ($lp) use ($layouts, $images, &$keyIndex) {
            $lp->cover_image = $images->random()->src();
            if ($lp->save()) {
                $num = rand(3, 8);
                $this->createLpLayouts($lp, $layouts, $num, $keyIndex);
            }
        });
    }

    public function createLpLayouts($lp, $layouts, $num, &$keyIndex)
    {
        $timeNumber = time() * 1000;
        for ($i = 0; $i < $num; $i++) {
            $layout = $layouts->random();
            $lpLayout = new OriginalLpLayout;
            $lpLayout->original_lp_id = $lp->id;
            $lpLayout->layout_id = $layout->id;
            $lpLayout->component_id = $layout->component_id;
            $lpLayout->render_html = $layout->render_html;
            $lpLayout->layout_order = $i;
            $lpLayout->unique_key = $timeNumber + $keyIndex;
            if ($lpLayout->save()) {
                if ($lpLayout->component->typeName() == 'popup') {
                    $this->createLpLayoutCondition($lpLayout);
                }
            }
            $keyIndex++;
        }
    }

    protected function createLpLayoutCondition($lpLayout)
    {
        $lpLayoutCondition = new OriginalLpLayoutCondition;
        $lpLayoutCondition->original_lp_layout_id = $lpLayout->id;
        $type = rand(0, 2);
        $lpLayoutCondition->start_point_type = $type;
        if ($type == 1) {
            $lpLayoutCondition->default_start_point_seconds = rand(3, 8);
        } else if ($type == 2) {
            $lpLayoutCondition->default_start_point_scroll = rand(5, 20) * 10;
        }
        $lpLayoutCondition->save();
    }
}
