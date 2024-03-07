<?php

namespace App\Exceptions;

class AuthenticationError extends ClientError
{
    public function __construct($message)
    {
        parent::__construct($message, 401);
    }
}
