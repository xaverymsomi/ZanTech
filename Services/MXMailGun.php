<?php

namespace Services;

use Logging\Log;

final class MXMailGun
{
    public function sendEmail(
        int|string $template,
        string $recipient,
        mixed $attachment = null,
        array $tokens = [],
        array $values = []
    ): bool {
        Log::sysLog([
            'event' => 'MAILGUN_SEND_REQUESTED',
            'template' => $template,
            'recipient' => LogSanitizer::maskEmail($recipient),
            'tokens' => $tokens,
            'attachment' => $attachment !== null,
        ]);

        return true;
    }
}
