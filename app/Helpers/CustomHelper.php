<?php

namespace App\Helpers;
use Carbon\Carbon;
use App\Models\Customers\CustomerAccount;
use App\Models\Customers\Customer;
use App\Models\Configurations\Setting;
use App\Enums\Orders\OrderPaymentStatus;
use App\Enums\Orders\OrderPaymentMethod;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Symfony\Component\Mime\DraftEmail;
use App\Models\Customers\Invoice;



class CustomHelper
{
    public static function generateInvoiceNumber(): string
    {
        $year = Carbon::now()->format('Y');

        // Find last invoice of current year
        $lastInvoice = Invoice::whereYear('invoice_date', $year)
            ->orderByDesc('id')
            ->first();

        $lastNumber = 0;

        if ($lastInvoice && preg_match('/INV-' . $year . '-(\d+)/', $lastInvoice->invoice_number, $matches)) {
            $lastNumber = (int) $matches[1];
        }

        $nextNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);

        return "INV-{$year}-{$nextNumber}";
    }
    public static function formatCurrency($value)
    {
        if (is_null($value)) {
            return '0';
        }

        return config('app.currency.code') . number_format($value, 2);
    }

    public static function getAvailableCredit($customer)
    {
        if (!(($customer->credit_limit ?? 0) > 0) || ($customer->is_credit_account ?? 0) != 1) {
            return 0;
        }

        $creditLimit = $customer->credit_limit ?? 0;
        $accounts = $customer->accounts ?? [];

        $salesTaxSetting = Setting::where('setting_name', 'sales_tax')->first();
        $salesTaxRate = (float) ($salesTaxSetting?->setting_value ?? 0.0);

        $balanceAdjustment = 0;

        foreach ($accounts as $account) {
            $type = strtolower($account->type);
            $amount = $account->amount;
            $taxable = $customer->getTaxStatus() === 'Taxable';
            $tax = 0;

            switch ($type) {
                case 'payment':
                    $tax = $taxable ? 0 : 0; // payments have no tax added in available credit
                    $balanceAdjustment += $amount; // payment increases available credit
                    break;

                case 'refund':
                    $tax = $taxable ? $salesTaxRate : 0;
                    $amountWithTax = $amount + $amount * $tax;
                    $balanceAdjustment += $amountWithTax; // refund increases available credit
                    break;

                case 'discount':
                    $balanceAdjustment += $amount; // discount increases available credit
                    break;

                case 'charge':
                    if ($account->sales_tax_type === 'add') {
                        $tax = $salesTaxRate;
                        $amountWithTax = $amount + $amount * $tax;
                    } elseif ($account->sales_tax_type === 'reverse') {
                        $tax = $salesTaxRate;
                        $amountWithTax = $amount; // tax was already included
                    } else {
                        $amountWithTax = $amount;
                    }

                    $balanceAdjustment -= $amountWithTax; // charge decreases available credit
                    break;

                case 'order':
                    $tax = $account->sales_tax ?? 0;
                    $amountWithTax = $amount + $amount * $tax;
                    $balanceAdjustment -= $amountWithTax; // order decreases available credit
                    break;
            }
        }

        return $creditLimit + $balanceAdjustment;
    }

    public static function isBadDebitCustomer($customer): bool
    {
        //  >45 days since last payment (danger)
        if (isset($customer->payment_status_badge) && $customer->payment_status_badge === 'danger') {
            return true;
        }

        //  Credit problems (credit_limit <= 0 OR NULL OR not a credit account)
        //    BUT only considered bad debt if available_credit_balance > 0
        $creditIssue = $customer->credit_limit <= 0 || is_null($customer->credit_limit) || $customer->is_credit_account == 0;

        if ($creditIssue && $customer->available_credit_balance > 0) {
            return true;
        }

        return false;
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

    public static function formatDateTime($dateTime, $format = null)
    {
        if (empty($dateTime)) {
            return null;
        }

        $format = $format ?? config('app.date.date_time_format', 'd/m/Y H:i A');
        return Carbon::parse($dateTime)->format($format);
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

    public static function statusBadge(string|OrderPaymentStatus|null $status): string
    {
        // Convert enum to string value if needed
        if ($status instanceof OrderPaymentStatus) {
            $status = $status->value;
        }

        // Handle null fallback
        $status ??= 'N/A';

        // Normalize for matching (e.g., convert 'Paid' to 'paid')
        $normalizedStatus = strtolower($status);

        $classes = [
            'published' => 'bg-green-100 text-green-800',
            'active' => 'bg-green-100 text-green-800',
            'inactive' => 'bg-red-100 text-red-800',
            'pending' => 'bg-yellow-100 text-yellow-800',
            'account' => 'bg-blue-100 text-blue-800',
            'partial refund' => 'bg-orange-100 text-orange-800',
            'refunded' => 'bg-purple-100 text-purple-800',
            'paid' => 'bg-green-100 text-green-800',
            'failed' => 'bg-red-100 text-red-800',
            'yes' => 'bg-green-100 text-green-800',
            'no' => 'bg-red-100 text-red-800',
            'draft' => 'bg-blue-100 text-blue-800',
            'rented' => 'bg-purple-100 text-purple-800',
            'available' => 'bg-green-100 text-green-800',
            'damaged' => 'bg-red-100 text-red-800',
            'maint. hold' => 'bg-warning-100 text-warning-800'
        ];

        $class = $classes[$normalizedStatus] ?? 'bg-gray-200 text-gray-800';

        return '<span class="px-2 py-1 rounded text-xs font-semibold ' . $class . '">' . ucfirst($status) . '</span>';
    }

    public static function paymentMethodLabel(null|string|OrderPaymentMethod $method): string
    {
        if ($method instanceof OrderPaymentMethod) {
            return $method->label();
        }

        return OrderPaymentMethod::tryFrom($method)?->label() ?? 'N/A';
    }

    public static function updateCreditBalance(CustomerAccount $record, float $externalTaxAmount = 0.0): void
    {
        $maxRetries = 5;
        $attempt = 0;

        while (true) {
            try {
                DB::transaction(function () use ($record, $externalTaxAmount) {
                    // Lock the customer row for update to prevent concurrent conflicts
                    $customer = Customer::findOrFail($record->customer_id);
                    $currentBalance = $customer->available_credit_balance ?? 0;
                    $newBalance = $currentBalance;

                    $salesTaxSetting = Setting::where('setting_name', 'sales_tax')->first();
                    $salesTaxRate = (float) ($salesTaxSetting?->setting_value ?? 0.0);

                    switch ($record->type) {
                        case 'payment':
                            if ($customer->getTaxStatus() === 'Taxable') {
                                $record->sales_tax = $salesTaxRate;

                                $amountWithTax = $record->amount;

                                // $amountWithTax = $record->amount + ($record->amount * $record->sales_tax);
                            } else {
                                $record->sales_tax = 0;
                                $amountWithTax = $record->amount;
                            }

                            $newBalance -= $amountWithTax;
                            break;

                        case 'refund':
                            if ($customer->getTaxStatus() === 'Taxable') {
                                $record->sales_tax = $salesTaxRate;
                                $amountWithTax = $record->amount + $record->amount * $record->sales_tax;
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
                            if ($record->sales_tax_type === 'add') {
                                $record->sales_tax = $salesTaxRate;
                                $amountWithTax = $record->amount + $record->amount * $record->sales_tax;
                            } elseif ($record->sales_tax_type === 'reverse') {
                                $record->sales_tax = $salesTaxRate;

                                $amountWithTax = $record->amount;
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
                });

                break; // If transaction succeeds, exit retry loop
            } catch (QueryException $e) {
                // Deadlock error code in MySQL is 40001
                if ($e->getCode() === '40001' && ++$attempt <= $maxRetries) {
                    usleep(100000); // wait 100ms before retrying
                    continue;
                }
                throw $e; // rethrow other exceptions or if retries exhausted
            }
        }
    }
    public static function reverseTransactionEffect(CustomerAccount $record): void
    {
        $customer = Customer::findOrFail($record->customer_id);
        $currentBalance = $customer->available_credit_balance ?? 0;
        $adjustedBalance = $currentBalance;

        switch ($record->type) {
            case 'payment':
                // $salesTaxAmount = $record->sales_tax > 0 ? $record->amount - ($record->amount ?? 0) / (1 + $record->sales_tax) : 0;

                $adjustedBalance += $record->amount;
                break;

            case 'refund':
                $salesTaxAmount = $record->sales_tax > 0 ? $record->amount * $record->sales_tax : 0;

                $adjustedBalance += $record->amount + $salesTaxAmount;
                break;

            case 'discount':
                $adjustedBalance += $record->amount;
                break;

            case 'charge':
                if ($record->sales_tax_type === 'reverse') {
                    $adjustedBalance -= $record->amount;
                    break;
                } else {
                    $salesTaxAmount = $record->sales_tax > 0 ? $record->amount * $record->sales_tax : 0;
                    $adjustedBalance -= $record->amount + $salesTaxAmount;
                    break;
                }

            case 'order':
                $salesTaxAmount = $record->sales_tax > 0 ? $record->amount * $record->sales_tax : 0;

                $adjustedBalance -= $record->amount + $salesTaxAmount;
                break;
        }

        $record->balance = $adjustedBalance;
        $record->save();

        $customer->available_credit_balance = $adjustedBalance;
        $customer->save();
    }
}
