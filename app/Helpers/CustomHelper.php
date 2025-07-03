<?php

namespace App\Helpers;
use Carbon\Carbon;

class CustomHelper
{
    public static function formatCurrency($value)
    {
        if (is_null($value)) {
            return '-';
        }

        return config('app.currency.code') . number_format($value, 2);
    }
    public static function formatDate($date, $format = null)
    {
        if (empty($date)) {
            return null;
        }

        $format = $format ?? config('app.date.date_format', 'd/m/Y');
        return Carbon::parse($date)->format($format);
    }


   
    public static function unformatPhone(string $formattedPhone): string
    {
        return preg_replace('/[^0-9]/', '', $formattedPhone); // remove non-digits
    }

   
    public static function formatPhone(?string $rawPhone): string
    {

        if (empty($rawPhone)) {
            return 'N/A'; // or return ''; or return $rawPhone; based on your needs
        }

        $digits = preg_replace('/[^0-9]/', '', $rawPhone);
        if (strlen($digits) !== 10) return $rawPhone; // fallback

        return sprintf('(%s) %s-%s',
            substr($digits, 0, 3),
            substr($digits, 3, 3),
            substr($digits, 6, 4)
        );
    }


}
