<?php

namespace App\Helpers;
use Carbon\Carbon;
use App\Models\Customers\CustomerAccount;
use App\Models\Customers\Customer;

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

    public static function formatTime($time, $format = 'H:i A')
    {
        if (empty($time)) {
            return null;
        }

        return Carbon::parse($time)->format($format);
    }

    public static function parseDateFromInput($date)
    {
        if (empty($date)) {
            return null;
        }

        $inputFormat = config('app.date.date_format', 'd/m/Y');
        $dbFormat = config('app.date.db_date_format', 'Y-m-d');

        return Carbon::createFromFormat($inputFormat, $date)->format($dbFormat);
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


       public static function updateCreditBalance(CustomerAccount $record): void
    {
        $customer = Customer::findOrFail($record->customer_id);
        $currentBalance = $customer->available_credit_balance ?? 0;
        $newBalance = $currentBalance;

        switch ($record->type) {
            case 'payment':
            case 'discount':
                $newBalance -= $record->amount;
                break;

            case 'refund':
                $newBalance += $record->amount;
                break;

            case 'charge':
                $chargeAmount = $record->amount;
                if ($record->sales_tax_type === 'add') {
                    $chargeAmount += ($record->amount * $record->sales_tax);
                }
                $newBalance += $chargeAmount;
                break;
        }

        // Update both balances
        $record->balance = $newBalance;
        $record->save();

        $customer->available_credit_balance = $newBalance;
        $customer->save();
    }



}
