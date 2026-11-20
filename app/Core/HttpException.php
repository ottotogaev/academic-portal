<?php

declare(strict_types=1);

namespace App\Core;

/** Expected HTTP error (400, 404, 405...) rendered as a normal error page, not logged as a failure. */
final class HttpException extends \RuntimeException
{
    /** @param array<string,string> $headers */
    public function __construct(private int $status, private array $headers = [])
    {
        parent::__construct('HTTP ' . $status, $status);
    }

    public function status(): int
    {
        return $this->status;
    }

    /** @return array<string,string> */
    public function headers(): array
    {
        return $this->headers;
    }
}
