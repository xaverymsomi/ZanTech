<?php

namespace Exceptions;

class ValidationException extends OrynException
{
    public function __construct(array $errors)
    {
        parent::__construct('Validation failed', 'Invalid input provided.', 422, ['errors' => $errors]);
    }
}
