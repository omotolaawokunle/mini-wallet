<?php

namespace App\Exceptions;

use Exception;

class TransferException extends Exception
{
    public function __construct(public string $message = 'Transfer failed'){
        parent::__construct($message);
    }
}
