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
        private array $post = [],
        public readonly string $ip = '',
        private array $files = [],
    ) {
    }

    public static function fromGlobals(): self
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $queryStart = strpos($uri, '?');
        $rawPath = $queryStart === false ? $uri : substr($uri, 0, $queryStart);

        // REMOTE_ADDR only: forwarded-for headers are client-controlled and would defeat login throttling.
        return self::create(
            (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $rawPath,
            $_GET,
            $_POST,
            (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
            $_FILES
        );
    }

    /** @param array<string,mixed> $query @param array<string,mixed> $post */
    public static function create(string $method, string $rawPath, array $query = [], array $post = [], string $ip = '', array $files = []): self
    {
        $path = rawurldecode($rawPath);
        if (preg_match('//u', $path) !== 1 || preg_match('/[\x00-\x1F\x7F]/', $path) === 1) {
            throw new HttpException(400);
        }

        return new self(strtoupper($method), self::normalize($path), $query, [], $post, $ip, $files);
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

    /** Form field as a string; arrays and missing fields give the default. */
    public function input(string $name, string $default = ''): string
    {
        $value = $this->post[$name] ?? null;

        return is_string($value) ? $value : $default;
    }

    /** One uploaded file entry as PHP provides it, or null. Multi-file fields are not supported. */
    public function file(string $name): ?array
    {
        $file = $this->files[$name] ?? null;

        return is_array($file) && isset($file['error']) && !is_array($file['error']) ? $file : null;
    }
}
