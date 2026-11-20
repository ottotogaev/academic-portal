<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Validates and stores uploaded files outside the web root under random names.
 * The original name is kept only as metadata for the download header; it never touches the file system.
 */
final class UploadStorage
{
    private const STORED = '/^[a-f0-9]{32}\.[a-z0-9]{2,5}$/';

    /** @param array<string,array{mime:string,detected:list<string>}> $types */
    public function __construct(
        private string $dir,
        private array $types,
        private int $maxBytes,
    ) {
    }

    /**
     * @param array<string,mixed>|null $file entry from $_FILES
     * @return array{empty:bool,error:?string,meta:?array{ext:string,mime:string,original:string,size:int}}
     */
    public function inspect(?array $file): array
    {
        $fail = static fn (string $key): array => ['empty' => false, 'error' => $key, 'meta' => null];

        if ($file === null || (int) $file['error'] === UPLOAD_ERR_NO_FILE) {
            return ['empty' => true, 'error' => null, 'meta' => null];
        }
        $error = (int) $file['error'];
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            return $fail('file_too_big');
        }
        if ($error !== UPLOAD_ERR_OK) {
            return $fail('file_failed');
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        $size = is_file($tmp) ? (int) filesize($tmp) : 0;
        if ($size <= 0) {
            return $fail('file_failed');
        }
        if ($size > $this->maxBytes) {
            return $fail('file_too_big');
        }

        $original = self::cleanName((string) ($file['name'] ?? ''));
        $ext = strtolower((string) pathinfo($original, PATHINFO_EXTENSION));
        if (preg_match('/^[a-z0-9]{1,5}$/', $ext) !== 1 || !isset($this->types[$ext])) {
            return $fail('file_type');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detected = (string) $finfo->file($tmp);
        if (!in_array($detected, $this->types[$ext]['detected'], true)) {
            return $fail('file_type');
        }

        return ['empty' => false, 'error' => null, 'meta' => [
            'ext' => $ext === 'jpeg' ? 'jpg' : $ext,
            'mime' => $this->types[$ext]['mime'],
            'original' => $original,
            'size' => $size,
        ]];
    }

    /** Moves an inspected upload into storage and returns its random stored name. */
    public function store(array $file, string $ext): string
    {
        $tmp = (string) $file['tmp_name'];
        if (!is_uploaded_file($tmp)) {
            throw new \RuntimeException('Not an uploaded file.');
        }
        if (!is_dir($this->dir) && !@mkdir($this->dir, 0750, true) && !is_dir($this->dir)) {
            throw new \RuntimeException('Upload directory is not writable.');
        }
        $name = bin2hex(random_bytes(16)) . '.' . $ext;
        if (!move_uploaded_file($tmp, $this->dir . '/' . $name)) {
            throw new \RuntimeException('Could not store the uploaded file.');
        }
        @chmod($this->dir . '/' . $name, 0640);

        return $name;
    }

    /** Absolute path of a stored file, or null when the name is malformed or the file is missing. */
    public function path(string $stored): ?string
    {
        if (preg_match(self::STORED, $stored) !== 1) {
            return null;
        }
        $path = $this->dir . '/' . $stored;

        return is_file($path) ? $path : null;
    }

    public function remove(string $stored): void
    {
        $path = $this->path($stored);
        if ($path !== null) {
            @unlink($path);
        }
    }

    /** Largest upload that will really work: the app limit capped by PHP's own limits. */
    public function effectiveLimit(): int
    {
        $limits = [$this->maxBytes];
        foreach (['upload_max_filesize', 'post_max_size'] as $key) {
            $bytes = self::iniBytes((string) ini_get($key));
            if ($bytes > 0) {
                $limits[] = $bytes;
            }
        }

        return min($limits);
    }

    /** @return list<string> */
    public function extensions(): array
    {
        return array_keys($this->types);
    }

    public static function cleanName(string $name): string
    {
        $name = str_replace('\\', '/', $name);
        $name = basename($name);
        $name = (string) preg_replace('/[\x00-\x1F\x7F"<>:*?|\/]+/u', '', $name);
        $name = trim($name, '. ');
        if (mb_strlen($name) > 150) {
            $ext = (string) pathinfo($name, PATHINFO_EXTENSION);
            $name = mb_substr(pathinfo($name, PATHINFO_FILENAME), 0, 140) . ($ext !== '' ? '.' . $ext : '');
        }

        return $name;
    }

    private static function iniBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '-1') {
            return 0;
        }
        $number = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1073741824,
            'm' => $number * 1048576,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
