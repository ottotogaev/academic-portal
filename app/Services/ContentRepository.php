<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Slug;

/**
 * Reads and writes one content type described in config/content.php.
 * Table and column names come only from that config; every value is bound as a parameter.
 */
final class ContentRepository
{
    public const STATUSES = ['draft', 'published', 'archived'];

    /** @param array<string,mixed> $def */
    public function __construct(
        private \PDO $pdo,
        private array $def,
        private string $defaultLocale = 'uz',
    ) {
    }

    private function titleField(): string
    {
        return (string) ($this->def['title_field'] ?? 'title');
    }

    /** @return list<array<string,mixed>> rows with the default-locale title, for the admin list */
    public function all(): array
    {
        [$where, $params] = $this->fixedWhere();
        $sql = 'SELECT b.*, t.' . $this->titleField() . ' AS title FROM ' . $this->def['table'] . ' b LEFT JOIN ' . $this->def['translations']
            . ' t ON t.' . $this->def['fk'] . ' = b.id AND t.locale = :locale'
            . ($where !== '' ? ' WHERE ' . $where : '') . ' ORDER BY ' . $this->def['admin_order'];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params + ['locale' => $this->defaultLocale]);

        return $stmt->fetchAll();
    }

    public function count(): int
    {
        [$where, $params] = $this->fixedWhere();
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM ' . $this->def['table'] . ' b' . ($where !== '' ? ' WHERE ' . $where : ''));
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /** @return array{base:array<string,mixed>,translations:array<string,array<string,mixed>>}|null */
    public function find(int $id): ?array
    {
        [$where, $params] = $this->fixedWhere();
        $stmt = $this->pdo->prepare('SELECT b.* FROM ' . $this->def['table'] . ' b WHERE b.id = :id' . ($where !== '' ? ' AND ' . $where : ''));
        $stmt->execute($params + ['id' => $id]);
        $base = $stmt->fetch();
        if (!is_array($base)) {
            return null;
        }

        $stmt = $this->pdo->prepare('SELECT * FROM ' . $this->def['translations'] . ' WHERE ' . $this->def['fk'] . ' = :id');
        $stmt->execute(['id' => $id]);
        $translations = [];
        foreach ($stmt->fetchAll() as $row) {
            $translations[(string) $row['locale']] = $row;
        }

        return ['base' => $base, 'translations' => $translations];
    }

    /**
     * Creates or updates a row and its translations in one transaction.
     * A non-default locale whose title is empty has its translation row removed.
     *
     * @param array<string,mixed>                 $base         base columns (config keys) plus "status"
     * @param array<string,array<string,string>>  $translations locale => field => value
     */
    public function save(?int $id, array $base, array $translations, string $now): int
    {
        $columns = array_merge(array_keys((array) $this->def['base']), ['status'], array_keys((array) $this->def['fixed']));
        $values = [];
        foreach ($columns as $column) {
            $values[$column] = $base[$column] ?? ((array) $this->def['fixed'])[$column] ?? null;
        }
        // "managed" columns (file metadata) are set by the controller, not by the form; only written when given.
        foreach ((array) ($this->def['managed'] ?? []) as $column) {
            if (array_key_exists($column, $base)) {
                $values[$column] = $base[$column];
            }
        }

        $this->pdo->beginTransaction();
        try {
            if ($id === null) {
                $names = array_merge(array_keys($values), ['created_at', 'updated_at']);
                $stmt = $this->pdo->prepare(
                    'INSERT INTO ' . $this->def['table'] . ' (' . implode(', ', $names) . ') VALUES (:' . implode(', :', $names) . ')'
                );
                $stmt->execute($values + ['created_at' => $now, 'updated_at' => $now]);
                $id = (int) $this->pdo->lastInsertId();
            } else {
                $set = [];
                foreach (array_keys($values) as $name) {
                    $set[] = $name . ' = :' . $name;
                }
                $set[] = 'updated_at = :updated_at';
                [$where, $fixedParams] = $this->fixedWhere('');
                $stmt = $this->pdo->prepare(
                    'UPDATE ' . $this->def['table'] . ' SET ' . implode(', ', $set) . ' WHERE id = :id' . ($where !== '' ? ' AND ' . $where : '')
                );
                $stmt->execute($values + $fixedParams + ['updated_at' => $now, 'id' => $id]);
            }

            foreach ($translations as $locale => $fields) {
                $this->saveTranslation($id, (string) $locale, $fields);
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return $id;
    }

    /** Deletes the row; translations go with it. Throws PDOException (SQLSTATE 23000) when it is still referenced. */
    public function delete(int $id): bool
    {
        [$where, $params] = $this->fixedWhere('');
        $stmt = $this->pdo->prepare('DELETE FROM ' . $this->def['table'] . ' WHERE id = :id' . ($where !== '' ? ' AND ' . $where : ''));
        $stmt->execute($params + ['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    /** Base row of a published item (no translation needed), for downloads. @return array<string,mixed>|null */
    public function publishedRow(int $id): ?array
    {
        [$where, $params] = $this->fixedWhere();
        $stmt = $this->pdo->prepare('SELECT b.* FROM ' . $this->def['table'] . " b WHERE b.id = :id AND b.status = 'published'" . ($where !== '' ? ' AND ' . $where : ''));
        $stmt->execute($params + ['id' => $id]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    public function slugExists(string $slug, ?int $exceptId): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM ' . $this->def['table'] . ' WHERE slug = :s AND id <> :id');
        $stmt->execute(['s' => $slug, 'id' => $exceptId ?? 0]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function uniqueSlug(string $base, ?int $exceptId): string
    {
        $slug = Slug::make($base, (string) $this->def['table']);
        $candidate = $slug;
        for ($i = 2; $this->slugExists($candidate, $exceptId); $i++) {
            $candidate = substr($slug, 0, 110) . '-' . $i;
        }

        return $candidate;
    }

    /** @return array<int,string> id => default-locale title, for the topic selector */
    public function titles(): array
    {
        $out = [];
        foreach ($this->all() as $row) {
            $out[(int) $row['id']] = (string) ($row['title'] ?? ('#' . $row['id']));
        }

        return $out;
    }

    public function exists(int $id): bool
    {
        return $this->find($id) !== null;
    }

    /**
     * Published rows that have a translation in $locale.
     *
     * @return list<array<string,mixed>>
     */
    public function published(string $locale, string $nowUtc): array
    {
        return $this->publicQuery($locale, $nowUtc, null);
    }

    /** @return array<string,mixed>|null */
    public function publishedBySlug(string $slug, string $locale, string $nowUtc): ?array
    {
        return $this->publicQuery($locale, $nowUtc, $slug)[0] ?? null;
    }

    /** @return list<array<string,mixed>> */
    private function publicQuery(string $locale, string $nowUtc, ?string $slug): array
    {
        [$where, $params] = $this->fixedWhere();
        $fields = array_map(static fn (string $f): string => 't.' . $f, array_keys((array) $this->def['fields']));
        $conditions = ["b.status = 'published'", 't.locale = :locale'];
        if ($where !== '') {
            $conditions[] = $where;
        }
        if (isset($this->def['dated'])) {
            $conditions[] = 'b.' . $this->def['dated'] . ' IS NOT NULL AND b.' . $this->def['dated'] . ' <= :now';
            $params['now'] = $nowUtc;
        }
        if ($slug !== null) {
            $conditions[] = 'b.slug = :slug';
            $params['slug'] = $slug;
        }
        $sql = 'SELECT b.*, ' . implode(', ', $fields) . ' FROM ' . $this->def['table'] . ' b JOIN ' . $this->def['translations']
            . ' t ON t.' . $this->def['fk'] . ' = b.id WHERE ' . implode(' AND ', $conditions) . ' ORDER BY ' . $this->def['public_order'];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params + ['locale' => $locale]);

        return $stmt->fetchAll();
    }

    /** @param array<string,string> $fields */
    private function saveTranslation(int $id, string $locale, array $fields): void
    {
        $fk = (string) $this->def['fk'];
        $table = (string) $this->def['translations'];
        $stmt = $this->pdo->prepare("SELECT id FROM {$table} WHERE {$fk} = :id AND locale = :l");
        $stmt->execute(['id' => $id, 'l' => $locale]);
        $existing = $stmt->fetchColumn();

        if (trim((string) ($fields[$this->titleField()] ?? '')) === '' && $locale !== $this->defaultLocale) {
            if ($existing !== false) {
                $this->pdo->prepare("DELETE FROM {$table} WHERE id = :t")->execute(['t' => $existing]);
            }

            return;
        }

        $names = array_keys((array) $this->def['fields']);
        $values = [];
        foreach ($names as $name) {
            $value = $fields[$name] ?? '';
            $required = (bool) ((array) $this->def['fields'])[$name]['required'];
            $values[$name] = ($value === '' && !$required) ? null : $value;
        }

        if ($existing === false) {
            $cols = array_merge([$fk, 'locale'], $names);
            $this->pdo->prepare("INSERT INTO {$table} (" . implode(', ', $cols) . ') VALUES (:' . implode(', :', $cols) . ')')
                ->execute($values + [$fk => $id, 'locale' => $locale]);
        } else {
            $set = array_map(static fn (string $n): string => $n . ' = :' . $n, $names);
            $this->pdo->prepare("UPDATE {$table} SET " . implode(', ', $set) . ' WHERE id = :row')
                ->execute($values + ['row' => $existing]);
        }
    }

    /** @return array{0:string,1:array<string,string>} */
    private function fixedWhere(string $alias = 'b.'): array
    {
        $parts = [];
        $params = [];
        foreach ((array) $this->def['fixed'] as $column => $value) {
            $parts[] = $alias . $column . ' = :fixed_' . $column;
            $params['fixed_' . $column] = (string) $value;
        }

        return [implode(' AND ', $parts), $params];
    }
}
