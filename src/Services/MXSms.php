<?php

namespace Services;

use Logging\Log;
use Foundation\Routing\RouterSecurity;

final class MXSms
{
    public function sendTemplateSMS(
        int|string $template,
        string $recipient,
        int|string|null $userId = null,
        mixed $attachment = null,
        mixed $sender = null,
        array $tokens = [],
        array $values = [],
        int|string|null $priority = null
    ): bool {
        // Mask recipient phone number (e.g. +255******1234)
        $maskedRecipient = preg_replace('/(\+?\d{1,3})?\d{6}(\d{4})/', '$1******$2', $recipient);
        if ($maskedRecipient === $recipient) {
            $len = strlen($recipient);
            $maskedRecipient = $len > 4 ? str_repeat('*', $len - 4) . substr($recipient, -4) : str_repeat('*', $len);
        }

        // Redact sensitive tokens and values (e.g. password, pin, token, otp keys)
        $redactedTokens = RouterSecurity::redactSensitive($tokens);
        $redactedValues = RouterSecurity::redactSensitive($values);

        Log::sysLog([
            'event'     => 'SMS_SEND_REQUESTED',
            'template'  => $template,
            'recipient' => $maskedRecipient,
            'user_id'   => $userId,
            'tokens'    => $redactedTokens,
            'values'    => $redactedValues,
            'priority'  => $priority,
        ]);

        return true;
    }
}
