<?php

namespace App\Helpers;
use Carbon\Carbon;
use App\Models\Customers\CustomerAccount;
use App\Models\Customers\Customer;
use App\Models\Configurations\Setting;

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
        if (strlen($digits) !== 10) {
            return $rawPhone;
        } // fallback

        return sprintf('(%s) %s-%s', substr($digits, 0, 3), substr($digits, 3, 3), substr($digits, 6, 4));
    }

    public static function statusBadge(string $status): string
    {
        $classes = [
            'active'    => 'bg-green-100 text-green-800',
            'inactive'  => 'bg-red-100 text-red-800',
            'pending' => 'bg-yellow-100 text-yellow-800',
            'account' => 'bg-blue-100 text-blue-800',
            'partial refund' => 'bg-orange-100 text-orange-800',
            'refunded' => 'bg-purple-100 text-purple-800',
            'paid' => 'bg-green-100 text-green-800',
            'failed' => 'bg-red-100 text-red-800',
        ];

        $class = $classes[strtolower($status)] ?? 'bg-gray-200 text-gray-800';

        return '<span class="px-2 py-1 rounded text-xs font-semibold ' . $class . '">'
            . ucfirst($status) .
            '</span>';
    }


    
    public static function updateCreditBalance(CustomerAccount $record, float $externalTaxAmount = 0.00): void
{
    $customer = Customer::findOrFail($record->customer_id);
    $currentBalance = $customer->available_credit_balance ?? 0;
    $newBalance = $currentBalance;

    $salesTaxSetting = Setting::where('setting_name', 'sales_tax')->first();
    $salesTaxRate = (float) ($salesTaxSetting?->setting_value ?? 0.00);

    switch ($record->type) {
        case 'payment':
        case 'refund':
            if ($customer->getTaxStatus() === 'Taxable') {
                $record->sales_tax = $salesTaxRate;
                $amountWithTax = $record->amount + ($record->amount * $record->sales_tax);
            } else {
                $record->sales_tax = 0;
                $amountWithTax = $record->amount;
            }

            $newBalance -= $amountWithTax;
            break;

        case 'discount':
            $record->sales_tax = 0;
            $newBalance -= $record->amount;
            break;

        case 'charge':
            if (
                $record->sales_tax_type === 'add'
            ) {
                $record->sales_tax = $salesTaxRate;
                $amountWithTax = $record->amount + ($record->amount * $record->sales_tax);
            } else {
                $record->sales_tax = 0;
                $amountWithTax = $record->amount;
            }

            $newBalance += $amountWithTax;
            break;

        case 'order':
            // $record->sales_tax = 0;
            $newBalance += $record->amount + $externalTaxAmount;
            break;
    }

    $record->balance = $newBalance;
    $record->save();

    $customer->available_credit_balance = $newBalance;
    $customer->save();
}
public static function reverseTransactionEffect(CustomerAccount $record): void
{
    $customer = Customer::findOrFail($record->customer_id);
    $currentBalance = $customer->available_credit_balance ?? 0;
    $adjustedBalance = $currentBalance;

    $salesTaxAmount = $record->sales_tax > 0 ? $record->amount * $record->sales_tax : 0;

    switch ($record->type) {
        case 'payment':
        case 'refund':
            $adjustedBalance += $record->amount + $salesTaxAmount;
            break;

        case 'discount':
            $adjustedBalance += $record->amount;
            break;

        case 'charge':
        case 'order':
            $adjustedBalance -= $record->amount + $salesTaxAmount;
            break;
    }

    $record->balance = $adjustedBalance;
    $record->save();

    $customer->available_credit_balance = $adjustedBalance;
    $customer->save();
}
}
