<?php

namespace App\Exceptions;

use RuntimeException;

class PlanLimitExceededException extends RuntimeException
{
    public function __construct(string $message = 'Your current plan limit has been reached.')
    {
        parent::__construct($message);
    }
}
