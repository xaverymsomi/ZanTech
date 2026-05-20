<?php

namespace Http;

final class Request
{
    public function __construct(
        private readonly array $query = [],
        private readonly array $request = [],
        private readonly array $files = [],
        private readonly array $server = [],
        private readonly array $cookies = [],
        private readonly array $json = [],
        private readonly string $rawBody = ''
    ) {}

    private static ?self $instance = null;

    public static function capture(): self
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $rawBody = (string)file_get_contents('php://input');
        $decoded = json_decode($rawBody, true);

        self::$instance = new self(
            $_GET,
            $_POST,
            $_FILES,
            $_SERVER,
            $_COOKIE,
            is_array($decoded) ? $decoded : [],
            $rawBody
        );

        return self::$instance;
    }

    public static function fake(
        array $query = [],
        array $request = [],
        array $server = [],
        array $json = [],
        string $rawBody = ''
    ): self {
        return new self($query, $request, [], $server, [], $json, $rawBody);
    }

    public function method(): string
    {
        return strtoupper((string)($this->server['REQUEST_METHOD'] ?? 'GET'));
    }

    public function path(): string
    {
        $raw = (string)($this->query['url']
            ?? $this->server['PATH_INFO']
            ?? $this->server['REQUEST_URI']
            ?? '/');

        $path = (string)(parse_url($raw, PHP_URL_PATH) ?? '/');
        return '/' . ltrim($path, '/');
    }

    public function uri(): string
    {
        return (string)($this->server['REQUEST_URI'] ?? $this->path());
    }

    public function ip(): string
    {
        return (string)($this->server['REMOTE_ADDR'] ?? 'unknown');
    }

    public function header(string $name, ?string $default = null): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        if (array_key_exists($key, $this->server)) {
            return (string)$this->server[$key];
        }

        $contentKey = strtoupper(str_replace('-', '_', $name));
        if (array_key_exists($contentKey, $this->server)) {
            return (string)$this->server[$contentKey];
        }

        return $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->json[$key] ?? $this->request[$key] ?? $this->query[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->request, $this->json);
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        return $key === null ? $this->query : ($this->query[$key] ?? $default);
    }

    public function post(?string $key = null, mixed $default = null): mixed
    {
        return $key === null ? $this->request : ($this->request[$key] ?? $default);
    }

    public function json(?string $key = null, mixed $default = null): mixed
    {
        return $key === null ? $this->json : ($this->json[$key] ?? $default);
    }

    public function server(?string $key = null, mixed $default = null): mixed
    {
        return $key === null ? $this->server : ($this->server[$key] ?? $default);
    }

    public function rawBody(): string
    {
        return $this->rawBody;
    }

    public function validate(array $rules): array
    {
        return \Validation\Validator::make($this->all())->validate($rules);
    }

    public function only(array $keys): array
    {
        return array_intersect_key($this->all(), array_flip($keys));
    }

    public function isAjax(): bool
    {
        // 1. Standard XHR Header
        if (isset($this->server['HTTP_X_REQUESTED_WITH'])
            && strtolower((string)$this->server['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        ) {
            return true;
        }

        // 2. JSON Request Content-Type Header
        $contentType = $this->server['CONTENT_TYPE'] ?? $this->server['HTTP_CONTENT_TYPE'] ?? '';
        if (str_contains(strtolower((string)$contentType), 'application/json')) {
            return true;
        }

        // 3. Accept JSON Header
        $accept = $this->server['HTTP_ACCEPT'] ?? '';
        if (str_contains(strtolower((string)$accept), 'application/json')) {
            return true;
        }

        return false;
    }
}
