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
use Illuminate\Support\Facades\Log;
use App\Services\InvoiceCalculationService;
use App\Services\Credit\CreditThresholdMonitor;
use App\Enums\Credit\CreditThresholdSourceType;


class CustomHelper
{

    /**
     * Convert decimal to percentage for display.
     *
     * @param float|null $value
     * @param int $decimals
     * @return float|string
     */
    public static function displayPercentage(?float $value, int $decimals = 2)
    {
        if (is_null($value)) return '';
        return number_format($value * 100, $decimals);
    }


    /**
     * Mark customer's pending invoices as overdue if due date has passed.
     *
     * @param  int|string  $customerId
     * @return int  Number of invoices updated
     */
    public static function markOverdueInvoices($customerId): int
    {
        try {
            $now = Carbon::now();

            // Find invoices that are pending and past due
            $invoices = Invoice::where('customer_id', $customerId)
                ->where('invoice_status', 'pending')
                ->whereDate('due_date', '<', $now)
                ->get();

            // Update each to overdue
            $count = 0;
            foreach ($invoices as $invoice) {
                $invoice->invoice_status = 'overdue';
                $invoice->save();
                $count++;
            }

            if ($count > 0) {
                Log::info("Updated {$count} overdue invoices for customer ID {$customerId}.");
            }

            return $count;
        } catch (\Throwable $e) {
            Log::error('Error marking overdue invoices: ' . $e->getMessage());
            return 0;
        }
    }

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

    /**
     * Remaining available credit for an authorized credit customer.
     *
     *     Available Credit = max(0, credit_limit − outstanding A/R balance)
     *
     * DERIVED from the ONE canonical outstanding balance
     * (customers.available_credit_balance — the same value shown as "Current
     * Balance", maintained by updateCreditBalance()), never re-summed from the
     * ledger. This guarantees Available Credit, Credit Utilization, and Current
     * Balance are always internally consistent, and that an existing A/R
     * balance immediately consumes part of a newly-approved limit.
     *
     * Clamped at 0 so an over-limit customer shows $0 available while the true
     * outstanding remains visible in available_credit_balance (over-limit
     * status stays detectable). A non-authorized customer (no credit account or
     * no positive limit) has no purchasing credit → 0.
     *
     * Previously this re-derived the balance by re-summing $customer->accounts
     * with its own tax arithmetic (divergent from updateCreditBalance — see
     * FINANCIAL_TRUTH_TABLE.md §3b) and hard-returned 0 on the authorization
     * gate, which is why a customer with prior A/R showed $0 / fully-used
     * available credit after approval.
     */
    public static function getAvailableCredit($customer)
    {
        if (($customer->is_credit_account ?? 0) != 1 || ($customer->credit_limit ?? 0) <= 0) {
            return 0;
        }

        $creditLimit = (float) $customer->credit_limit;
        $outstanding = (float) ($customer->available_credit_balance ?? 0);

        return max(0.0, round($creditLimit - $outstanding, 2));
    }

    /**
     * Canonical Bad Debt predicate — delegates to the single authority
     * (CreditAccountSummary). Business-approved rule: positive outstanding
     * balance AND oldest outstanding exposure >= 60 days. Credit-limit
     * configuration is deliberately NOT part of this classification (the former
     * >45-day / credit_limit<=0 / not-a-credit-account logic was removed).
     */
    public static function isBadDebitCustomer($customer): bool
    {
        if (! $customer instanceof Customer) {
            return false;
        }

        return \App\Services\Credit\CreditAccountSummary::for($customer)->badDebt()['is_bad_debt'];
    }

    public static function formatDate($date, $format = null)
    {
        if (empty($date)) {
            return null;
        }

        $format = $format ?? config('app.date.date_format', 'd/m/Y');
        return Carbon::parse($date)->format($format);
    }

    public static function formatTime($time, $format = 'H:i')
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

