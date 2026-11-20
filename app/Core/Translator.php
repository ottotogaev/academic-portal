<?php

declare(strict_types=1);

namespace App\Core;

/**
 * UI strings from app/Lang/<locale>.php. A locale without a file is "unavailable":
 * translations are never invented or silently replaced with another language.
 */
final class Translator
{
    /** @var array<string,array<string,mixed>> */
    private array $cache = [];

    /** @param list<string> $locales allowed locales */
    public function __construct(private string $dir, private array $locales)
    {
    }

    public function available(string $locale): bool
    {
        return in_array($locale, $this->locales, true) && is_file($this->dir . '/' . $locale . '.php');
    }

    /** Dot-notation lookup. A missing key returns the key itself so gaps are visible. */
    public function get(string $locale, string $key, array $replace = []): string
    {
        $value = $this->load($locale);
        foreach (explode('.', $key) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $key;
            }
            $value = $value[$part];
        }
        if (!is_string($value)) {
            return $key;
        }
        foreach ($replace as $name => $replacement) {
            $value = str_replace('{' . $name . '}', (string) $replacement, $value);
        }

        return $value;
    }

    /** @return array<string,mixed> */
    private function load(string $locale): array
    {
        if (!$this->available($locale)) {
            return [];
        }

        return $this->cache[$locale] ??= (array) require $this->dir . '/' . $locale . '.php';
    }
}
