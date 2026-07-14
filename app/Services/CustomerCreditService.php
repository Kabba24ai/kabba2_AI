<?php

namespace App\Services;

use App\Models\Customers\Customer;
use App\Models\Customers\CustomerCredit;
use App\Models\Iam\Personnel\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Financial Engine — Customer Credit Platform, Phase 3.0 foundation
 * (extended in Phase 3.1 with `effective_date`/`internal_comments` support
 * on `createFinancialCredit()`, for the internal administration UI's Grant
 * Credit dialog — both optional, additive parameters; no existing behavior
 * changed; extended again in Phase 3.2 with an optional `$orderId` on both
 * `createFinancialCredit()` and `redeem()`, plus `appliedToOrder()`, so
 * Order Entry can apply/remove credit against a specific order and show
 * how much is currently applied — also additive, no existing behavior
 * changed).
 *
 * CustomerCreditService manages customer ASSETS — Financial Store Credit a
 * customer owns, independent of any specific order or invoice.
 * {@see LedgerBalanceService} manages customer DEBT — running balance owed
 * on the charge-account ledger. These are deliberately separate mechanisms
 * with separate storage (`customer_credits` here, `customer_accounts`
 * there); this class does not call `LedgerBalanceService`, `CustomHelper`,
 * or `TaxCalculationService`, and nothing in those classes calls this one.
 * See docs/financial-engine-consolidation/PHASE_3_0_PRE_IMPLEMENTATION_CHECKLIST.md
 * for the full reasoning behind that boundary.
 *
 * Per docs/financial-engine-consolidation/CUSTOMER_CREDIT_ARCHITECTURE.md,
 * this service is the ONLY component authorized to create a `customer_credits`
 * row — a future `PromotionalCreditService` or `GiftCardService` would call
 * into this class rather than writing that table directly, the same
 * "who decides vs. who writes" separation this initiative maintains
 * everywhere else.
 *
 * IMPLEMENTED in Phase 3.0 (Financial Credit only):
 *   - createFinancialCredit() — grant a customer purchasing power.
 *   - redeem() — spend it, validated against the current balance.
 *   - remainingBalance() — always computed live from the ledger, never a
 *     separately cached total (avoiding the exact kind of drift
 *     PHASE_2_5A_LEDGER_READINESS_REVIEW.md found between four independent
 *     balance-computation methods on the debt side).
 *   - history() — the full grant/redemption ledger for a customer, which
 *     doubles as its own audit trail (no separate audit table needed —
 *     the same shape `customer_accounts` already uses).
 *   - canRedeem() — a non-mutating pre-check for a redemption amount.
 *   - Idempotency — an optional, unique key preventing a retried request
 *     from creating a duplicate grant or redemption.
 *
 * ADDED in Phase 3.2 (Order Entry Integration — manual application only):
 *   - `$orderId` on createFinancialCredit()/redeem() — ties a grant or
 *     redemption to a specific order.
 *   - appliedToOrder() — net credit currently applied to a specific order.
 *   "Remove Applied Credit" is an offsetting createFinancialCredit() call,
 *   never a mutation of the original redemption row.
 *
 * NOT implemented (explicitly deferred, per each phase's own mission — do
 * not build these without a new, explicit mission):
 *   - Promotional Credit (no `credit_category`/source column exists yet —
 *     every row this service creates today is implicitly Financial Credit).
 *   - Gift Cards.
 *   - Expiration — no `expires_at` column, no expiration logic, no
 *     scheduled job. When a future phase adds expiration, it slots in at
 *     two points this class already isolates cleanly: (a) grant creation
 *     would set an expiration value, and (b) `remainingBalance()`/`canRedeem()`
 *     would need to exclude expired grants from the available total. Both
 *     are contained changes to this file, not a restructuring of it.
 *   - Automatic credit application, the Customer Resolution Wizard,
 *     marketing/campaigns, customer-portal screens.
 *   - Any integration with `customer_accounts.type = 'credit'`/`'debit'` —
 *     those enum values remain exactly as undefined as
 *     FINANCIAL_TRUTH_TABLE.md §3b rows 4-5 already document; this service
 *     does not touch them.
 */
class CustomerCreditService
{
    public const TYPE_GRANT = 'grant';

    public const TYPE_REDEMPTION = 'redemption';

