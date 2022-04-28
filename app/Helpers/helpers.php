<?php

use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

if (!function_exists('ddlog')) {
    function ddlog($str)
    {
        $date = date("Y-m-d");
        $file = storage_path() . '/logs/debug-' . $date . '.log';
        ob_start();
        var_dump($str);
        $str = date("Y-m-d H:i:s") . "\n" . ob_get_contents();
        $str = str_replace("=>\n", "=>", $str);
        $result = file_put_contents($file, $str . "\n", FILE_APPEND | LOCK_EX);
        ob_end_clean();
    }
}

if (!function_exists('errlog')) {
    function errlog($str)
    {
        $date = date("Y-m-d");
        $file = storage_path() . '/logs/error-' . $date . '.log';
        ob_start();
        var_dump($str);
        $str = date("Y-m-d H:i:s") . "\n" . ob_get_contents();
        $str = str_replace("=>\n", "=>", $str);
        $result = file_put_contents($file, $str . "\n", FILE_APPEND | LOCK_EX);
        ob_end_clean();
    }
}

if (!function_exists('filename')) {
    function filename($ext = '', $length = 18)
    {
        $name = strtolower(str_random($length)) . time();
        return empty($ext) ? $name : $name . '.' . $ext;
    }
}

if (!function_exists('startsWith')) {
    function startsWith($haystack, $needle)
    {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }
}

if (!function_exists('endsWith')) {
    function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }
        return (substr($haystack, -$length) === $needle);
    }
}

if (!function_exists('safeArrayGet')) {
    function safeArrayGet($arr, $key)
    {
        return isset($arr[$key]) ? $arr[$key] : '';
    }
}

if (!function_exists('safeString2Array')) {
    function safeString2Array($str, $separator = ',')
    {
        $data = [];
        $tmp = explode($separator, $str);
        foreach (explode($separator, $str) as $segment) {
            $data[trim($segment)] = 1;
        }
        if (!empty($data)) {
            $data = array_keys($data);
        }
        return $data;
    }
}

if (!function_exists('safeDateFormat')) {
    function safeDateFormat($date, $format)
    {
        if (empty($date)) {
            return '';
        }
        return $date->format($format);
    }
}

if (!function_exists('randKey')) {
    function randKey($len)
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_-@#';
        mt_srand((float)microtime() * 1000000 * getmypid()); // seed the random number generater (must be done)

        $key = '';

        while (strlen($key) < $len)
            $key .= substr($chars, (mt_rand() % strlen($chars)), 1);

        return $key;
    }
}

if (!function_exists('photoUrl')) {
    function photoUrl($photo)
    {
        if (empty($photo)) {
            return '';
        }
        if (starts_with($photo, 'http')) {
            return $photo;
        }
        return Storage::disk('s3')->url($photo);
    }
}

if (!function_exists('fileUrl')) {
    function fileUrl($file)
    {
        if (empty($file)) {
            return '';
        }
        return Storage::disk('s3')->url($file);
    }
}

if (!function_exists('dateFormat')) {
    function dateFormat($date)
    {
        return date("Y.m.d", strtotime($date));
    }
}

if (!function_exists('dateFormatNj')) {
    function dateFormatNj($date)
    {
        return date('n/j', strtotime($date));
    }
}

if (!function_exists('arrayObjectVars')) {
    function arrayObjectVars($object)
    {
        $object = collect($object)
            ->transform(function ($obj) {
                return get_object_vars($obj);
            })
            ->toArray();

        return $object;
    }
}

if (!function_exists('curlPostData')) {
    function curlPostData($url, $header, $params, $isPost = false)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.93 Safari/537.36 Edg/90.0.818.51');
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        if ($isPost) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        }
        curl_setopt($curl, CURLOPT_TIMEOUT_MS, 60000);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT_MS, 60000);
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
}

if (!function_exists('showText')) {
    function showText($value, $replaceBlank = true)
    {
        if ($replaceBlank === true) {
            return nl2br(str_replace(' ', '&nbsp;', e($value)));
        }
        return nl2br(e($value));
    }
}

