<?php

use App\Models\Image;
use App\Support\Faker\Image as ImageFaker;
use Faker\Generator as Faker;

$factory->define(Image::class, function (Faker $faker, array $arguments = []) {
    $now = now();
    $name = implode('-', $faker->words(3, false)) . '.jpg';
    //LoremPixel.com is used by $faker->image to download files from the service
    //The service is down, so we can't use $faker->image() at the moment.
    //https://github.com/fzaninotto/Faker/issues/1884
    $date = $now->format('Y-m-d');
    $subPath = 'uploads/images/0/' . $date;
    $storePath = storage_path('app/public/' . $subPath);
    $fileName = ImageFaker::image($storePath, 640, 480, false);

    return [
        'name' => $name,
        'path' => config('app.url') . '/storage/' . $subPath . '/' . $fileName,
        'created_at' => $now,
        'updated_at' => $now,
    ];
});
