<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Single-administrator authentication.
 * - password_hash / password_verify (bcrypt by default), rehash when the algorithm cost changes
 * - failed logins are counted per client and globally in login_attempts, so guessing is slowed down
 * - session ID is regenerated on login; logout destroys the session
 * All timestamps are produced in PHP so SQL stays portable across MySQL and MariaDB versions.
 */
final class Auth
{
    public const MAX_FAILURES_PER_CLIENT = 5;
    public const MAX_FAILURES_GLOBAL = 30;
    public const WINDOW_SECONDS = 900; // 15 minutes
    public const MIN_PASSWORD_LENGTH = 12;

    private const SESSION_KEY = 'admin_id';

    private ?\PDO $pdo = null;

    /** @param \PDO|\Closure():\PDO $db a lazy factory keeps session-only checks free of database access */
    public function __construct(
        private \PDO|\Closure $db,
        private Session $session,
        private ?int $now = null,
    ) {
    }

    private function pdo(): \PDO
    {
        return $this->pdo ??= $this->db instanceof \PDO ? $this->db : ($this->db)();
    }

    public function check(): bool
    {
        return $this->id() !== null;
    }

    public function id(): ?int
    {
        $id = $this->session->get(self::SESSION_KEY);

        return is_int($id) ? $id : null;
    }

    public function username(): ?string
    {
        $id = $this->id();
        if ($id === null) {
            return null;
        }
        $stmt = $this->pdo()->prepare('SELECT username FROM admins WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $name = $stmt->fetchColumn();

        return is_string($name) ? $name : null;
    }

    /** True when too many recent failures exist for this client or overall. */
    public function throttled(string $ip): bool
    {
        $since = $this->stamp($this->time() - self::WINDOW_SECONDS);

        $stmt = $this->pdo()->prepare('SELECT COUNT(*) FROM login_attempts WHERE client_hash = :c AND attempted_at >= :s');
        $stmt->execute(['c' => $this->client($ip), 's' => $since]);
        if ((int) $stmt->fetchColumn() >= self::MAX_FAILURES_PER_CLIENT) {
            return true;
        }

        $stmt = $this->pdo()->prepare('SELECT COUNT(*) FROM login_attempts WHERE attempted_at >= :s');
        $stmt->execute(['s' => $since]);

        return (int) $stmt->fetchColumn() >= self::MAX_FAILURES_GLOBAL;
    }

    /** Returns true on success. The caller checks throttled() first. */
    public function attempt(string $username, string $password, string $ip): bool
    {
        $stmt = $this->pdo()->prepare('SELECT id, password_hash FROM admins WHERE username = :u');
        $stmt->execute(['u' => $username]);
        $row = $stmt->fetch();

        // Always run one password_verify so a wrong username and a wrong password take the same time.
        $hash = is_array($row) ? (string) $row['password_hash'] : self::dummyHash();
        $ok = password_verify($password, $hash) && is_array($row);

        if (!$ok) {
            $this->recordFailure($ip);

            return false;
        }

        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            $this->storeHash((int) $row['id'], $password);
        }
        $this->pdo()->prepare('DELETE FROM login_attempts WHERE client_hash = :c')->execute(['c' => $this->client($ip)]);
        $this->pdo()->prepare('UPDATE admins SET last_login_at = :t WHERE id = :id')
            ->execute(['t' => $this->stamp($this->time()), 'id' => (int) $row['id']]);

        $this->session->regenerate();
        $this->session->set(self::SESSION_KEY, (int) $row['id']);

        return true;
    }

    public function logout(): void
    {
        $this->session->destroy();
    }

    /** Verifies the current password, applies the length rule and replaces the hash. Returns an error key or null. */
    public function changePassword(string $current, string $new): ?string
    {
        $id = $this->id();
        if ($id === null) {
            return 'auth';
        }
        $stmt = $this->pdo()->prepare('SELECT password_hash FROM admins WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $hash = $stmt->fetchColumn();
        if (!is_string($hash) || !password_verify($current, $hash)) {
            return 'current';
        }
        if (self::passwordProblem($new) !== null) {
            return 'weak';
        }
        $this->storeHash($id, $new);
        $this->session->regenerate();

        return null;
    }

    /** Creates the one administrator. Fails when an account already exists. */
    public static function createAdmin(\PDO $pdo, string $username, string $password, int $now): void
    {
        if (preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username) !== 1) {
            throw new \InvalidArgumentException('Username must be 3-50 characters: letters, digits, "_", ".", "-".');
        }
        if (($problem = self::passwordProblem($password)) !== null) {
            throw new \InvalidArgumentException($problem);
        }
        if ((int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0) {
            throw new \RuntimeException('An administrator already exists. Version 1 supports exactly one.');
        }
        $t = gmdate('Y-m-d H:i:s', $now);
        $pdo->prepare('INSERT INTO admins (username, password_hash, created_at, updated_at) VALUES (:u, :h, :c, :c2)')
            ->execute(['u' => $username, 'h' => password_hash($password, PASSWORD_DEFAULT), 'c' => $t, 'c2' => $t]);
    }

    /** Human-readable reason, or null when acceptable. */
    public static function passwordProblem(string $password): ?string
    {
        if (mb_strlen($password) < self::MIN_PASSWORD_LENGTH) {
            return 'Password must be at least ' . self::MIN_PASSWORD_LENGTH . ' characters.';
        }
        if (strlen($password) > 1024) {
            return 'Password is too long.';
        }

        return null;
    }

    private function storeHash(int $id, string $password): void
    {
        $this->pdo()->prepare('UPDATE admins SET password_hash = :h, updated_at = :t WHERE id = :id')
            ->execute(['h' => password_hash($password, PASSWORD_DEFAULT), 't' => $this->stamp($this->time()), 'id' => $id]);
    }

    private function recordFailure(string $ip): void
    {
        $this->pdo()->prepare('INSERT INTO login_attempts (client_hash, attempted_at) VALUES (:c, :t)')
            ->execute(['c' => $this->client($ip), 't' => $this->stamp($this->time())]);
        // Housekeeping: keep the table small.
        $this->pdo()->prepare('DELETE FROM login_attempts WHERE attempted_at < :s')
            ->execute(['s' => $this->stamp($this->time() - 86400)]);
    }

    /** The raw address is never stored. */
    private function client(string $ip): string
    {
        return hash('sha256', $ip);
    }

    private function time(): int
    {
        return $this->now ?? time();
    }

    private function stamp(int $time): string
    {
        return gmdate('Y-m-d H:i:s', $time);
    }

    private static function dummyHash(): string
    {
        static $hash = null;

        return $hash ??= password_hash('not-a-real-password', PASSWORD_DEFAULT);
    }
}
