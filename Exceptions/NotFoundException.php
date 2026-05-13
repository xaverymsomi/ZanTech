<?php

namespace Exceptions;

class NotFoundException extends ZantechException
{
    public function __construct(string $message = 'Resource not found')
    {
        parent::__construct(
            $message,
            'Resource not found.',
            404
        );
    }
}
