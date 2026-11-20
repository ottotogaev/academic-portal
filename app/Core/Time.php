<?php

declare(strict_types=1);

namespace App\Core;

/** The database stores UTC; people enter and read dates in the configured zone (default Asia/Tashkent). */
final class Time
{
    public const DB = 'Y-m-d H:i:s';
    public const INPUT = 'Y-m-d\TH:i';

    public static function nowUtc(?int $now = null): string
    {
        return gmdate(self::DB, $now ?? time());
    }

    /** "2026-11-20T17:30" (zone time, from <input type="datetime-local">) to a UTC DB string; null when invalid. */
    public static function inputToUtc(string $value, string $zone): ?string
    {
        $local = \DateTimeImmutable::createFromFormat('!' . self::INPUT, $value, new \DateTimeZone($zone));
        $errors = \DateTimeImmutable::getLastErrors();
        if ($local === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            return null;
        }

        return $local->setTimezone(new \DateTimeZone('UTC'))->format(self::DB);
    }

    public static function utcToInput(?string $utc, string $zone): string
    {
        return self::convert($utc, $zone, self::INPUT);
    }

    public static function format(?string $utc, string $zone, string $format = 'd.m.Y'): string
    {
        return self::convert($utc, $zone, $format);
    }

    private static function convert(?string $utc, string $zone, string $format): string
    {
        if ($utc === null || $utc === '') {
            return '';
        }
        $date = \DateTimeImmutable::createFromFormat('!' . self::DB, $utc, new \DateTimeZone('UTC'));

        return $date === false ? '' : $date->setTimezone(new \DateTimeZone($zone))->format($format);
    }
}