        $format = $format ?? config('app.date.date_time_format', 'd/m/Y h:i A');
        return Carbon::parse($dateTime)->format($format);
    }

     public static function formatDateTime12Hour($dateTime, $format = null)
    {
        if (empty($dateTime)) {
            return null;
        }

        // Default format: month/day/year - 12-hour time with am/pm
        $format = $format ?? 'm/d/Y - h:i A';

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

    /**
     * Order-payment status badge — always via the centralized presenter,
     * covering every OrderPaymentStatus case (the generic statusBadge()
     * below has no entries for the five Invoice* cases or PartialPayment,
     * and its string-keyed map isn't safe to widen to cover them: several
     * unrelated status vocabularies elsewhere in the app — e.g.
     * terms_and_conditions.status, product_categories.status — also use
     * the literal value "Pending", so guessing intent from the string
     * alone risks mislabeling an unrelated badge).
     */
    public static function paymentStatusBadge(string|OrderPaymentStatus|null $status): string
    {
        $label = \App\Services\PaymentDescriptionPresenter::statusLabel($status);
        $class = \App\Services\PaymentDescriptionPresenter::statusBadgeClasses($status);

        return '<span class="px-2 py-1 rounded text-xs font-semibold ' . $class . '">' . e($label) . '</span>';
    }

    /**
     * Payment Architecture Finalization (Tier 2) — the order-level
     * counterpart to paymentStatusBadge() above. That helper renders a
     * SINGLE order_payments row's status; this one renders an ORDER's
     * aggregate collection + refund state
     * (OrderPaymentSummary::balanceStatusLabel()). Every screen showing
     * "this order's payment status" (order lists, Dispatch, Schedules, CRM
     * customer views) must call this with the order's OrderPaymentSummary
     * instead of passing Order::last_payment_status into
     * paymentStatusBadge() — that reflects only whichever payment happened
     * to be entered last, which disagrees with the order's real state on
     * any order with more than one payment row.
     */
    public static function orderPaymentStatusBadge(\App\Services\Orders\OrderPaymentSummary $summary): string
    {
        $label = \App\Services\PaymentDescriptionPresenter::orderStatusLabel($summary);
        $class = \App\Services\PaymentDescriptionPresenter::orderStatusBadgeClasses($summary);

        return '<span class="px-2 py-1 rounded text-xs font-semibold ' . $class . '">' . e($label) . '</span>';
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
            // NOTE: payment statuses (incl. Account → "On Account") must go
            // through paymentStatusBadge()/PaymentDescriptionPresenter, the
            // canonical payment palette. This generic map is for non-payment
            // status vocabularies only (terms, equipment, store, category);
            // no 'account' entry lives here so it can never emit a divergent
            // Account badge.
            'partial refund' => 'bg-orange-100 text-orange-800',
            'refunded' => 'bg-purple-100 text-purple-800',
            'paid' => 'bg-green-100 text-green-800',
            'failed' => 'bg-red-100 text-red-800',
            'yes' => 'bg-green-100 text-green-800',
            'no' => 'bg-red-100 text-red-800',
            'draft' => 'bg-blue-100 text-blue-800',
            'rented' => 'bg-blue-100 text-blue-700 text-xs font-medium',
            'available' => 'bg-green-100 text-green-700 text-xs font-medium',
            'damaged' => 'bg-red-100 text-red-700 text-xs font-medium',
            'maint. hold' => 'bg-yellow-100 text-yellow-700 text-xs font-medium',
            'accepted' => 'bg-green-100 text-green-800',
            'declined' => 'bg-red-100 text-red-800',
            'exempt' => 'bg-gray-100 text-gray-800',
        ];

        $class = $classes[$normalizedStatus] ?? 'bg-gray-200 text-gray-800';

         // default
    $extraClass = 'text-xs font-semibold';
    $icon = '';

    if (in_array($normalizedStatus, ['rented', 'available', 'damaged', 'maint. hold'])) {
        $extraClass = 'text-xs font-medium';

        $icon = match ($normalizedStatus) {
            'rented'      => '<svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path d="M16 21v-2a4 4 0 0 0-8 0v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>',
            'available'   => '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <path d="m9 11 3 3L22 4"></path>
                                </svg>',
            'damaged'     => '<svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
                                    <path d="M12 9v4"></path>
                                    <path d="M12 17h.01"></path>
                                </svg>',
            'maint. hold' => '<svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 1 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94Z"></path>
                                </svg>',
        };
    }

        return '' . $icon . ' <span class="px-2 py-1 rounded ' . $extraClass . ' ' . $class . '">' . ucfirst($status) . '</span>';
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
                            // Sales Tax Architecture Correction — bounded refund
                            // enhancement: when RefundStoreController has already
                            // resolved a real per-charge treatment (the employee
                            // linked this refund to a specific originating
                            // BillingCharge), use it instead of the customer-
                            // exemption-driven default below. sales_tax_type is
                            // never pre-set by any OTHER caller of this method for
                            // a 'refund' record (RefundStoreController is the only
                            // one that creates type='refund' rows), so this branch
                            // cannot change behavior for a free-form refund with no
                            // linked charge — those keep the exact prior formula.
                            if (\App\Services\ChargeTaxCalculator::isValidTreatment($record->sales_tax_type)) {
                                $resolved = \App\Services\ChargeTaxCalculator::calculate(
                                    (float) $record->amount, $record->sales_tax_type, (float) $record->sales_tax
                                );
                                $record->sales_tax = $resolved['tax_rate'];
                                $amountWithTax = $resolved['total_amount'];
                            } elseif ($customer->getTaxStatus() === 'Taxable') {
                                $record->sales_tax = $salesTaxRate;
                                $amountWithTax = $record->amount + $record->amount * $record->sales_tax;
                            } else {
                                $record->sales_tax = 0;
                                $amountWithTax = $record->amount;
                            }

                            $newBalance -= $amountWithTax;
                            break;

                        case 'discount':

                            if ($customer->getTaxStatus() === 'Taxable') {

                                // $record->sales_tax = $salesTaxRate;
                                // $amountWithTax = $record->amount + $record->amount * $record->sales_tax;

                                   if ($record->sales_tax_type === 'add') {

                                        $record->sales_tax = $salesTaxRate;

                                        $amountWithTax =
                                            $record->amount +
                                            ($record->amount * $record->sales_tax);

                                    } elseif ($record->sales_tax_type === 'reverse') {

                                        $record->sales_tax = $salesTaxRate;

                                        $amountWithTax = $record->amount;

                                    } elseif ($record->sales_tax_type === 'free') {

                                        $record->sales_tax = 0;

                                        $amountWithTax = $record->amount;

                                    } else {

                                        $record->sales_tax = 0;

                                        $amountWithTax = $record->amount;
                                    }


                            } else {
                                $record->sales_tax = 0;
                                $amountWithTax = $record->amount;
                            }

                            $newBalance -= $amountWithTax;
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

                    // Credit Threshold Exception observation (advisory, never a
                    // ceiling). Fires only for approved credit accounts on genuine
                    // new exposure that crosses/extends beyond the limit; deferred
                    // past commit and fully guarded so it can never affect this
                    // financial posting. See CreditThresholdMonitor.
                    CreditThresholdMonitor::observe(
                        $customer,
                        (float) $currentBalance,
                        (float) $newBalance,
                        $record,
                        $record->type === 'order'
                            ? CreditThresholdSourceType::OrderOnAccount
                            : CreditThresholdSourceType::AccountCharge,
                    );
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
                // Mirror the charge/discount reverse handling (LED-2). A
                // reverse-tax refund's `amount` is already tax-inclusive and the
                // posting subtracted exactly `amount`, so the reversal must add
                // back exactly `amount` — NOT amount + amount×rate (which
                // over-restored the balance). Free-form taxable refunds keep the
                // rate-additive restore to match their posting.
                if ($record->sales_tax_type === 'reverse') {
                    $adjustedBalance += $record->amount;
                } else {
                    $salesTaxAmount = $record->sales_tax > 0 ? $record->amount * $record->sales_tax : 0;
                    $adjustedBalance += $record->amount + $salesTaxAmount;
                }
                break;

            case 'discount':
                // $adjustedBalance += $record->amount;
                // break;


                if ($record->sales_tax_type === 'reverse') {

                    $adjustedBalance += $record->amount;
                    break;

                } else {
                      $salesTaxAmount = $record->sales_tax > 0 ? $record->amount * $record->sales_tax : 0;

                    $adjustedBalance += $record->amount + $salesTaxAmount;
                    break;

                }

                $salesTaxAmount = $record->sales_tax > 0 ? $record->amount * $record->sales_tax : 0;

                $adjustedBalance += $record->amount + $salesTaxAmount;
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

        // Log::info('REVERSE RESULT', [
        //     'record_id' => $record->id,
        //     'type' => $record->type,
        //     'adjusted_balance' => $adjustedBalance,
        // ]);
        $record->save();

        $customer->available_credit_balance = $adjustedBalance;
        $customer->save();
    }

    /**
     * Recompute the canonical outstanding A/R balance from the ledger and
     * write it back to customers.available_credit_balance (plus each
     * customer_accounts row's running `balance` snapshot).
     *
     * The outstanding debt is the SAME regardless of credit authorization, so
     * this always forward-accumulates it from the ledger (oldest → newest),
     * mirroring updateCreditBalance()'s incremental logic — charge/order add
     * amount(+tax), payment/refund/discount subtract. Available credit is
     * DERIVED from this figure by getAvailableCredit(), so this method must
     * NOT call getAvailableCredit() (that would be circular now that the helper
     * reads available_credit_balance). This decoupling replaced the former
     * credit-account branch that seeded the running balance from
     * `credit_limit − getAvailableCredit()`.
     */
    public static function fixTheRunningBalance(int $customerId): void
    {
        DB::transaction(function () use ($customerId) {

            $customer = Customer::lockForUpdate()->findOrFail($customerId);

            $accounts = CustomerAccount::where('customer_id', $customerId)
                ->orderBy('id') // oldest → newest
                ->get();

            $runningBalance = 0.0;

            foreach ($accounts as $account) {
                $runningBalance = round($runningBalance + self::ledgerRowDelta($account), 2);
                $account->balance = $runningBalance;
                $account->save();
            }

            $customer->available_credit_balance = $runningBalance;
            $customer->save();
        });
    }

    /**
     * Signed contribution of ONE ledger row to the outstanding A/R balance:
     * charge/order INCREASE the debt (+), payment/refund/discount REDUCE it (−),
     * tax-aware (tax added except for payments and reverse-taxed charge/discount
     * rows, whose amount is already tax-inclusive). The single canonical
     * per-row rule shared by fixTheRunningBalance() and
     * recomputeOutstandingBalance() so the repair preview and the write agree.
     */
    public static function ledgerRowDelta(CustomerAccount $account): float
    {
        $amount  = (float) $account->amount;
        $taxRate = (float) ($account->sales_tax ?? 0);

        $totalWithTax = $amount;
        if ($taxRate > 0 &&
            !(
                $account->type === 'payment' ||
                // Reverse-taxed rows store an ALREADY tax-inclusive `amount`, so
                // the rate must NOT be re-applied. This holds for charge and
                // discount AND for refund — a linked reverse-tax refund
                // (RefundStoreController::storeLinkedRefund) stores the full
                // tax-inclusive credit as `amount` with sales_tax_type='reverse'.
                // Omitting 'refund' here was LED-1: recompute re-added amount×rate,
                // over-reducing A/R and corrupting balances on any repair/recompute.
                (in_array($account->type, ['charge', 'discount', 'refund'], true) && $account->sales_tax_type === 'reverse')
            )
        ) {
            $totalWithTax += ($amount * $taxRate);
        }

        return match ($account->type) {
            'charge', 'order'               => $totalWithTax,
            'payment', 'refund', 'discount' => -$totalWithTax,
            default                         => 0.0,
        };
    }

    /**
     * Read-only recompute of a customer's canonical outstanding A/R balance
     * from the ledger (no writes) — the value fixTheRunningBalance() would
     * persist. Used by the credit-balance repair command's dry-run to detect
     * drift between the stored available_credit_balance and the ledger.
     */
    public static function recomputeOutstandingBalance(int $customerId): float
    {
        $total = CustomerAccount::where('customer_id', $customerId)
            ->orderBy('id')
            ->get()
            ->reduce(fn ($carry, $account) => round($carry + self::ledgerRowDelta($account), 2), 0.0);

        return (float) $total;
    }

    /**
     * Get customer account status + alert metadata
     */
    public static function getCustomerAccountStatus(Customer $customer): array
    {
        $days = $customer->days_since_last_payment;

        $alert = [
            'show' => false,
            'color' => null,
            'days' => $days,
        ];

        /**
         * Bad Debt — canonical, business-approved rule via the single authority
         * (isBadDebitCustomer → CreditAccountSummary): positive outstanding
         * balance AND oldest outstanding exposure >= 60 days. Credit-limit
         * configuration is NOT consulted (the former "not approved + no credit
         * limit + balance" Rule 1 and the "days_since_last_payment >= 60" Rule 2
         * were removed). Bad Debt is deliberately independent of over-limit.
         */
        if (self::isBadDebitCustomer($customer)) {
            return [
                'status' => 'Bad Debt',
                'badge' => [
                    'label' => 'Bad Debt',
                    'bg' => 'bg-red-100',
                    'text' => 'text-red-800',
                ],
                'alert' => [
                    'show' => true,
                    'color' => 'red',
                    'days' => $days,
                ],
            ];
        }

        /**
         * Past-due alert coloring (DISPLAY only — NOT Bad Debt) based on days
         * since last account payment. Preserved unchanged.
         */
        if ($days !== null) {
            if ($days > 45) {
                $alert = ['show' => true, 'color' => 'red', 'days' => $days];
            } elseif ($days > 30) {
                $alert = ['show' => true, 'color' => 'orange', 'days' => $days];
            } elseif ($days > 0) {
                $alert = ['show' => true, 'color' => 'yellow', 'days' => $days];
            }
        }

        return [
            'status' => 'Good Standing',
            'badge' => [
                'label' => 'Good Standing',
                'bg' => 'bg-green-100',
                'text' => 'text-green-800',
            ],
            'alert' => $alert,
        ];
    }



    /**
     * @deprecated Delegates to InvoiceCalculationService::recomputeSummary()
     * as of Phase 2.4 (docs/financial-engine-consolidation/PHASE_2_4_COMPLETION_REPORT.md).
     * Kept under this name so existing callers (CustomerAccount\UpdateController,
     * CustomerAccount\DeleteController, Invoice\UpdateController,
     * Invoice\DeleteInvoiceController, BulkDeleteController,
     * RepairDeletedOrdersController) require no changes. New code should
     * call InvoiceCalculationService::recomputeSummary() directly.
     */
    public static function updateInvoiceSummary(Invoice $invoice): void
    {
        InvoiceCalculationService::recomputeSummary($invoice);
    }


    public static function calculateSalesTaxRate(
    float $subtotal,
    float $taxAmount
    ): float {

        if ($subtotal <= 0) {
            return 0;
        }

        return $taxAmount / $subtotal;
    }

   public static function calculateRefundSalesTax($refundAmount, $subtotal, $taxAmount)
{
    $refundAmount = (float) $refundAmount;
    $subtotal     = (float) $subtotal;
    $taxAmount    = (float) $taxAmount;

    if ($refundAmount <= 0 || $subtotal <= 0 || $taxAmount <= 0) {
        return 0;
    }

    $taxRate = $taxAmount / $subtotal;

    return round(
        $refundAmount - ($refundAmount / (1 + $taxRate)),
        2
    );
}

