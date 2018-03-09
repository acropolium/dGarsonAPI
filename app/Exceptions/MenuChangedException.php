<?php

namespace App\Exceptions;

class MenuChangedException extends \Exception {
    public function __construct($message = 'Menu has changed', $code = 409)
    {
        parent::__construct(trans('messages.'.$message), $code);

    }
}