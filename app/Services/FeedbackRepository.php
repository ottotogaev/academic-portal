<?php

declare(strict_types=1);

namespace App\Services;

/** Private messages from the contact form (feedback_messages). Timestamps are UTC strings from PHP. */
final class FeedbackRepository
{
    public const STATUSES = ['new', 'in_progress', 'processed'];

    public function __construct(private \PDO $pdo)
    {
    }

    public function create(string $name, string $email, ?string $subject, string $message, string $clientHash, string $now): int
    {
        $this->pdo->prepare(
            'INSERT INTO feedback_messages (sender_name, sender_email, subject, message, status, client_hash, created_at, updated_at)'
            . " VALUES (:n, :e, :s, :m, 'new', :c, :t, :t2)"
        )->execute(['n' => $name, 'e' => $email, 's' => $subject, 'm' => $message, 'c' => $clientHash, 't' => $now, 't2' => $now]);

        return (int) $this->pdo->lastInsertId();
    }

    public function countSince(string $sinceUtc, ?string $clientHash = null): int
    {
        $sql = 'SELECT COUNT(*) FROM feedback_messages WHERE created_at >= :s' . ($clientHash !== null ? ' AND client_hash = :c' : '');
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($clientHash !== null ? ['s' => $sinceUtc, 'c' => $clientHash] : ['s' => $sinceUtc]);

        return (int) $stmt->fetchColumn();
    }

    public function countNew(): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM feedback_messages WHERE status = 'new'")->fetchColumn();
    }

    /** @return list<array<string,mixed>> */
    public function all(): array
    {
        return $this->pdo->query('SELECT id, sender_name, sender_email, subject, status, created_at FROM feedback_messages ORDER BY id DESC')->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM feedback_messages WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    public function setStatus(int $id, string $status, string $now): bool
    {
        if (!in_array($status, self::STATUSES, true)) {
            throw new \InvalidArgumentException('Unknown status.');
        }
        $stmt = $this->pdo->prepare('UPDATE feedback_messages SET status = :s, updated_at = :t WHERE id = :id');
        $stmt->execute(['s' => $status, 't' => $now, 'id' => $id]);

        return $this->find($id) !== null;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM feedback_messages WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }
}
