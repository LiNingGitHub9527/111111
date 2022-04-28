<?php

namespace App\Services;

use Maatwebsite\Excel\Excel;
use App\Support\Office\Excel\Export\SimpleExport;

class ExcelService
{
    private static $instance = null;

    public static function instance()
    {
        if (self::$instance !== null) {
            return self::$instance;
        }
        $instance = new ExcelService();
        self::$instance = $instance;
        return $instance;
    }

    public function __construct()
    {
    }

    public function simpleDownload($fileName, $headData, $data)
    {
        if (!$this->endsWith($fileName, '.csv')) {
            $fileName .= '.csv';
        }
        $simpleExport = new SimpleExport($headData, $data);
        $headers = [
            'Access-Control-Expose-Headers' => 'Content-Disposition'
        ];
        return $simpleExport->download($fileName, Excel::CSV, $headers);
    }

    public function simpleDownloadXls($fileName, $headData, $data, $options = [])
    {
        if (!$this->endsWith($fileName, '.xls')) {
            $fileName .= '.xls';
        }
        $simpleExport = new SimpleExport($headData, $data, $options);
        return $simpleExport->download($fileName, Excel::XLS,  [
            // 'Content-Type' => 'text/csv'
            'Content-Type'=>'application/zip;charset=utf-8'
        ]);
    }

    protected function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }
        return (substr($haystack, -$length) === $needle);
    }
}
