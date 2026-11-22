<?php

declare(strict_types=1);

/**
 * Deployment self-check. Run it after every deployment:  php bin/check.php
 * Prints OK / WARN / FAIL lines and exits 1 when anything FAILs. It never prints secrets.
 * On hosting without a terminal run it from a cPanel cron job and read the output e-mail.
 */

use App\Core\Env;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$basePath = dirname(__DIR__);
$app = require $basePath . '/bootstrap/app.php';

$fail = 0;
$warn = 0;
$say = static function (string $level, string $text) use (&$fail, &$warn): void {
    if ($level === 'FAIL') {
        $fail++;
    } elseif ($level === 'WARN') {
        $warn++;
    }
    printf("%-5s %s\n", $level, $text);
};
$expect = static fn (bool $ok, string $good, string $bad, string $level = 'FAIL') => $say($ok ? 'OK' : $level, $ok ? $good : $bad);

echo "PHP and extensions\n";
$expect(PHP_VERSION_ID >= 80100, 'PHP ' . PHP_VERSION, 'PHP 8.1 or newer is required, found ' . PHP_VERSION);
foreach (['pdo_mysql', 'mbstring', 'fileinfo'] as $ext) {
    $expect(extension_loaded($ext), "extension {$ext}", "extension {$ext} is missing");
}

echo "Environment\n";
$env = (string) $app->config()->get('app.env', 'production');
$expect($env === 'production', 'APP_ENV=production', "APP_ENV is '{$env}' (use production on the live site)", 'WARN');
$expect(!filter_var(Env::get('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN) || $env !== 'production', 'APP_DEBUG is off', 'APP_DEBUG=true on production');
$url = (string) $app->config()->get('app.url', '');
$expect(str_starts_with($url, 'https://'), 'APP_URL uses https', "APP_URL is '{$url}': use https:// once the certificate is active", 'WARN');
$expect((bool) $app->config()->get('app.session_secure', true), 'SESSION_SECURE is on', 'SESSION_SECURE=false: the login cookie would travel over plain http', $url !== '' && str_starts_with($url, 'https://') ? 'FAIL' : 'WARN');
$expect(is_file($basePath . '/.env'), '.env file present', '.env file is missing');
$expect(!is_file($basePath . '/public/.env') && !is_dir($basePath . '/public/app') && !is_dir($basePath . '/public/storage'), 'public/ holds no .env, app or storage', 'public/ exposes application files');

echo "Writable folders\n";
foreach (['storage/logs', 'storage/sessions', 'storage/uploads', 'storage/cache'] as $dir) {
    $path = $basePath . '/' . $dir;
    if (!is_dir($path)) {
        @mkdir($path, 0750, true);
    }
    $expect(is_dir($path) && is_writable($path), "{$dir} is writable", "{$dir} is not writable");
}

echo "Database\n";
try {
    $pdo = $app->database()->pdo();
    $version = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
    $say('OK', 'connected, server version ' . $version);
    if (stripos($version, 'mariadb') === false && version_compare($version, '5.6.5', '<')) {
        $say('FAIL', 'MySQL 5.6.5 or newer is required (migration 0001 uses DEFAULT CURRENT_TIMESTAMP)');
    }

    $applied = [];
    try {
        $applied = $pdo->query('SELECT version FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable) {
    }
    $files = glob($basePath . '/database/migrations/*.sql') ?: [];
    $pending = array_values(array_filter(array_map('basename', $files), static fn (string $f): bool => !in_array($f, $applied, true) && !in_array(basename($f, '.sql'), $applied, true)));
    $expect($pending === [], count($files) . ' migrations applied', 'pending migrations: ' . implode(', ', $pending) . ' (run php bin/migrate.php --yes)');

    try {
        $admins = (int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
        $expect($admins === 1, 'one administrator exists', $admins === 0 ? 'no administrator yet (php bin/create-admin.php <username>)' : 'more than one administrator found');
    } catch (Throwable) {
        $say('FAIL', 'table admins is missing: run the migrations');
    }
} catch (Throwable $e) {
    $say('FAIL', 'database connection failed: check DB_* in .env (' . get_class($e) . ')');
}

$say('OK', 'contact feature: ' . ((bool) $app->config()->get('app.contact_enabled', false) ? 'ON (FEATURE_CONTACT=true)' : 'off (FEATURE_CONTACT=false, expected until the end of the project)'));

echo "Uploads\n";
$limit = $app->uploads()->effectiveLimit();
$say($limit >= 5 * 1048576 ? 'OK' : 'WARN', 'largest file a user can upload: ' . round($limit / 1048576, 1) . ' MB (min of UPLOAD_MAX_SIZE, upload_max_filesize, post_max_size)');
$say(!is_file($basePath . '/storage/uploads/.htaccess') ? 'WARN' : 'OK', 'storage/uploads/.htaccess ' . (is_file($basePath . '/storage/uploads/.htaccess') ? 'present' : 'missing (second layer of protection)'));

echo "\n" . ($fail === 0 ? 'Ready' : 'NOT ready') . ": {$fail} failure(s), {$warn} warning(s).\n";
exit($fail === 0 ? 0 : 1);
