<?php

use Illuminate\Contracts\Encryption\DecryptException;

if (! function_exists('safe_decrypt')) {
    /**
     * Safely decrypt a string encrypted by Laravel's Crypt facade.
     * Returns $default instead of throwing DecryptException when the
     * payload is missing, empty, or was encrypted with a different key.
     */
    function safe_decrypt(?string $value, string $default = ''): string
    {
        if (empty($value)) {
            return $default;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return $default;
        }
    }
}
