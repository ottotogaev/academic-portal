<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Request;

/** Validation of the admin profile form. Field names: email, phone, "{locale}__{field}". */
final class ProfileForm
{
    private const MAX = ['full_name' => 255, 'position' => 255, 'academic_degree' => 255, 'specialization' => 255,
        'biography' => 20000, 'education' => 20000, 'experience' => 20000];

    /**
     * @param list<string> $locales first is the required default locale
     * @return array{base:array{email:?string,phone:?string},translations:array<string,array<string,string>>,old:array<string,string>,errors:array<string,string>}
     */
    public static function validate(Request $request, array $locales): array
    {
        $errors = [];
        $old = [];

        $email = trim($request->input('email'));
        $old['email'] = $email;
        if ($email !== '' && (mb_strlen($email) > 190 || filter_var($email, FILTER_VALIDATE_EMAIL) === false)) {
            $errors['email'] = 'email';
        }
        $phone = trim($request->input('phone'));
        $old['phone'] = $phone;
        if ($phone !== '' && preg_match('/^[0-9+()\s.-]{5,40}$/', $phone) !== 1) {
            $errors['phone'] = 'phone';
        }

        $translations = [];
        foreach ($locales as $index => $locale) {
            $fields = [];
            $any = false;
            foreach (self::MAX as $field => $max) {
                $key = $locale . '__' . $field;
                $value = str_replace("\r\n", "\n", trim($request->input($key)));
                $old[$key] = $value;
                $fields[$field] = $value;
                $any = $any || $value !== '';
                if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value) === 1) {
                    $errors[$key] = 'invalid';
                } elseif (mb_strlen($value) > $max) {
                    $errors[$key] = 'too_long';
                }
            }
            if (($index === 0 || $any) && $fields['full_name'] === '' && !isset($errors[$locale . '__full_name'])) {
                $errors[$locale . '__full_name'] = 'required';
            }
            $translations[$locale] = $fields;
        }

        return [
            'base' => ['email' => $email === '' ? null : $email, 'phone' => $phone === '' ? null : $phone],
            'translations' => $translations,
            'old' => $old,
            'errors' => $errors,
        ];
    }
}
