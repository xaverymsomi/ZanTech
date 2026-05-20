<?php

namespace Services;

use Logging\Log;
use Throwable;

final class DbErrorHandler
{
    public static function logException(Throwable $e, string $context, array $meta = []): void
    {
        Log::sysErr([
            'context' => $context,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'meta' => $meta,
        ]);
    }
}
