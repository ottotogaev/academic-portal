<?php

declare(strict_types=1);

/**
 * Applies pending SQL migrations from database/migrations/*.sql in file-name order.
 *
 *   php bin/migrate.php            apply pending migrations
 *   php bin/migrate.php --status   list applied and pending migrations
 *   php bin/migrate.php --yes      required in production (back up the database first)
 *
 * Migration files: one statement per ";" at the end of a line. MySQL DDL is not transactional, so write
 * migrations to be idempotent (CREATE TABLE IF NOT EXISTS). Destructive statements need explicit approval.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$basePath = dirname(__DIR__);
$app = require $basePath . '/bootstrap/app.php';

$args = array_slice($argv, 1);
$statusOnly = in_array('--status', $args, true);
$confirmed = in_array('--yes', $args, true);

$pdo = $app->database()->pdo();
$pdo->exec(
    'CREATE TABLE IF NOT EXISTS schema_migrations ('
    . 'version VARCHAR(190) NOT NULL, '
    . 'applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, '
    . 'PRIMARY KEY (version)'
    . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);

$applied = $pdo->query('SELECT version FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
$files = glob($basePath . '/database/migrations/*.sql') ?: [];
sort($files);

$pending = [];
foreach ($files as $file) {
    $version = basename($file, '.sql');
    if (in_array($version, $applied, true)) {
        echo "applied  {$version}\n";
    } else {
        $pending[$version] = $file;
        echo "pending  {$version}\n";
    }
}

if ($statusOnly || $pending === []) {
    echo $pending === [] ? "Nothing to migrate.\n" : '';
    exit(0);
}

if ($app->config()->get('app.env') === 'production' && !$confirmed) {
    fwrite(STDERR, "Production environment: back up the database, then run again with --yes.\n");
    exit(2);
}

foreach ($pending as $version => $file) {
    $sql = (string) file_get_contents($file);
    $statements = preg_split('/;[ \t]*\r?\n/', $sql . "\n") ?: [];
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if ($statement !== '') {
            $pdo->exec($statement);
        }
    }
    $insert = $pdo->prepare('INSERT INTO schema_migrations (version) VALUES (?)');
    $insert->execute([$version]);
    echo "migrated {$version}\n";
}
