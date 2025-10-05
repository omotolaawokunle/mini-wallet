<?php

namespace App\Exceptions;

use Exception;

class AccountFlaggedException extends Exception
{
    public function __construct(string $reason = 'Your account has been flagged due to balance discrepancy. Please contact support.')
    {
        parent::__construct($reason);
    }
}

