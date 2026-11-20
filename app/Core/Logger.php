<?php

declare(strict_types=1);

namespace App\Core;

/** Private file logger (storage/logs). Never pass secrets in messages or context. */
final class Logger
{
    public function __construct(private string $dir)
    {
    }

    public function error(string $message, array $context = []): void
    {
        $this->write('ERROR', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->write('WARNING', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->write('INFO', $message, $context);
    }

    private function write(string $level, string $message, array $context): void
    {
        $extra = $context === []
            ? ''
            : ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
        $line = sprintf("[%s] %s %s%s\n", date('c'), $level, $message, $extra);

        if (!is_dir($this->dir)) {
            @mkdir($this->dir, 0750, true);
        }
        @file_put_contents($this->dir . '/app-' . date('Y-m-d') . '.log', $line, FILE_APPEND | LOCK_EX);
    }
}
