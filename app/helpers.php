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
