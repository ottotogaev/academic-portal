<?php

declare(strict_types=1);

namespace App\Core;

/** Centralised error handling: detailed logs stay private, production responses stay generic. */
final class ErrorHandler
{
    private static ?Logger $logger = null;
    private static bool $debug = false;

    public static function register(Logger $logger, bool $debug): void
    {
        self::$logger = $logger;
        self::$debug = $debug;

        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleError(int $severity, string $message, string $file, int $line): bool
    {
        if ($severity === E_DEPRECATED || $severity === E_USER_DEPRECATED) {
            self::$logger?->warning('Deprecated', ['message' => $message, 'file' => $file, 'line' => $line]);

            return true;
        }
        if ((error_reporting() & $severity) === 0) {
            return false;
        }

        throw new \ErrorException($message, 0, $severity, $file, $line);
    }

    public static function handleException(\Throwable $e): void
    {
        self::logThrowable($e);

        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, get_class($e) . ': ' . $e->getMessage() . PHP_EOL);
            exit(1);
        }

        self::respond(self::render($e, self::$debug));
    }

    public static function handleShutdown(): void
    {
        $error = error_get_last();
        $fatal = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
        if ($error === null || !in_array($error['type'], $fatal, true)) {
            return;
        }

        self::$logger?->error('Fatal error', [
            'message' => $error['message'],
            'file' => $error['file'],
            'line' => $error['line'],
        ]);

        if (PHP_SAPI !== 'cli') {
            self::respond(self::render(new \ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']), self::$debug));
        }
    }

    /** Body of the 500 response. In production it never contains messages, paths or traces. */
    public static function render(\Throwable $e, bool $debug): string
    {
        if ($debug) {
            $text = get_class($e) . ': ' . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine() . "\n\n" . $e->getTraceAsString();

            return '<!doctype html><meta charset="utf-8"><title>Error</title><pre>'
                . htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</pre>';
        }

        ob_start();
        include dirname(__DIR__) . '/Views/errors/500.php';

        return (string) ob_get_clean();
    }

    private static function respond(string $body): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
            header('X-Content-Type-Options: nosniff');
        }
        echo $body;
    }

    private static function logThrowable(\Throwable $e): void
    {
        self::$logger?->error('Unhandled exception', [
            'class' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}
