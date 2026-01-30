<?php

namespace App\Helpers;
use Stevebauman\Purify\Facades\Purify;

class PurifyHelper
{
    public static function purify($requestArr, array $exceptFields = [])
    {
        $config = config('purify.configs.default'); // or just use 'default'

        if (is_array($requestArr)) {
            $clean = [];

            foreach ($requestArr as $key => $value) {
                if (is_array($value)) {
                    $clean[$key] = self::purify($value, $exceptFields);
                    continue;
                }

                if ($value === '' || $value === null) {
                    $clean[$key] = null;
                    continue;
                }

                // Non-strings (ints, bools, etc.) — keep as-is
                if (!is_string($value)) {
                    $clean[$key] = $value;
                    continue;
                }

                if (in_array($key, $exceptFields, true)) {
                    // Completely skip sanitizing these fields
                    $clean[$key] = html_entity_decode($value, ENT_QUOTES | ENT_HTML5);
                } else {
                    $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5);
                    $cleaned = Purify::config($config)->clean($decoded); // or 'default'
                    // Decode &amp; back to & after purification
                    $clean[$key] = html_entity_decode($cleaned, ENT_QUOTES | ENT_HTML5);
                }
            }

            return $clean;
        }

        // Scalar input
        if ($requestArr !== '' && $requestArr !== null) {
            if (!is_string($requestArr)) {
                return $requestArr;
            }
            $decoded = html_entity_decode($requestArr, ENT_QUOTES | ENT_HTML5);
            return Purify::config($config)->clean($decoded);
        }

        return null;
    }
}
