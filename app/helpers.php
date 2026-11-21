<?php

declare(strict_types=1);

use App\Core\App;

/** Escape a value for HTML output (text and quoted attributes). */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function app(): App
{
    return App::current();
}

function locale(): string
{
    return App::current()->locale();
}

/** Translate a UI string for the current locale. */
function t(string $key, array $replace = []): string
{
    $app = App::current();

    return $app->translator()->get($app->locale(), $key, $replace);
}

/** Locale-prefixed public path, e.g. url('about') => /uz/about, url() => /uz/. */
function url(string $path = '', ?string $locale = null): string
{
    $locale ??= App::current()->locale();
    $path = trim($path, '/');

    return '/' . $locale . ($path === '' ? '/' : '/' . $path);
}

/** Public asset path with a cache-busting version when the file exists. */
function asset(string $path): string
{
    $path = ltrim($path, '/');
    $url = '/assets/' . $path;

    if (defined('APP_PUBLIC_PATH')) {
        $file = APP_PUBLIC_PATH . '/assets/' . $path;
        if (is_file($file)) {
            $url .= '?v=' . filemtime($file);
        }
    }

    return $url;
}

/** Render a view partial and return trusted HTML. */
function partial(string $template, array $data = []): string
{
    return App::current()->view()->render($template, $data);
}

/** Hidden CSRF input for admin forms. */
function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(App::current()->csrf()->token()) . '">';
}

/** Plain text to safe HTML: blank lines separate paragraphs, single newlines become <br>. No markup is accepted. */
function text_html(?string $text): string
{
    $paragraphs = preg_split('/\n{2,}/', trim(str_replace("\r\n", "\n", (string) $text))) ?: [];
    $out = '';
    foreach ($paragraphs as $paragraph) {
        if (trim($paragraph) !== '') {
            $out .= '<p>' . nl2br(e($paragraph), false) . '</p>';
        }
    }

    return $out;
}

/** Date from a UTC database value, in the configured zone. */
function format_date(?string $utc, string $format = 'd.m.Y'): string
{
    return \App\Core\Time::format($utc, (string) App::current()->config()->get('app.timezone', 'Asia/Tashkent'), $format);
}

/** "1.5 MB" style size; decimal comma is avoided on purpose. */
function format_bytes(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    if ($bytes < 1048576) {
        return round($bytes / 1024) . ' KB';
    }

    return number_format($bytes / 1048576, 1, '.', '') . ' MB';
}

/** One field of the teacher profile for the current locale ('' = not set, so nothing is shown). */
function profile(string $key): string
{
    return App::current()->profile()[$key] ?? '';
}
