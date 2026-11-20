<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Request;
use App\Core\Slug;
use App\Core\Time;

/**
 * Validates an admin content form against a config/content.php definition.
 * Field names: base columns use their own name; translated fields are "{locale}__{field}".
 */
final class ContentForm
{
    /**
     * @param array<string,mixed> $def
     * @param list<string>        $locales   locales that have translated UI (the first is the required default)
     * @param array<string,array<int,string>> $refs valid ids per referenced type, for "ref" columns
     * @return array{base:array<string,mixed>,translations:array<string,array<string,string>>,old:array<string,string>,errors:array<string,string>}
     */
    public static function validate(
        array $def,
        Request $request,
        array $locales,
        string $zone,
        array $refs,
        ?ContentRepository $repo,
        ?int $id,
    ): array {
        $errors = [];
        $old = [];
        $base = [];

        $status = $request->input('status', 'draft');
        $old['status'] = $status;
        if (!in_array($status, ContentRepository::STATUSES, true)) {
            $errors['status'] = 'invalid';
            $status = 'draft';
        }
        $base['status'] = $status;

        foreach ((array) $def['base'] as $name => $spec) {
            $raw = trim($request->input($name));
            $old[$name] = $raw;
            $base[$name] = self::baseValue($name, (array) $spec, $raw, $zone, $refs, $errors);
        }

        if (isset($def['base']['slug']) && $repo !== null && $base['slug'] !== null && $repo->slugExists((string) $base['slug'], $id)) {
            $errors['slug'] = 'slug_taken';
        }

        $translations = [];
        foreach ($locales as $index => $locale) {
            $fields = [];
            $any = false;
            foreach ((array) $def['fields'] as $name => $spec) {
                $value = str_replace("\r\n", "\n", trim($request->input($locale . '__' . $name)));
                $old[$locale . '__' . $name] = $value;
                $fields[$name] = $value;
                $any = $any || $value !== '';
                if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value) === 1) {
                    $errors[$locale . '__' . $name] = 'invalid';
                } elseif (mb_strlen($value) > (int) $spec['max']) {
                    $errors[$locale . '__' . $name] = 'too_long';
                }
            }
            if ($index === 0 || $any) {
                foreach ((array) $def['fields'] as $name => $spec) {
                    if (($spec['required'] ?? false) && $fields[$name] === '' && !isset($errors[$locale . '__' . $name])) {
                        $errors[$locale . '__' . $name] = 'required';
                    }
                }
            }
            $translations[$locale] = $fields;
        }

        return ['base' => $base, 'translations' => $translations, 'old' => $old, 'errors' => $errors];
    }

    /** @param array<string,mixed> $spec @param array<string,array<int,string>> $refs @param array<string,string> $errors */
    private static function baseValue(string $name, array $spec, string $raw, string $zone, array $refs, array &$errors): mixed
    {
        switch ($spec['type']) {
            case 'int':
                if ($raw === '') {
                    return 0;
                }
                if (preg_match('/^\d{1,7}$/', $raw) !== 1) {
                    $errors[$name] = 'invalid';

                    return 0;
                }

                return (int) $raw;
            case 'slug':
                if ($raw === '') {
                    return null; // generated from the title by the controller
                }
                if (!Slug::valid($raw)) {
                    $errors[$name] = 'invalid_slug';
                }

                return $raw;
            case 'datetime':
                if ($raw === '') {
                    return null;
                }
                $utc = Time::inputToUtc($raw, $zone);
                if ($utc === null) {
                    $errors[$name] = 'invalid';
                }

                return $utc;
            case 'ref':
                if ($raw === '') {
                    return null;
                }
                if (preg_match('/^\d{1,10}$/', $raw) !== 1 || !isset(($refs[$spec['ref']] ?? [])[(int) $raw])) {
                    $errors[$name] = 'ref_missing';

                    return null;
                }

                return (int) $raw;
        }

        throw new \LogicException('Unknown field type.');
    }
}
