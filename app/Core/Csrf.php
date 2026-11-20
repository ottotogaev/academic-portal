<?php

declare(strict_types=1);

namespace App\Core;

/** Per-session CSRF token, compared in constant time. Every state-changing admin request must pass it. */
final class Csrf
{
    private const KEY = '_csrf';

    public function __construct(private Session $session)
    {
    }

    public function token(): string
    {
        $token = $this->session->get(self::KEY);
        if (!is_string($token) || strlen($token) !== 64) {
            $token = bin2hex(random_bytes(32));
            $this->session->set(self::KEY, $token);
        }

        return $token;
    }

    public function valid(string $submitted): bool
    {
        $token = $this->session->get(self::KEY);

        return is_string($token) && $submitted !== '' && hash_equals($token, $submitted);
    }
}
