<?php

namespace Exceptions;

class RouterException extends OrynException
{
    public function __construct(string $internalMessage, string $publicMessage = 'A routing error occurred.', int $statusCode = 500, array $context = [])
    {
        parent::__construct($internalMessage, $publicMessage, $statusCode, $context);
    }
}
