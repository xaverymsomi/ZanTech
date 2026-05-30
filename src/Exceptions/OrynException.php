<?php

namespace Exceptions;

use Exception;
use Throwable;

/**
 * Base exception for all Oryn framework errors.
 *
 * - Wraps native Throwable
 * - Adds safe public message
 * - Adds HTTP status code
 * - Allows structured logging
 */
class OrynException extends Exception
{
    protected int $statusCode;
    protected string $publicMessage;
    protected array $context = [];

    public function __construct(string $message, string $publicMessage = 'An internal error occurred.', int $statusCode = 500, array $context = [], ?Throwable $previous = null) {
        parent::__construct($message, 0, $previous);

        $this->publicMessage = $publicMessage;
        $this->statusCode = $statusCode;
        $this->context = $context;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getPublicMessage(): string
    {
        return $this->publicMessage;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}

// Backward-compatibility alias so any code still referencing ZantechException continues to work.
class_alias(OrynException::class, 'Exceptions\ZantechException');
