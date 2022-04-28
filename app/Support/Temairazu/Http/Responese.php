<?php

namespace App\Support\Temairazu\Http;

use Symfony\Component\HttpFoundation\Response as BaseResponse;


class Responese extends BaseResponse
{
    protected $data;

    public function __construct($data = null, int $status = 200, array $headers = [])
    {
        parent::__construct('', $status, $headers);

        if (null === $data) {
            $data = '';
        }

        $this->headers->set('Content-Type', 'text/plain');
        $this->charset = 'UTF-8';

        $this->setData($data);
    }

    public static function create($data = null, $status = 200, $headers = [])
    {
        return new static($data, $status, $headers);
    }

    public static function success()
    {
        return self::create('OK');
    }

    public static function error($message = '')
    {
        $data = 'NG,' . $message;
        return self::create($data);
    }

    public function setData($data)
    {
        if (is_array($data)) {
            $data = $this->formatCSV($data);
        } elseif ($data instanceof Arrayable) {
            $data = $this->formatCSV($data->toArray());
        }
        $this->data = $data;

        return $this->update();
    }

    protected function formatCSV($data)
    {
        if (!isset($data[0])) {
            $data = [$data];
        }
        $arr = [];
        foreach ($data as $value) {
            $arr[] = $this->formatRowCSV($value);
        }
        return implode("\n", $arr);
    }

    protected function formatRowCSV($row)
    {
        $arr = [];
        foreach ($row as $value) {
            if (is_array($value)) {
                $value = $this->formatSubCSV($value);
            } else {
                $value = $this->formatValue($value);
            }
            $arr[] = '"' . $value . '"';
        }
        return implode(',', $arr);
    }

    protected function formatSubCSV($data)
    {
        $arr = [];
        foreach ($data as $value) {
            $arr[] = $this->formatValue($value);
        }

        return implode(',ZZ', $arr);
    }

    protected function formatValue($value)
    {
        $value = str_replace('"', '', $value);
        $value = str_replace("\n", ',ZZ', $value);
        return $value;
    }

    protected function update()
    {
        return $this->setContent($this->data);
    }
}
