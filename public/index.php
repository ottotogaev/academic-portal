<?php

declare(strict_types=1);

// Single public entry point.
// $basePath is the application root (the folder that contains app/, config/, storage/ and .env).
// On shared hosting where this folder is the domain root and the application lives in a sibling
// directory, change this one line, e.g. dirname(__DIR__) . '/academic-portal'.
$basePath = dirname(__DIR__);

define('APP_PUBLIC_PATH', __DIR__);

$app = require $basePath . '/bootstrap/app.php';
$app->run();
