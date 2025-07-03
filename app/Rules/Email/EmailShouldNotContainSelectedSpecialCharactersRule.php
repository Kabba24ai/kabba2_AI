<?php

namespace App\Rules\Email;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EmailShouldNotContainSelectedSpecialCharactersRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (str_contains($value, '+')) {
            $fail('Invalid Email Address.');
        }
    }
}