    /**
     * Grant a customer Financial Store Credit.
     *
     * @param  float  $amount  Must be strictly positive.
     * @param  string  $reason  E.g. "Overpayment — Invoice #1042", "Manager Goodwill".
     * @param  int|null  $responsibleUserId  Who authorized the grant, if known.
     * @param  string|null  $idempotencyKey  If provided and a non-deleted grant or
     *                                       redemption already exists with this exact key, that
     *                                       existing row is returned unchanged and no new row is
     *                                       created — this is the duplicate-protection mechanism.
     * @param  string|null  $effectiveDate  Phase 3.1 addition, for the admin Grant Credit dialog.
     *                                      Purely informational/display — does NOT backdate
     *                                      `created_at` or change this grant's position in
     *                                      `history()`'s chronological order, which remains
     *                                      based on when the grant was actually recorded. A
     *                                      genuinely backdated ledger is a business-policy
     *                                      question this phase does not decide.
     * @param  string|null  $internalComments  Phase 3.1 addition — staff-only notes, distinct
     *                                         from `$notes` (which may be customer-visible in a
     *                                         future screen; this phase treats both as internal,
     *                                         since nothing customer-facing exists yet).
     * @param  int|null  $orderId  Phase 3.2 addition. Set when this grant is an offsetting
     *                             reversal of credit previously applied to a specific order
     *                             (Order Entry's "Remove Applied Credit" action) — never
     *                             mutates or deletes the original redemption row, per this
     *                             initiative's immutable-ledger discipline.
     *
     * @throws \InvalidArgumentException if $amount is not strictly positive.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException if the customer does not exist.
     */
    public static function createFinancialCredit(
        int $customerId,
        float $amount,
        string $reason,
        ?int $responsibleUserId = null,
        ?string $idempotencyKey = null,
        ?string $effectiveDate = null,
        ?string $internalComments = null,
        ?int $orderId = null,
    ): CustomerCredit {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('CustomerCreditService::createFinancialCredit() requires a strictly positive amount.');
        }

