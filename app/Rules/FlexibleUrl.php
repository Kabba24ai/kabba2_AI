<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Accepts any of: internal path (/…), anchor (#…), full URL (https?://…),
 * mailto:, tel: — reusable across all builder sections.
 */
class FlexibleUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return; // nullability enforced separately
        }

        if (
            str_starts_with($value, '/')           // internal path
            || str_starts_with($value, '#')        // anchor
            || str_starts_with($value, 'mailto:')  // email link
            || str_starts_with($value, 'tel:')     // phone link
            || filter_var($value, FILTER_VALIDATE_URL) !== false // full URL
        ) {
            return;
        }

        $fail('The :attribute must be a valid URL, internal path (/…), anchor (#…), mailto:, or tel:.');
    }
}
