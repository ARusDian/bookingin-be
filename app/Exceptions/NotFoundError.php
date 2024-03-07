<?php

namespace App\Exceptions;

class NotFoundError extends ClientError
{
    public function __construct($message)
    {
        parent::__construct($message, 404);
    }
}