        if ($idempotencyKey !== null) {
            $existing = CustomerCredit::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing;
            }
        }

        // Confirms the customer exists before any write — same guarding
        // pattern LedgerBalanceService::applyTransaction() uses via
        // Customer::findOrFail(), reused here only as a pattern, not a call
        // into that class.
        Customer::findOrFail($customerId);

        return CustomerCredit::create([
            'customer_id' => $customerId,
            'order_id' => $orderId,
            'type' => self::TYPE_GRANT,
            'amount' => round($amount, 2),
            'reason' => $reason,
            'effective_date' => $effectiveDate,
            'idempotency_key' => $idempotencyKey,
            'responsible_person_id' => $responsibleUserId,
            'responsible_person_name' => $responsibleUserId ? optional(User::find($responsibleUserId))->full_name : null,
            'internal_comments' => $internalComments,
        ]);
    }

    /**
     * Redeem (spend) Financial Store Credit against the customer's balance.
     *
     * Wrapped in a database transaction with a row lock on the customer
     * record, so two concurrent redemption requests for the same customer
     * cannot both pass the balance check before either writes — the same
     * class of race condition `CustomHelper::updateCreditBalance()`'s
     * deadlock-retry loop exists to handle on the debt side, addressed here
     * with pessimistic locking instead, since a single-row insert (unlike
     * that method's dual-table write) does not need retry-after-deadlock
     * machinery to stay simple and correct.
     *
     * @param  float  $amount  Must be strictly positive and must not exceed the current balance.
     * @param  string  $reason  E.g. "Applied to Order #4471".
     * @param  string|null  $idempotencyKey  Same duplicate-protection semantics as createFinancialCredit().
     * @param  int|null  $orderId  Phase 3.2 addition — set when this redemption is Order Entry
     *                             applying credit against a specific order.
     *
     * @throws \InvalidArgumentException if $amount is not strictly positive.
     * @throws \RuntimeException if $amount exceeds the customer's current available balance.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException if the customer does not exist.
     */
    public static function redeem(
        int $customerId,
        float $amount,
        string $reason,
        ?int $responsibleUserId = null,
        ?string $idempotencyKey = null,
        ?int $orderId = null,
    ): CustomerCredit {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('CustomerCreditService::redeem() requires a strictly positive amount.');
        }

        if ($idempotencyKey !== null) {
            $existing = CustomerCredit::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing;
            }
        }

        return DB::transaction(function () use ($customerId, $amount, $reason, $responsibleUserId, $idempotencyKey, $orderId) {
            // Lock the customer row so a concurrent redemption for the same
            // customer must wait for this transaction to commit before it
            // can compute its own balance check.
            $customer = Customer::where('id', $customerId)->lockForUpdate()->firstOrFail();

            $available = self::remainingBalance($customer->id);

            if ($amount > $available) {
                throw new \RuntimeException(
                    "CustomerCreditService::redeem() rejected: requested {$amount} exceeds available balance {$available} "
                    ."for customer {$customer->id}. No row was created."
                );
            }

            return CustomerCredit::create([
                'customer_id' => $customer->id,
                'order_id' => $orderId,
                'type' => self::TYPE_REDEMPTION,
                'amount' => round($amount, 2),
                'reason' => $reason,
                'idempotency_key' => $idempotencyKey,
                'responsible_person_id' => $responsibleUserId,
                'responsible_person_name' => $responsibleUserId ? optional(User::find($responsibleUserId))->full_name : null,
            ]);
        });
    }

    /**
     * Whether a redemption of $amount is currently valid for this customer,
     * without mutating anything. A pure pre-check — the same validation
     * redeem() itself performs, exposed for callers that want to disable a
     * control or show a message before attempting a real redemption.
     */
    public static function canRedeem(int $customerId, float $amount): bool
    {
        if ($amount <= 0) {
            return false;
        }

        return $amount <= self::remainingBalance($customerId);
    }

    /**
     * The customer's current Financial Store Credit balance — always
     * computed live as (sum of grants) minus (sum of redemptions), never
     * read from a separately maintained running total. This is the same
     * "one source of truth" discipline enforced everywhere else in the
     * Financial Engine.
     */
    public static function remainingBalance(int $customerId): float
    {
        $granted = CustomerCredit::where('customer_id', $customerId)
            ->where('type', self::TYPE_GRANT)
            ->sum('amount');

        $redeemed = CustomerCredit::where('customer_id', $customerId)
            ->where('type', self::TYPE_REDEMPTION)
            ->sum('amount');

        return round((float) $granted - (float) $redeemed, 2);
    }

    /**
     * The full grant/redemption history for a customer, oldest first — this
     * doubles as the audit trail; there is no separate audit log to query.
     */
    public static function history(int $customerId): Collection
    {
        return CustomerCredit::where('customer_id', $customerId)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * Phase 3.2 — Order Entry Integration.
     *
     * The three summary figures Order Entry's Customer Credit panel needs
     * (mirrors the figures the Phase 3.1 administration tab already shows),
     * consolidated here so callers query only this service, never
     * `CustomerCredit` directly.
     */
    public static function summaryForCustomer(int $customerId): array
    {
        return [
            'balance' => self::remainingBalance($customerId),
            'lifetime_granted' => (float) CustomerCredit::where('customer_id', $customerId)->where('type', self::TYPE_GRANT)->sum('amount'),
            'lifetime_redeemed' => (float) CustomerCredit::where('customer_id', $customerId)->where('type', self::TYPE_REDEMPTION)->sum('amount'),
        ];
    }

    /**
     * Phase 3.2 — Order Entry Integration.
     *
     * Net Financial Store Credit currently applied to a specific order:
     * (redemptions recorded against this order) minus (offsetting grants
     * recorded against this order, i.e. "Remove Applied Credit" reversals).
     * Always computed live from the ledger, the same discipline
     * {@see remainingBalance()} follows — never a separately stored total.
     */
    public static function appliedToOrder(int $orderId): float
    {
        $redeemed = CustomerCredit::where('order_id', $orderId)
            ->where('type', self::TYPE_REDEMPTION)
            ->sum('amount');

        $reversed = CustomerCredit::where('order_id', $orderId)
            ->where('type', self::TYPE_GRANT)
            ->sum('amount');

        return round((float) $redeemed - (float) $reversed, 2);
    }

    /**
     * Store Credit transparency (Phase 2 payment experience): the
     * customer's balance immediately before a specific ledger row, so a
     * past redemption can show its true "Beginning Credit" even after
     * later grants/redemptions have moved the live balance. Same sum
     * pattern as remainingBalance(), scoped to rows strictly before this
     * one — ties broken by id, matching history()'s ordering.
     *
     * Soft-deleted grants/redemptions are excluded here exactly as they
     * are from remainingBalance() (Eloquent's default global scope) — a
     * later-deleted row disappears from both the live and the historical
     * view, there is no way to reconstruct "as it truly was including
     * since-deleted rows" without withTrashed().
     */
    public static function balanceBefore(CustomerCredit $entry): float
    {
        $granted = CustomerCredit::where('customer_id', $entry->customer_id)
            ->where('type', self::TYPE_GRANT)
            ->where(fn ($q) => $q->where('created_at', '<', $entry->created_at)
                ->orWhere(fn ($q2) => $q2->where('created_at', $entry->created_at)->where('id', '<', $entry->id)))
            ->sum('amount');

        $redeemed = CustomerCredit::where('customer_id', $entry->customer_id)
            ->where('type', self::TYPE_REDEMPTION)
            ->where(fn ($q) => $q->where('created_at', '<', $entry->created_at)
                ->orWhere(fn ($q2) => $q2->where('created_at', $entry->created_at)->where('id', '<', $entry->id)))
            ->sum('amount');

        return round((float) $granted - (float) $redeemed, 2);
    }
}
