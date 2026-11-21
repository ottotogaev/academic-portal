<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Request;

/** Validation of the public contact form. Plain text only; limits protect the inbox and the database. */
final class ContactForm
{
    public const NAME_MAX = 120;
    public const EMAIL_MAX = 190;
    public const SUBJECT_MAX = 190;
    public const MESSAGE_MIN = 10;
    public const MESSAGE_MAX = 5000;

    /**
     * @return array{values:array{name:string,email:string,subject:string,message:string},errors:array<string,string>}
     */
    public static function validate(Request $request): array
    {
        $norm = static fn (string $v): string => str_replace("\r\n", "\n", trim($v));
        $values = [
            'name' => $norm($request->input('name')),
            'email' => $norm($request->input('email')),
            'subject' => $norm($request->input('subject')),
            'message' => $norm($request->input('message')),
        ];
        $errors = [];

        foreach ($values as $field => $value) {
            // Control characters (except newline/tab in the message) have no place in any field.
            $pattern = $field === 'message' ? '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/' : '/[\x00-\x1F\x7F]/';
            if (preg_match($pattern, $value) === 1) {
                $errors[$field] = 'invalid';
            }
        }

        if (!isset($errors['name'])) {
            if (mb_strlen($values['name']) < 2) {
                $errors['name'] = 'required';
            } elseif (mb_strlen($values['name']) > self::NAME_MAX) {
                $errors['name'] = 'too_long';
            }
        }
        if (!isset($errors['email'])) {
            if ($values['email'] === '') {
                $errors['email'] = 'required';
            } elseif (mb_strlen($values['email']) > self::EMAIL_MAX || filter_var($values['email'], FILTER_VALIDATE_EMAIL) === false) {
                $errors['email'] = 'email';
            }
        }
        if (!isset($errors['subject']) && mb_strlen($values['subject']) > self::SUBJECT_MAX) {
            $errors['subject'] = 'too_long';
        }
        if (!isset($errors['message'])) {
            $length = mb_strlen($values['message']);
            if ($length < self::MESSAGE_MIN) {
                $errors['message'] = 'message_short';
            } elseif ($length > self::MESSAGE_MAX) {
                $errors['message'] = 'too_long';
            }
        }

        return ['values' => $values, 'errors' => $errors];
    }
}
