<?php

declare(strict_types=1);

namespace Library;

/**
 * Zantech Request Helper
 * - Centralizes access to GET, POST, and JSON body
 * - Provides default values and basic filtering
 */
class Request
{
    private array $params;
    private array $json;

    public function __construct()
    {
        $this->params = array_merge($_GET, $_POST);
        
        $rawBody = file_get_contents('php://input');
        $decoded = json_decode($rawBody, true);
        $this->json = is_array($decoded) ? $decoded : [];
    }

    /**
     * Get value from any source (priority: JSON > POST/GET)
     */
    public function get(string $key, $default = null): mixed
    {
        return $this->json[$key] ?? $this->params[$key] ?? $default;
    }

    /**
     * Get value as string
     */
    public function string(string $key, string $default = ''): string
    {
        return (string)$this->get($key, $default);
    }

    /**
     * Get value as integer
     */
    public function int(string $key, int $default = 0): int
    {
        $val = $this->get($key, $default);
        return is_numeric($val) ? (int)$val : $default;
    }

    /**
     * Get value as boolean
     */
    public function bool(string $key, bool $default = false): bool
    {
        $val = $this->get($key, $default);
        if ($val === null) return $default;
        
        return in_array($val, [true, 1, '1', 'true', 'on', 'yes'], true);
    }

    /**
     * Get value as array
     */
    public function array(string $key, array $default = []): array
    {
        $val = $this->get($key, $default);
        return is_array($val) ? $val : $default;
    }

    /**
     * Get all inputs
     */
    public function all(): array
    {
        return array_merge($this->params, $this->json);
    }

    /**
     * Check if key exists
     */
    public function has(string $key): bool
    {
        return isset($this->json[$key]) || isset($this->params[$key]);
    }
}
