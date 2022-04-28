<?php

use Illuminate\Database\Seeder;
use App\Models\Component;

class ComponentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (config('migrate.seeds.skip_component')) {
            return;
        }

        //add test 30 components
        factory(Component::class, 30)->make()->each(function($component) {
            $component->html = '<div nocode-component-container></div>';
            $component->save();
        });
    }
}
