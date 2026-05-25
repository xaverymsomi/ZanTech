<?php

namespace Exceptions;

class DatabaseException extends ZantechException
{
    public function __construct(string $technical, string $public = 'A Database error occurred.', int $status = 500, array $context = [], ?\Throwable $previous = null)
    {
        parent::__construct($technical, $public, $status, $context, $previous);
    }
}
