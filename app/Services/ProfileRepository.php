<?php

declare(strict_types=1);

namespace App\Services;

/** The single teacher profile (teacher_profiles + teacher_profile_translations). */
final class ProfileRepository
{
    public const FIELDS = ['full_name', 'position', 'academic_degree', 'specialization', 'biography', 'education', 'experience'];

    public function __construct(private \PDO $pdo, private string $defaultLocale = 'uz')
    {
    }

    /** @return array{id:int,email:?string,phone:?string,translations:array<string,array<string,?string>>}|null */
    public function get(): ?array
    {
        $row = $this->pdo->query('SELECT id, email, phone FROM teacher_profiles ORDER BY id ASC LIMIT 1')->fetch();
        if (!is_array($row)) {
            return null;
        }
        $stmt = $this->pdo->prepare('SELECT * FROM teacher_profile_translations WHERE teacher_profile_id = :id');
        $stmt->execute(['id' => $row['id']]);
        $translations = [];
        foreach ($stmt->fetchAll() as $t) {
            $translations[(string) $t['locale']] = $t;
        }

        return ['id' => (int) $row['id'], 'email' => $row['email'], 'phone' => $row['phone'], 'translations' => $translations];
    }

    /**
     * Creates the profile on first save, then updates it.
     *
     * @param array{email:?string,phone:?string}        $base
     * @param array<string,array<string,string>>        $translations locale => field => value
     */
    public function save(array $base, array $translations, string $now): void
    {
        $this->pdo->beginTransaction();
        try {
            $current = $this->get();
            if ($current === null) {
                $this->pdo->prepare('INSERT INTO teacher_profiles (email, phone, created_at, updated_at) VALUES (:e, :p, :c, :u)')
                    ->execute(['e' => $base['email'], 'p' => $base['phone'], 'c' => $now, 'u' => $now]);
                $id = (int) $this->pdo->lastInsertId();
            } else {
                $id = $current['id'];
                $this->pdo->prepare('UPDATE teacher_profiles SET email = :e, phone = :p, updated_at = :u WHERE id = :id')
                    ->execute(['e' => $base['email'], 'p' => $base['phone'], 'u' => $now, 'id' => $id]);
            }

            foreach ($translations as $locale => $fields) {
                $this->saveTranslation($id, (string) $locale, $fields);
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /** @param array<string,string> $fields */
    private function saveTranslation(int $profileId, string $locale, array $fields): void
    {
        $stmt = $this->pdo->prepare('SELECT id FROM teacher_profile_translations WHERE teacher_profile_id = :p AND locale = :l');
        $stmt->execute(['p' => $profileId, 'l' => $locale]);
        $existing = $stmt->fetchColumn();

        if (($fields['full_name'] ?? '') === '' && $locale !== $this->defaultLocale) {
            if ($existing !== false) {
                $this->pdo->prepare('DELETE FROM teacher_profile_translations WHERE id = :id')->execute(['id' => $existing]);
            }

            return;
        }
        $values = [];
        foreach (self::FIELDS as $field) {
            $value = $fields[$field] ?? '';
            $values[$field] = ($value === '' && $field !== 'full_name') ? null : $value;
        }
        if ($existing === false) {
            $cols = array_merge(['teacher_profile_id', 'locale'], self::FIELDS);
            $this->pdo->prepare('INSERT INTO teacher_profile_translations (' . implode(', ', $cols) . ') VALUES (:' . implode(', :', $cols) . ')')
                ->execute($values + ['teacher_profile_id' => $profileId, 'locale' => $locale]);
        } else {
            $set = array_map(static fn (string $f): string => $f . ' = :' . $f, self::FIELDS);
            $this->pdo->prepare('UPDATE teacher_profile_translations SET ' . implode(', ', $set) . ' WHERE id = :row')
                ->execute($values + ['row' => $existing]);
        }
    }
}
