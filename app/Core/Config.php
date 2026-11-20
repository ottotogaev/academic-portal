<?php

declare(strict_types=1);

namespace App\Core;

/** Reads config/<name>.php files lazily; keys use dot notation, e.g. "app.default_locale". */
final class Config
{
    /** @var array<string,mixed> */
    private array $cache = [];

    public function __construct(private string $dir)
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $parts = explode('.', $key);
        $file = array_shift($parts);

        if (preg_match('/^[a-z_]+$/', $file) !== 1) {
            return $default;
        }

        if (!array_key_exists($file, $this->cache)) {
            $path = $this->dir . '/' . $file . '.php';
            $this->cache[$file] = is_file($path) ? require $path : [];
        }

        $value = $this->cache[$file];
        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }

        return $value;
    }
}
