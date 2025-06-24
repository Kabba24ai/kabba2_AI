<?php

namespace App\Helpers;

class CustomHelper
{
    public static function formatCurrency($value)
    {
        if (is_null($value)) {
            return '-';
        }

        return config('app.currency.code') . number_format($value, 2);
    }

}
