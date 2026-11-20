<?php

declare(strict_types=1);

namespace App\Core;

/**
 * PHP session wrapper, started lazily so public pages never set a cookie.
 * Strict mode, HttpOnly, SameSite=Lax, files kept in storage/sessions (not the shared system temp dir).
 * With $native=false it is array-backed, which is what the unit tests use.
 */
final class Session
{
    public const IDLE_TIMEOUT = 7200;      // 2 hours without activity
    public const ABSOLUTE_TIMEOUT = 43200; // 12 hours since login

    private bool $started = false;

    /** @var array<string,mixed> */
    private array $memory = [];

    public function __construct(private string $savePath, private bool $secure, private bool $native = true)
    {
    }

    public function start(): void
    {
        if ($this->started) {
            return;
        }
        $this->started = true;

        if (!$this->native) {
            return;
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        if (!is_dir($this->savePath) && !@mkdir($this->savePath, 0700, true) && !is_dir($this->savePath)) {
            throw new \RuntimeException('Session directory is not writable.');
        }

        // The __Host- prefix pins the cookie to this exact host, path "/" and HTTPS.
        session_name($this->secure ? '__Host-ap_sid' : 'ap_sid');
        session_save_path($this->savePath);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.gc_maxlifetime', (string) self::ABSOLUTE_TIMEOUT);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $this->secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        if (!session_start()) {
            throw new \RuntimeException('Session could not be started.');
        }

        $now = time();
        $last = (int) ($_SESSION['_last'] ?? $now);
        $born = (int) ($_SESSION['_born'] ?? $now);
        if ($now - $last > self::IDLE_TIMEOUT || $now - $born > self::ABSOLUTE_TIMEOUT) {
            $this->destroy();
            $this->started = true;
            $this->restart();

            return;
        }
        $_SESSION['_born'] = $born;
        $_SESSION['_last'] = $now;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->start();

        return $this->native ? ($_SESSION[$key] ?? $default) : ($this->memory[$key] ?? $default);
    }

    public function set(string $key, mixed $value): void
    {
        $this->start();
        if ($this->native) {
            $_SESSION[$key] = $value;
        } else {
            $this->memory[$key] = $value;
        }
    }

    public function forget(string $key): void
    {
        $this->start();
        if ($this->native) {
            unset($_SESSION[$key]);
        } else {
            unset($this->memory[$key]);
        }
    }

    /** Read once, then remove. */
    public function pull(string $key, mixed $default = null): mixed
    {
        $value = $this->get($key, $default);
        $this->forget($key);

        return $value;
    }

    /** New session ID, same data. Call after login and after changing the password. */
    public function regenerate(): void
    {
        $this->start();
        if ($this->native) {
            session_regenerate_id(true);
        }
    }

    /** Drop all data, delete the server-side session and expire the cookie. */
    public function destroy(): void
    {
        if (!$this->native) {
            $this->memory = [];
            $this->started = false;

            return;
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_destroy();
        }
        if (!headers_sent()) {
            setcookie(session_name(), '', [
                'expires' => time() - 3600,
                'path' => '/',
                'secure' => $this->secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
        $this->started = false;
    }

    /** Fresh empty session after an expiry. */
    private function restart(): void
    {
        $this->started = false;
        $this->start();
    }
}
