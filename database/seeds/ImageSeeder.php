<?php

use App\Models\Image;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class ImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (config('migrate.seeds.skip_image')) {
            return;
        }

        //clean images
        //        Storage::deleteDirectory('uploads/images/0');
        //
        //        $date = now()->format('Y-m-d');
        //        Storage::makeDirectory('uploads/images/0/' . $date);

        $now = now();
        $date = $now->format('Y-m-d');
        $deletePath = storage_path('app/public/uploads/images/0');
        File::deleteDirectory($deletePath);
        $makePath = $deletePath . '/' . $date;
        File::makeDirectory($makePath, 0777, true, true);

        //add test 30 admin images
        factory(Image::class, 30)->make()->each(function ($image) {
            $image->client_id = 0;
            $image->hotel_id = 0;
            $image->save();
        });
    }
}
