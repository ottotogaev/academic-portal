<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    /** @param array<string,string> $headers */
    public function __construct(
        private string $body = '',
        private int $status = 200,
        private array $headers = [],
    ) {
    }

    public static function html(string $body, int $status = 200): self
    {
        return new self($body, $status, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public static function text(string $body, int $status = 200): self
    {
        return new self($body, $status, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /** Only same-site absolute paths are allowed, which rules out open redirects and header injection. */
    public static function redirect(string $path, int $status = 302): self
    {
        if (preg_match('#^/(?!/)[^\\\\\r\n]*$#', $path) !== 1) {
            throw new \InvalidArgumentException('Redirect target must be a local path.');
        }

        return new self('', $status, ['Location' => $path]);
    }

    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;

        return $clone;
    }

    public function hasHeader(string $name): bool
    {
        return array_key_exists(strtolower($name), array_change_key_case($this->headers, CASE_LOWER));
    }

    public function status(): int
    {
        return $this->status;
    }

    public function body(): string
    {
        return $this->body;
    }

    /** @return array<string,string> */
    public function headers(): array
    {
        return $this->headers;
    }

    public function send(bool $headOnly = false): void
    {
        if (!headers_sent()) {
            header_remove('X-Powered-By');
            http_response_code($this->status);
            foreach ($this->headers as $name => $value) {
                header($name . ': ' . $value, true);
            }
        }
        if (!$headOnly) {
            echo $this->body;
        }
    }
}
