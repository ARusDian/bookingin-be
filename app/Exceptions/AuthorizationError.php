<?php

namespace App\Exceptions;

class AuthorizationError extends ClientError
{
    public function __construct($message)
    {
        parent::__construct($message, 403);
    }
}
