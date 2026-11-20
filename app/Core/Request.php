<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    /**
     * @param array<string,mixed>  $query
     * @param array<string,string> $params route parameters
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        private array $query = [],
        private array $params = [],
    ) {
    }

    public static function fromGlobals(): self
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $queryStart = strpos($uri, '?');
        $rawPath = $queryStart === false ? $uri : substr($uri, 0, $queryStart);

        return self::create((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'), $rawPath, $_GET);
    }

    /** @param array<string,mixed> $query */
    public static function create(string $method, string $rawPath, array $query = []): self
    {
        $path = rawurldecode($rawPath);
        if (preg_match('//u', $path) !== 1 || preg_match('/[\x00-\x1F\x7F]/', $path) === 1) {
            throw new HttpException(400);
        }

        return new self(strtoupper($method), self::normalize($path), $query);
    }

    /** Leading slash, collapsed duplicate slashes, no trailing slash (except the root). */
    public static function normalize(string $path): string
    {
        return '/' . trim((string) preg_replace('#/+#', '/', $path), '/');
    }

    /** @param array<string,string> $params */
    public function withParams(array $params): self
    {
        $clone = clone $this;
        $clone->params = $params;

        return $clone;
    }

    public function param(string $name): ?string
    {
        return $this->params[$name] ?? null;
    }

    public function query(string $name, ?string $default = null): ?string
    {
        $value = $this->query[$name] ?? null;

        return is_string($value) ? $value : $default;
    }
}
