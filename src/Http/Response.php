<?php

namespace Http;

final class Response
{
    public function __construct(
        private string $content = '',
        private int $status = 200,
        private array $headers = []
    ) {}

    public static function html(string $content, int $status = 200, array $headers = []): self
    {
        return new self($content, $status, ['Content-Type' => 'text/html; charset=UTF-8'] + $headers);
    }

    public static function json(array $data, int $status = 200, array $headers = []): self
    {
        return new self(
            (string)json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $status,
            ['Content-Type' => 'application/json; charset=UTF-8'] + $headers
        );
    }

    public static function redirect(string $location, int $status = 302): self
    {
        return new self('', $status, ['Location' => $location]);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function send(): void
    {
        http_response_code($this->status);

        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value, true);
        }

        echo $this->content;
    }
}