public static function formatCallReason(?string $reason): string
{
    $labels = [
        'contract_renewal'       => 'Contract Renewal',
        'delivery_pickup'        => 'Delivery / Pickup',
        'equipment_availability' => 'Equipment Availability',
        'equipment_return'       => 'Equipment Return',
        'general_followup'       => 'General Follow-up',
        'maintenance_request'    => 'Maintenance Request',
        'order_review'           => 'Order Review',
        'payment_followup'       => 'Payment Follow-up',
        'rental_inquiry'         => 'Rental Inquiry',
        'returning_call'         => 'Returning Their Call',
        'availability_lead_time' => 'Availability / Lead Time',
        'equipment_service'      => 'Equipment Service / Technical Support',
        'invoice_billing'        => 'Invoice / Billing Question',
        'order_parts'            => 'Order Parts',
        'order_status'           => 'Order Status',
        'other'                  => 'Other',
        'price_quote'            => 'Price Quote',
        'return_exchange'        => 'Return / Exchange',
        'warranty_defective'     => 'Warranty / Defective Item',
    ];

    return $labels[$reason] ?? ucwords(str_replace('_', ' ', $reason ?? ''));
}

/**
 * Customer-facing label for a delivery service option. The stored enum
 * values ('Delivery + Pickup', 'Delivery Only', 'Return Only') are kept for
 * data compatibility; only the display wording changes — "Return Pickup"
 * avoids confusion with in-store pickup.
 */
public static function serviceOptionLabel(?string $serviceOption): string
{
    return match ($serviceOption) {
        'Delivery + Pickup' => 'Delivery + Return Pickup',
        'Delivery Only'     => 'Delivery Only',
        'Return Only'       => 'Return Pickup Only',
        default             => (string) $serviceOption,
    };
}
}
