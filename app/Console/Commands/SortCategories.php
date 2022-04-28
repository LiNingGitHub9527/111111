<?php

namespace App\Console\Commands;

use App\Support\Api\Signature\AuthSignature;
use Illuminate\Console\Command;
use App\Models\OriginalHotelHardCategory;

class SortCategories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shareg:sort-categories';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'sort OriginalHotelHardCategories';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $categoryList = OriginalHotelHardCategory::all();

        foreach ($categoryList as $category) {
            if ($category->id == 1) {
                $category->sort_num = 4;
            }            
            if ($category->id == 2) {
                $category->sort_num = 5;
            }
            if ($category->id == 3) {
                $category->sort_num = 6;
            }
            if ($category->id == 4) {
                $category->sort_num = 7;
            }
            if ($category->id == 5) {
                $category->sort_num = 8;
            }
            if ($category->id == 6) {
                $category->sort_num = 1;
            }
            if ($category->id == 7) {
                $category->sort_num = 2;
            }
            if ($category->id == 8) {
                $category->sort_num = 3;
            }
            $category->save();
        }

    }
}
