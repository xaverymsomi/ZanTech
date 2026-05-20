<?php

namespace Exceptions;

class ForbiddenException extends ZantechException
{
    public function __construct(string $message = 'Forbidden')
    {
        parent::__construct($message, 'You do not have permission to access this resource.', 403);
    }
}
