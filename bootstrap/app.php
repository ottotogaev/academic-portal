<?php

declare(strict_types=1);

use App\Core\App;
use App\Core\Config;
use App\Core\Env;
use App\Core\ErrorHandler;
use App\Core\Logger;

/** @var string $basePath application root, defined by the entry script (public/index.php or bin/*) */

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '0');
ini_set('zend.exception_ignore_args', '1');

spl_autoload_register(static function (string $class) use ($basePath): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = $basePath . '/app/' . $relative . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require_once $basePath . '/app/helpers.php';

Env::load($basePath . '/.env');

$config = new Config($basePath . '/config');
$logger = new Logger($basePath . '/storage/logs');

date_default_timezone_set('Asia/Tashkent');
mb_internal_encoding('UTF-8');

ErrorHandler::register(
    $logger,
    (bool) $config->get('app.debug') && $config->get('app.env') !== 'production'
);

return new App($basePath, $config, $logger);
