<?php

declare(strict_types=1);

namespace App\Core;

/** One lazily created PDO connection. Pages that need no data never open it. */
final class Database
{
    private ?\PDO $pdo = null;

    /** @param array<string,mixed> $config */
    public function __construct(private array $config)
    {
    }

    public function pdo(): \PDO
    {
        if ($this->pdo === null) {
            if ((string) ($this->config['database'] ?? '') === '' || (string) ($this->config['username'] ?? '') === '') {
                throw new \RuntimeException('Database is not configured. Set DB_DATABASE, DB_USERNAME and DB_PASSWORD in .env.');
            }

            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $this->config['host'],
                $this->config['port'],
                $this->config['database'],
                $this->config['charset']
            );

            $this->pdo = new \PDO($dsn, (string) $this->config['username'], (string) $this->config['password'], [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }

        return $this->pdo;
    }
}
