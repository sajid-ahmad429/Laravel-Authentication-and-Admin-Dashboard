<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\User;

class ValidateUser implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  string  $attribute The attribute name being validated (email, password, etc.)
     * @param  mixed  $value The value of the attribute being validated
     * @param  \Closure  $fail The closure to call if validation fails
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Check if the field is email or password
        if ($attribute === 'email') {
            $user = User::where('email', $value)->first();

            if (!$user) {
                $fail('The email address is not registered.');
            }
        }

        if ($attribute === 'password' && isset($value)) {
            // Assuming the password is validated only when the email exists
            $email = request()->input('email');  // Get the email from the request

            $user = User::where('email', $email)->first();

            if ($user && !password_verify($value, $user->password)) {
                $fail('The provided password is incorrect.');
            }
        }
    }
}

