<?php

declare(strict_types=1);

namespace Exceptions;

use Throwable;

final class RedirectException extends ZantechException
{
    public function __construct(string $to, int $statusCode = 302, array $context = [], ?Throwable $previous = null)
    {
        // Public message is not needed; handler will redirect.
        parent::__construct(
            "Redirect to {$to}",
            'Redirecting...',
            $statusCode,
            array_merge(['redirect' => $to], $context),
            $previous
        );
    }
}
