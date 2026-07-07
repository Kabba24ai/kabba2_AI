<?php

namespace App\Services;

use App\Http\DataObjects\TaxBreakdown;
use App\Models\Configurations\Setting;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerAccount;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Financial Engine — Version 2.1, Phase 2.6-2.7 foundation.
 *
 * TRANSACTION-SAFE (see docs/financial-engine-consolidation/
 * PHASE_2_3A_CALCULATION_PATTERN_ARCHITECTURE.md §6). This class implements
 * the policy already approved in docs/financial-engine-consolidation/
 * FINANCIAL_TRUTH_TABLE.md — it does not define, reinterpret, or guess at
 * business policy. Every branch below is either (a) an exact port of the
 * existing production formula in `CustomHelper::updateCreditBalance()`
 * (re-verified directly against that method during Phase 2.6, not assumed),
 * or (b) an explicit refusal to guess, for every transaction type the Truth
 * Table marks as Pending Business Decision, Undefined, or out of scope.
 *
 * As of Phase 2.7, this class has ZERO production callers. It exists to be
 * built and validated in isolation, exactly as `TaxCalculationService`
 * was in Phase 2.1 — no controller, model observer, or job may call it yet.
 * `CustomHelper::updateCreditBalance()` remains the production-serving
 * implementation, untouched, until a future phase (2.8+) migrates callers
 * onto this class after the conditions in `PHASE_2_5A_LEDGER_READINESS_REVIEW.md`
 * §10 / `FINANCIAL_TRUTH_TABLE.md` §7 are met.
 *
 * Phase 2.7 added {@see self::applyTransaction()}, the database-write
 * wrapper around {@see self::amountWithTax()} — structurally mirroring
 * `CustomHelper::updateCreditBalance()`'s customer lookup, tax-rate
 * resolution, balance computation, persistence, and deadlock-retry
 * behavior, re-verified directly against that method's live source during
 * Phase 2.7 (not assumed from Phase 2.6). Its write-path equivalence with
 * production was independently proven via live, rolled-back comparison —
 * see `docs/financial-engine-consolidation/PHASE_2_7_COMPLETION_REPORT.md`.
 * `applyTransaction()` does NOT support Extension Charge — that type never
 * creates a `customer_accounts` row for this method to operate on; it
 * remains served by `amountWithTax()` alone (see `TYPE_EXTENSION`'s
 * docblock).
 *
 * Implemented transaction types (all "Ready Now" per FINANCIAL_TRUTH_TABLE.md §7):
 *   - Payment            (`customer_accounts.type = 'payment'`)
 *   - Order              (`customer_accounts.type = 'order'`)
 *   - Manual Charge, Fuel Charge, Damage Charge
 *     (`customer_accounts.type = 'charge'` — these three share byte-identical
 *     tax treatment; they differ only by the `reason` column, which has no
 *     effect on tax calculation, so they are implemented as one branch)
 *   - Extension Charge    (never creates a `customer_accounts` row — see
 *     `TYPE_EXTENSION`'s docblock below for why it is still handled here)
 *
 * Deliberately NOT implemented (calling amountWithTax() with any of these
 * throws immediately, per FINANCIAL_TRUTH_TABLE.md §7 "Needs Business
 * Decision" / "Undefined" classifications):
 *   - Refund, Discount    (Pending Business Decision — the four legacy
 *     methods disagree on tax treatment; see FINANCIAL_TRUTH_TABLE.md §3b
 *     rows 2-3)
 *   - Credit, Debit       (Undefined — confirmed dead code, zero existing
 *     precedent to implement against; see FINANCIAL_TRUTH_TABLE.md §3b
 *     rows 4-5)
 *   - Account Invoice special handling (out of scope — this transaction
 *     type never affects a balance at all; see FINANCIAL_TRUTH_TABLE.md
 *     §3b row 12)
 *   - Promotional Credit, Bad Debt, Settlement Adjustments, Payment Plans
 *     (no corresponding transaction type exists anywhere in this codebase
 *     today — these are named in the Phase 2.6 mission as explicitly out
 *     of scope, not as existing types this class chose to skip)
 *   - Aging, Collections  (not calculation concerns at all — see
 *     FINANCIAL_TRUTH_TABLE.md §5 item 10, an unresolved scope question,
 *     not something `amountWithTax()` could answer regardless)
 */
class LedgerBalanceService
{
    /** `customer_accounts.type = 'payment'`. Tax-inclusive; amount is never modified by tax. */
    public const TYPE_PAYMENT = 'payment';

    /**
     * `customer_accounts.type = 'charge'`. Covers Manual Charge, Fuel Charge,
     * and Damage Charge — all three share identical tax treatment; they are
     * distinguished only by the `reason` column (e.g. 'Fuel Charge',
     * 'Damages', or an arbitrary manual-charge reason), which this method
     * does not need to know about.
     */
    public const TYPE_CHARGE = 'charge';

    /** `customer_accounts.type = 'order'`. Tax is supplied by the caller, not computed here. */
    public const TYPE_ORDER = 'order';

    /**
     * Extension Charge. Never creates a `customer_accounts` row (confirmed
     * in FINANCIAL_TRUTH_TABLE.md §3b row 9 — `customer_account_id` is
     * explicitly null for this type), so it has no `customer_accounts.type`
     * value of its own. Included here — even though it will never flow
     * through `applyTransaction()`/`reverseTransaction()` in a future
     * phase — because `Extension\StoreController` currently computes its
     * own tax inline (`round($baseAmount * $salesTaxRate, 2)`), duplicating
     * the exact formula `TaxCalculationService::addTaxToExclusiveAmount()`
     * already centralizes. Exposing it here gives that controller a correct
     * migration target without this class ever needing to touch a ledger row.
     */
    public const TYPE_EXTENSION = 'extension';

    /**
     * The unified tax-inclusion decision for the Financial Engine.
     *
     * TRANSACTION-SAFE. Returns a rounded, storable {@see TaxBreakdown} for
     * any of the four implemented transaction types. Every formula below is
     * delegated to {@see TaxCalculationService} — this method contains no
     * tax arithmetic of its own, only the policy lookup of which formula
     * applies to which transaction type, per FINANCIAL_TRUTH_TABLE.md.
     *
     * @param  string  $transactionType  One of the `TYPE_*` constants on this class.
     * @param  float  $amount  The transaction's stored `amount` column.
     * @param  float  $taxRate  The tax rate as a decimal (e.g. 0.0887 for 8.87%).
     *                          Ignored for `TYPE_ORDER` (tax is supplied via
     *                          $externalTaxAmount instead — see below).
     * @param  string|null  $salesTaxType  The transaction's `sales_tax_type`
     *                                     column (`'add'`, `'reverse'`, `'free'`, or
     *                                     null). Required for `TYPE_CHARGE` and
     *                                     `TYPE_EXTENSION`; ignored for `TYPE_PAYMENT`
     *                                     and `TYPE_ORDER`, which have no such column.
     * @param  bool  $customerTaxable  Whether the customer's tax status is
     *                                 'Taxable' (`Customer::getTaxStatus()`).
     *                                 Required for `TYPE_PAYMENT`; ignored otherwise
     *                                 (charge/order/extension taxability is decided
     *                                 by `$salesTaxType`/the caller, not this flag —
     *                                 matching `updateCreditBalance()` exactly, which
     *                                 only checks `getTaxStatus()` for `payment`,
     *                                 `refund`, and `discount`).
     * @param  float  $externalTaxAmount  The already-computed tax dollar
     *                                    amount, supplied by the caller. Required for
     *                                    `TYPE_ORDER` only — `updateCreditBalance()`'s
     *                                    `'order'` case never computes tax itself, it
     *                                    only adds a caller-supplied amount.
     *
     * @throws \LogicException if $transactionType is not yet implemented —
     *                         see the class docblock for exactly which types this covers
     *                         and why each deferred type is deferred.
     */
    public static function amountWithTax(
        string $transactionType,
        float $amount,
        float $taxRate = 0.0,
        ?string $salesTaxType = null,
        bool $customerTaxable = true,
        float $externalTaxAmount = 0.0,
    ): TaxBreakdown {
        return match ($transactionType) {
            // customer_accounts.php: updateCreditBalance() 'payment' case —
            // $amountWithTax is always $record->amount, regardless of
            // taxable status; only whether `sales_tax` gets recorded as the
            // rate or as 0 differs. Modeling that as "effective rate" here
            // reproduces the exact same totalAmount either way, since
            // extractTaxFromInclusiveAmount()'s totalAmount is always the
            // input amount, unaffected by the rate.
            self::TYPE_PAYMENT => TaxCalculationService::extractTaxFromInclusiveAmount(
                $amount,
                $customerTaxable ? $taxRate : 0.0,
            ),

            // updateCreditBalance() 'charge' case — three-way branch on
            // sales_tax_type, byte-identical for Manual/Fuel/Damage charges.
            self::TYPE_CHARGE => match ($salesTaxType) {
                'add' => TaxCalculationService::addTaxToExclusiveAmount($amount, $taxRate),
                'reverse' => TaxCalculationService::extractTaxFromInclusiveAmount($amount, $taxRate),
                default => TaxCalculationService::addTaxToExclusiveAmount($amount, 0.0),
            },

            // updateCreditBalance() 'order' case — tax is supplied by the
            // caller (e.g. checkout), never computed here. No rate-based
            // formula applies, so this is composed directly rather than
            // via TaxCalculationService (there is no "duplicated formula"
            // risk here: addition of two already-known values is not a tax
            // calculation to centralize).
            self::TYPE_ORDER => new TaxBreakdown(
                baseAmount: $amount,
                taxAmount: $externalTaxAmount,
                totalAmount: round($amount + $externalTaxAmount, 2),
            ),

            // Extension\StoreController's current inline formula:
            // `$validated['add_tax'] ? round($baseAmount * $salesTaxRate, 2) : 0.00`.
            // Callers migrating to this method should pass salesTaxType='add'
            // when their add_tax flag is true, and null/anything else when
            // it is false — mirroring TYPE_CHARGE's 'add' vs. default split,
            // since Extension Charge has no 'reverse' concept of its own.
            self::TYPE_EXTENSION => $salesTaxType === 'add'
                ? TaxCalculationService::addTaxToExclusiveAmount($amount, $taxRate)
                : TaxCalculationService::addTaxToExclusiveAmount($amount, 0.0),

            'refund' => throw new \LogicException(
                "LedgerBalanceService::amountWithTax() does not implement 'refund' — Pending Business Decision "
                .'(FINANCIAL_TRUTH_TABLE.md §3b row 2: the four legacy methods disagree on refund tax treatment, '
                .'and no form collects sales_tax_type for a refund). Do not guess; obtain business sign-off and '
                .'implement this branch explicitly.'
            ),

            'discount' => throw new \LogicException(
                "LedgerBalanceService::amountWithTax() does not implement 'discount' — Pending Business Decision "
                .'(FINANCIAL_TRUTH_TABLE.md §3b row 3: getAvailableCredit() ignores sales_tax_type entirely while '
                .'the other three legacy methods branch on it). Do not guess; obtain business sign-off and '
                .'implement this branch explicitly.'
            ),

            'credit', 'debit' => throw new \LogicException(
                "LedgerBalanceService::amountWithTax() does not implement '{$transactionType}' — Undefined "
                .'(FINANCIAL_TRUTH_TABLE.md §3b rows 4-5: confirmed dead code, zero existing precedent to '
                .'implement against). Define real behavior or formally deprecate the enum value before implementing.'
            ),

            'account_invoice' => throw new \LogicException(
                "LedgerBalanceService::amountWithTax() does not implement 'account_invoice' — this transaction "
                .'type never affects a balance at all (FINANCIAL_TRUTH_TABLE.md §3b row 12, confirmed directly '
                .'against Invoice\\StoreController: it never calls updateCreditBalance()). There is nothing for '
                .'this method to compute for this type.'
            ),

            default => throw new \LogicException(
                "LedgerBalanceService::amountWithTax() does not implement transaction type '{$transactionType}'. "
                .'See docs/financial-engine-consolidation/FINANCIAL_TRUTH_TABLE.md §7 for what is currently '
                .'approved, and docs/financial-engine-consolidation/PHASE_2_6_COMPLETION_REPORT.md for what '
                .'Phase 2.6 deliberately deferred.'
            ),
        };
    }

    /**
     * The database-write counterpart to {@see self::amountWithTax()}.
     *
     * TRANSACTION-SAFE. Structurally mirrors `CustomHelper::updateCreditBalance()`
     * exactly — same customer lookup, same tax-rate-setting resolution, same
     * balance computation (now delegated to `amountWithTax()` instead of
     * re-implemented inline), same persistence of both the ledger row and
     * the customer row, and the same deadlock-retry loop. Nothing about
     * *what* gets computed changes from Phase 2.6 — this method only adds
     * the *write* structure Phase 2.6 deliberately left out.
     *
     * Supports only `TYPE_PAYMENT`, `TYPE_CHARGE`, and `TYPE_ORDER` — the
     * three types that correspond to a real `customer_accounts.type` value
     * and therefore have a `CustomerAccount` row for this method to persist
     * against. `TYPE_EXTENSION` is not supported here (see the class
     * docblock) and any other type ($record->type of 'refund', 'discount',
     * 'credit', 'debit', 'account_invoice', or anything unrecognized)
     * throws immediately, before any database work begins.
     *
     * @param  CustomerAccount  $record  An unsaved or existing ledger row.
     *                                   Its `type`, `amount`, `sales_tax_type`, and
     *                                   `customer_id` must already be set, exactly
     *                                   as `CustomHelper::updateCreditBalance()`
     *                                   expects.
     * @param  float  $externalTaxAmount  Caller-supplied tax dollar amount,
     *                                    used only for `TYPE_ORDER` — see
     *                                    {@see self::amountWithTax()}'s docblock.
     *
     * @throws \LogicException if $record->type is not one of the three
     *                         types this method supports.
     */
    public static function applyTransaction(CustomerAccount $record, float $externalTaxAmount = 0.0): void
    {
        if (! in_array($record->type, [self::TYPE_PAYMENT, self::TYPE_CHARGE, self::TYPE_ORDER], true)) {
            // Fail fast, before opening a transaction or acquiring any lock —
            // this call was never going to succeed, so it should not pay for
            // (or risk contending on) database work first.
            throw new \LogicException(
                "LedgerBalanceService::applyTransaction() does not support transaction type '{$record->type}'. "
                ."Calling amountWithTax('{$record->type}', ...) directly will explain exactly why — see that "
                .'method for the specific FINANCIAL_TRUTH_TABLE.md row that blocks it. applyTransaction() itself '
                .'additionally never supports TYPE_EXTENSION, since Extension Charge has no customer_accounts row.'
            );
        }

        $maxRetries = 5;
        $attempt = 0;

        while (true) {
            try {
                DB::transaction(function () use ($record, $externalTaxAmount) {
                    $customer = Customer::findOrFail($record->customer_id);
                    $currentBalance = $customer->available_credit_balance ?? 0;

                    $salesTaxSetting = Setting::where('setting_name', 'sales_tax')->first();
                    $salesTaxRate = (float) ($salesTaxSetting?->setting_value ?? 0.0);
                    $customerTaxable = $customer->getTaxStatus() === 'Taxable';

                    $breakdown = self::amountWithTax(
                        $record->type,
                        (float) $record->amount,
                        taxRate: $salesTaxRate,
                        salesTaxType: $record->sales_tax_type,
                        customerTaxable: $customerTaxable,
                        externalTaxAmount: $externalTaxAmount,
                    );

                    // The `sales_tax` column records a RATE (e.g. 0.0887), not
                    // the dollar tax amount `amountWithTax()` returns — a
                    // distinct piece of production behavior, re-verified
                    // directly against `updateCreditBalance()`'s live source.
                    // 'order' is intentionally left alone: production never
                    // assigns this field for that type (a pre-existing no-op,
                    // preserved exactly, not "fixed").
                    match ($record->type) {
                        self::TYPE_PAYMENT => $record->sales_tax = $customerTaxable ? $salesTaxRate : 0,
                        self::TYPE_CHARGE => $record->sales_tax = in_array($record->sales_tax_type, ['add', 'reverse'], true)
                            ? $salesTaxRate
                            : 0,
                        self::TYPE_ORDER => null,
                    };

                    $direction = $record->type === self::TYPE_PAYMENT ? -1 : 1;
                    $newBalance = $currentBalance + $direction * $breakdown->totalAmount;

                    $record->balance = $newBalance;
                    $record->save();

                    $customer->available_credit_balance = $newBalance;
                    $customer->save();
                });

                break;
            } catch (QueryException $e) {
                // Deadlock error code in MySQL is 40001 — identical retry
                // policy to CustomHelper::updateCreditBalance().
                if ($e->getCode() === '40001' && ++$attempt <= $maxRetries) {
                    usleep(100000);

                    continue;
                }

                throw $e;
            }
        }
    }
}
