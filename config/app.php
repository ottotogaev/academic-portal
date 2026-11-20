<?php

declare(strict_types=1);

use App\Core\Env;

$locales = ['uz', 'ru', 'en'];
$default = (string) Env::get('APP_DEFAULT_LOCALE', 'uz');

return [
    'env' => (string) Env::get('APP_ENV', 'production'),
    'debug' => Env::bool('APP_DEBUG', false),
    'url' => rtrim((string) Env::get('APP_URL', 'http://localhost:8000'), '/'),
    'locales' => $locales,
    'default_locale' => in_array($default, $locales, true) ? $default : 'uz',
    // Zone used to read and show dates entered in the admin; the database always stores UTC.
    'timezone' => (string) Env::get('APP_TIMEZONE', 'Asia/Tashkent'),
    'session_secure' => Env::bool('SESSION_SECURE', true),
    'upload_max_size' => (int) Env::get('UPLOAD_MAX_SIZE', '10485760'),
];
