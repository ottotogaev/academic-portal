<?php

declare(strict_types=1);

namespace App\Core;

final class Slug
{
    public const PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /** Latin (Uzbek) text to an ASCII slug; apostrophe-like marks are dropped (o‘ -> o, g‘ -> g). */
    public static function make(string $text, string $fallback = 'item'): string
    {
        $text = mb_strtolower($text);
        $text = str_replace(["‘", "’", 'ʻ', 'ʼ', "'", '`'], '', $text);
        $text = (string) preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim($text, '-');
        $text = substr($text, 0, 100);
        $text = trim($text, '-');

        return $text === '' ? $fallback : $text;
    }

    public static function valid(string $slug): bool
    {
        return strlen($slug) <= 120 && preg_match(self::PATTERN, $slug) === 1;
    }
}
