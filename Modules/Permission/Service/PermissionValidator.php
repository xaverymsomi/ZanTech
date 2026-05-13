<?php

namespace Modules\Permission\Service;
use Exception;
use Loggers\Log;

class PermissionValidator
{
    public static function readJsonBody(): mixed
    {
        $raw = file_get_contents('php://input') ?: '';
        if ($raw === '') return null;

        $decoded = json_decode($raw, true);

        // If invalid JSON, return null (caller will validate)
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        // Can be array OR scalar (int/string/bool/null)
        return $decoded;
    }

    public static function validateDomainAndRowValue(array $payload): array
    {
        $errors = [];

        if (empty($payload['domain']) || !is_string($payload['domain'])) {
            $errors['domain'] = 'domain is required';
        } else {
            if (!preg_match('/^[A-Za-z0-9_]+$/', $payload['domain'])) {
                $errors['domain'] = 'domain contains invalid characters';
            }
        }

        if (empty($payload['id']) || !is_string($payload['id'])) {
            $errors['id'] = 'id is required';
        } else {
            if (strlen($payload['id']) < 10 || strlen($payload['id']) > 100) {
                $errors['id'] = 'id length invalid';
            }
            if (preg_match('/[\'";]/', $payload['id'])) {
                $errors['id'] = 'id contains invalid characters';
            }
        }

        return $errors;
    }

    public static function validateIntId($value, string $field): array
    {
        if (is_array($value)) return [$field => 'must be an integer'];
        if (!is_numeric($value)) return [$field => 'must be an integer'];
        if ((int)$value <= 0) return [$field => 'must be > 0'];
        return [];
    }

    public static function validateUserGroupPayload(array $payload): array
    {
        $errors = [];
        if (empty($payload['id']) || !is_string($payload['id'])) {
            $errors['id'] = 'user row_value is required';
        }
        if (!isset($payload['new_data']) || !is_array($payload['new_data'])) {
            $errors['new_data'] = 'new_data must be an array';
        } else {
            $errors += self::validateCheckedRows($payload['new_data']);
        }
        return $errors;
    }

    public static function validateGroupPermissionPayload(array $payload): array
    {
        $errors = [];
        if (!isset($payload['id']) || !is_numeric($payload['id']) || (int)$payload['id'] <= 0) {
            $errors['id'] = 'group id must be integer > 0';
        }
        if (!isset($payload['new_data']) || !is_array($payload['new_data'])) {
            $errors['new_data'] = 'new_data must be an array';
        } else {
            $errors += self::validateCheckedRows($payload['new_data']);
        }
        return $errors;
    }

    public static function validateUserPermissionPayload(array $payload): array
    {
        $errors = self::validateDomainAndRowValue($payload);
        if (!isset($payload['new_data']) || !is_array($payload['new_data'])) {
            $errors['new_data'] = 'new_data must be an array';
        } else {
            $errors += self::validateCheckedRows($payload['new_data']);
        }
        return $errors;
    }

    public static function validateCreateGroupPayload(array $payload): array
    {
        $errors = [];
        if (empty($payload['name']) || !is_string($payload['name'])) {
            $errors['name'] = 'name is required';
        } elseif (mb_strlen($payload['name']) > 120) {
            $errors['name'] = 'name too long';
        }
        return $errors;
    }

    public static function validateCreatePermissionPayload(array $payload): array
    {
        $errors = [];
        if (empty($payload['display_name']) || !is_string($payload['display_name'])) {
            $errors['display_name'] = 'display_name is required';
        }
        if (empty($payload['name']) || !is_string($payload['name'])) {
            $errors['name'] = 'name is required';
        }
        if (!isset($payload['section_id']) || !is_numeric($payload['section_id']) || (int)$payload['section_id'] <= 0) {
            $errors['section_id'] = 'section_id must be integer > 0';
        }
        return $errors;
    }

    public static function validateCreateSectionPayload(array $payload): array
    {
        $errors = [];
        if (empty($payload['txt_name']) || !is_string($payload['txt_name'])) {
            $errors['txt_name'] = 'txt_name is required';
        } elseif (mb_strlen($payload['txt_name']) > 120) {
            $errors['txt_name'] = 'txt_name too long';
        }
        return $errors;
    }

    private static function validateCheckedRows(array $rows): array
    {
        foreach ($rows as $i => $row) {
            if (!is_array($row) || count($row) < 2) {
                return ['new_data' => "invalid row at index {$i}"];
            }
            $allowed = (int)$row[0];
            $id = $row[1];

            if (!in_array($allowed, [0, 1], true)) {
                return ['new_data' => "invalid isAllowed at index {$i}"];
            }
            if (!is_numeric($id) || (int)$id <= 0) {
                return ['new_data' => "invalid fk_id at index {$i}"];
            }
        }
        return [];
    }

    public static function sanitizeName(string $name, int $maxLen = 120): string
    {
        $name = trim($name);
        $name = strip_tags($name);
        if (mb_strlen($name) > $maxLen) {
            $name = mb_substr($name, 0, $maxLen);
        }
        return $name;
    }

    public static function sanitizePermissionKey(string $key): string
    {
        $key = strtolower(trim($key));

        // Convert whitespace and hyphens to underscores
        $key = preg_replace('/[\s\-]+/', '_', $key);

        // Remove anything not allowed
        $key = preg_replace('/[^a-z0-9_]/', '', $key);

        // Collapse multiple underscores and trim
        $key = preg_replace('/_+/', '_', $key);
        return trim($key, '_');
    }

    public static function assertSafeDomain(string $domain): void
    {
        // Prevent table injection (domain used to locate a table)
        if (!preg_match('/^[A-Za-z0-9_]+$/', $domain)) {
            Log::sysLog('[PERMISSION][' . $domain . '] invalid domain');
        }
    }
}