//多次元配列の特定のキー名の削除
if (!function_exists('arrayWalkDelete')) {
    function arrayWalkDelete($array, $delKeys)
    {
        foreach ($delKeys as $key) {
            array_walk($array, 'arrayColDelete', $key);
        }
        return $array;
    }
}

if (!function_exists('arrayColDelete')) {
    function arrayColDelete(&$row, $key, $keyName)
    {
        unset($row[$keyName]);
    }
}

if (!function_exists('makeAllDateByMonth')) {
    function makeAllDateByMonth($targetMonth)
    {
        $dateArr = [];
        $startDate = date('Y-m-d', strtotime('first day of ' . $targetMonth));
        $lastDate = date('Y-m-d', strtotime('last day of ' . $targetMonth));

        $currentDate = $startDate;

        while (strtotime($lastDate) >= strtotime($currentDate)) {
            array_push($dateArr, $currentDate);
            $currentDate = date('Y-m-d', strtotime('+1 day', strtotime($currentDate)));
        }

        return $dateArr;
    }
}

if (!function_exists('dateText')) {
    function dateText($date, $format = 'Y年m月d日'): ?string
    {
        if (empty($date)) {
            return null;
        }
        return Carbon::parse($date)->format($format);
    }
}

if (!function_exists('extractSpace')) {
    function extractSpace(string $input, int $limit = -1)
    {
        return preg_split('/[\p{Z}\p{Cc}]++/u', $input, $limit, PREG_SPLIT_NO_EMPTY);
    }
}

if (!function_exists('isHotel')) {
    function isHotel(\App\Models\Hotel $hotel): bool
    {
        $isHotel = false;
        $businessType = $hotel->business_type;
        if ($businessType == 1) {
            $isHotel = true;
        }

        return $isHotel;
    }
}

if (!function_exists('isReservationBusiness')) {
    function isReservationBusiness(\App\Models\Hotel $hotel): bool
    {
        $isReservationBusiness = false;
        $businessType = $hotel->business_type;
        if (in_array($businessType, [3, 4])) {
            $isReservationBusiness = true;
        }

        return $isReservationBusiness;
    }
}

if (!function_exists('getDepositType')) {
    function getDepositType($index)
    {
        switch ($index) {
            case 1:
                return "普通";
            case 2:
                return "当座";
            case 4:
                return "貯蓄";
            default:
                return "";
        }
    }
}

if (!function_exists('getMonthDayStart')) {
    function getMonthDayStart($month)
    {
        if (empty($month)) {
            $monthDayStart = now()->startOfMonth();
        } else {
            $monthDayStart = Carbon::parse($month)->startOfMonth();
        }
        $monthDayEnd = Carbon::parse($monthDayStart)->endOfMonth();
        return [$monthDayStart, $monthDayEnd];
    }
}

if (!function_exists('getDownloadFileResponseHeader')) {
    function getDownloadFileResponseHeader($fileName, $mime)
    {
        return [
            "Content-type"        => $mime,
            "Content-Disposition" => "attachment; filename={$fileName}; filename*=utf-8''{$fileName}",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0",
            'Access-Control-Expose-Headers' => 'Content-Disposition'
        ];
    }
}

if (!function_exists('getFormatedStartEndDatesForSaleMail')) {
    function getFormatedStartEndDatesForSaleMail($monthDayStart, $monthDayEnd)
    {
        return [$monthDayStart->format('Y-m-d'), $monthDayEnd->format('Y-m-d') . " 23:59:59:999"];
    }
}

if (!function_exists('getSaleMailAttachmentName')) {
    function getSaleMailAttachmentName($date)
    {
        $year = $date->format("Y");
        $month = $date->format("m");
        return  "{$year}年{$month}月_明細書.pdf";
    }
}

if (!function_exists('getSaleMailDownloadName')) {
    function getSaleMailDownloadName($hotel, $date)
    {
        $year = $date->format("Y");
        $month = $date->format("m");
        return  $hotel->name . "明細書_" . $year . $month . ".pdf";
    }
}

if (!function_exists('replceKeyWithValueFromText')) {
    function replceKeyWithValueFromText($text, $pairs)
    {
        $result = $text;
        foreach ($pairs as $key => $value) {
            $result = str_replace($key, $value, $result);
        }
        return $result;
    }
}
