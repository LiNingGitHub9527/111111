<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Throwable;

class PmsApiReturnException extends Exception
{

    private $obj;

    public function __construct(string $message = "", $obj = null, int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->obj = $obj;
    }

    public function render(Request $request, Exception $e)
    {
        return parent::render($request, $e);
    }

    public function getObj()
    {
        return json_encode($this->obj);
    }

}