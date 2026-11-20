<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Minimal .env loader. Values from the file take precedence, the real process environment is the fallback.
 * Quote values that contain " #" (for example passwords).
 */
final class Env
{
    /** @var array<string,string> */
    private static array $values = [];

    public static function load(string $file): void
    {
        if (!is_file($file) || !is_readable($file)) {
            return;
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            if (str_starts_with($line, 'export ')) {
                $line = ltrim(substr($line, 7));
            }
            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }
            $key = trim(substr($line, 0, $pos));
            if (preg_match('/^[A-Z][A-Z0-9_]*$/', $key) !== 1) {
                continue;
            }
            self::$values[$key] = self::parseValue(trim(substr($line, $pos + 1)));
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        if (array_key_exists($key, self::$values)) {
            return self::$values[$key];
        }
        $env = getenv($key);

        return $env !== false ? $env : $default;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key);
        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    public static function set(string $key, string $value): void
    {
        self::$values[$key] = $value;
    }

    public static function reset(): void
    {
        self::$values = [];
    }

    private static function parseValue(string $raw): string
    {
        if ($raw === '') {
            return '';
        }
        $quote = $raw[0];
        if ($quote === '"' || $quote === "'") {
            $end = strpos($raw, $quote, 1);
            if ($end !== false) {
                return substr($raw, 1, $end - 1);
            }
        }
        $comment = strpos($raw, ' #');
        if ($comment !== false) {
            $raw = substr($raw, 0, $comment);
        }

        return rtrim($raw);
    }
}
