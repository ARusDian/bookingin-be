<?php

namespace App\Exceptions;

class InvariantError extends ClientError
{
    public function __construct($message)
    {
        parent::__construct($message);
    }
}
