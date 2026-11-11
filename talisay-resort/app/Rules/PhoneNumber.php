<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A contact number: 7–20 characters of digits, spaces, +, -, ( or ).
 * Accepts 09171234567, 0917 123 4567, +63 917 123 4567 and (053) 123-4567.
 */
class PhoneNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/^[0-9+()\-\s]{7,20}$/', $value) || preg_match_all('/\d/', $value) < 7) {
            $fail('Enter a valid phone number, for example 09171234567.');
        }
    }
}
