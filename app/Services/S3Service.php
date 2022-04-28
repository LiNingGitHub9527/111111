<?php
namespace App\Services;

use Storage;

class S3Service
{
    public function __construct()
    {
        $this->disk = Storage::disk('s3');
    }

    public function getS3Image($path)
    {
        $imageUrl = $this->disk->url($path);
        return $imageUrl;
    }
}