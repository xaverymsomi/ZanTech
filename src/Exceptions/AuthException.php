<?php

namespace Exceptions;

class AuthException extends OrynException
{
    public function __construct(string $message = 'Unauthorized')
    {
        parent::__construct($message, 'Unauthorized access.', 401);
    }
}
