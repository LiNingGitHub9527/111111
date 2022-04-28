<?php

namespace App\Support\Upload;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;

class Uploader
{
    protected $savePathPrefix;

    protected $filePathPrefix;

    protected $imgPathPrefix;

    protected $customs;

    protected $envi;

    protected static $instance = null;

    public static function instance()
    {
        if (self::$instance != null) {
            return self::$instance;
        }
        $instance = new Uploader();
        self::$instance = $instance;
        return $instance;
    }

    public function __construct()
    {
        $this->savePathPrefix = 'uploads/';
        $this->filePathPrefix = 'files/';
        $this->imgPathPrefix = 'images/';
        $this->customs = 'customs/';
        $this->envi = config('app.env', 'local');
    }

    protected function fileName($ext, $length = 18)
    {
        $name = strtolower(str_random($length)) . '-' . time();
        return empty($ext) ? $name : $name . '.' . $ext;
    }

    public function uploadImg($file, $subPath = '', $rules = ['jpeg', 'jpg', 'png', 'gif'])
    {
        $savePath = $this->savePathPrefix . $this->imgPathPrefix . $subPath . '/' .Carbon::now()->toDateString();

        if ($file->isValid()) {
            // $name = $file->getClientOriginalName();
            $ext = strtolower($file->getClientOriginalExtension());
            if (!in_array($ext, $rules)) {
                return false;
            }
            return $file->store($savePath);
        }
        
        return false;
    }

    public function copyImg($fromPath, $subPath = '')
    {
        $savePath = $this->savePathPrefix . $this->imgPathPrefix . $subPath . '/' .Carbon::now()->toDateString();

        return Storage::copy($fromPath, $savePath);
    }

    public function uploadCustomFile($file)
    {
        $savePath = $this->envi . '/' . $this->savePathPrefix . $this->customs . Carbon::now()->toDateString();

        if ($file->isValid()) {
            $newName = $this->createFileName($file->getClientOriginalExtension());
            $filePath = Storage::putFileAs($savePath, new File($file), $newName, 'public');

            return $filePath;
        }
        return false;
    }

    public function uploadFile($file, $subPath = '')
    {
        $savePath = $this->savePathPrefix . $this->filePathPrefix . $subPath . '/' . Carbon::now()->toDateString();

        if ($file->isValid()) {
            $newName = $this->createFileName($file->getClientOriginalExtension());
            $filePath = Storage::putFileAs($savePath, new File($file), $newName, 'public');

            return $filePath;
        }
        return false;
    }

    public function uploadFileWithContent($content, $ext, $subPath = '')
    {
        $savePath = $this->savePathPrefix . $this->filePathPrefix . $subPath . '/' . Carbon::now()->toDateString();

        $name = $this->createFileName($ext);

        $result = Storage::put(
            $path = trim($savePath.'/'.$name, '/'), $content, 'public'
        );

        return $result ? $path : false;
    }

    public function uploadFileWithPathAndContent($oldPath, $suffix, $content)
    {
        $pathInfo = pathinfo($oldPath);
        $path = $pathInfo['dirname'] 
            . '/' . $pathInfo['filename'] . '_' . $suffix . '.' . $pathInfo['extension'];

        $result  = Storage::put($path, $content, 'public');

        return $result ? $path : false;
    }

    protected function createFileName($ext)
    {
        $timeCode = $this->to62(time());
        $token = $this->randStr();

        $newFileName = $timeCode . '_' . $token . '.' . $ext;

        return $newFileName;
    }

    public function to62($num)
    {
        $base = 62;
        $index = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $ret = '';
        for ($t = floor(log10($num) / log10($base)); $t >= 0; $t --) {
            $a = floor($num / pow($base, $t));
            $ret .= substr($index, $a, 1);
            $num -= $a * pow($base, $t);
        }
        return $ret;
    }

    public function from62($num)
    {
        $base = 62;
        $index = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $ret = 0;
        $len = strlen($num) - 1;
        for ($t = 0; $t <= $len; $t ++) {
            $ret += strpos($index, substr($num, $t, 1)) * pow($base, $len - $t);
        }
        return $ret;
    }

    public function randStr($len = 32)
    {
        $token = '';
        $str = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $size = strlen($str) - 1;
        for ($i = 0; $i < $len; $i++) {
            $token .= $str[mt_rand(0, $size)];
        }

        return $token;
    }
}