<?php

declare(strict_types=1);

/**
 * Creates the single administrator. No default account exists.
 *
 *   php bin/create-admin.php <username>
 *
 * The password is typed at a hidden prompt (twice), or read from the ADMIN_PASSWORD environment
 * variable for non-interactive use. It is never printed, logged or accepted as a command-line argument.
 */

use App\Core\Auth;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$basePath = dirname(__DIR__);
$app = require $basePath . '/bootstrap/app.php';

$username = $argv[1] ?? '';
if ($username === '') {
    fwrite(STDERR, "Usage: php bin/create-admin.php <username>\n");
    exit(1);
}

$password = getenv('ADMIN_PASSWORD');
if ($password === false || $password === '') {
    $hidden = PHP_OS_FAMILY !== 'Windows' && function_exists('posix_isatty') && posix_isatty(STDIN);
    $read = static function (string $prompt) use ($hidden): string {
        fwrite(STDOUT, $prompt);
        if ($hidden) {
            shell_exec('stty -echo');
        }
        $line = (string) fgets(STDIN);
        if ($hidden) {
            shell_exec('stty echo');
            fwrite(STDOUT, "\n");
        }

        return rtrim($line, "\r\n");
    };
    $password = $read('Password (min ' . Auth::MIN_PASSWORD_LENGTH . ' characters): ');
    if ($password !== $read('Repeat password: ')) {
        fwrite(STDERR, "Passwords do not match.\n");
        exit(1);
    }
}

try {
    Auth::createAdmin($app->database()->pdo(), $username, $password, time());
} catch (InvalidArgumentException | RuntimeException $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

echo "Administrator \"{$username}\" created.\n";
