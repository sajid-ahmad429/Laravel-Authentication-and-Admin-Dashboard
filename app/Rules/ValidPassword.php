<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\InvokableRule;

/**
 * Shared strong-password policy.
 *
 * Usage: 'password' => ['required', ValidPassword::rules()]
 */
class ValidPassword implements ValidationRule
{
    /**
     * Rule array for the Validator.
     *
     * @return array<int, mixed>
     */
    public static function rules(): array
    {
        return [
            'min:8',
            'max:64',
            'regex:/[a-z]/',      // at least one lowercase letter
            'regex:/[A-Z]/',      // at least one uppercase letter
            'regex:/[0-9]/',      // at least one digit
            'regex:/[@$!%*?&^#\-_+=.]/', // at least one special character
        ];
    }

    /**
     * Messages for the rule array (pair with ->validate(['password' => self::messages()])).
     *
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'password.min'   => 'The password must be at least 8 characters.',
            'password.max'   => 'The password may not exceed 64 characters.',
            'password.regex' => 'The password must include upper & lower case letters, a number and a special character.',
        ];
    }

    /**
     * Invokable rule implementation (when used standalone).
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || strlen($value) < 8) {
            $fail('The :attribute must be at least 8 characters.');

            return;
        }

        if (strlen($value) > 64) {
            $fail('The :attribute may not exceed 64 characters.');

            return;
        }

        foreach (['/[a-z]/', '/[A-Z]/', '/[0-9]/', '/[@$!%*?&^#\-_+=.]/'] as $pattern) {
            if (! preg_match($pattern, $value)) {
                $fail('The :attribute must include upper & lower case letters, a number and a special character.');

                return;
            }
        }
    }
}
