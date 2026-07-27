<?php

namespace App\Services\Credit;

use App\Helpers\CustomHelper;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerAccount;
use App\Services\CustomerCreditService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Canonical, READ-ONLY derivation layer for all Credit-Account-facing financial
 * values. Stage B of the Credit Account Foundation corrections: the audit
 * proved balance-adjacent facts were derived inconsistently across the app
 * (utilization 3 ways, aging 2 ways, bad-debt 4 ways). Every screen should read
 * these values from here so the same customer shows the same facts everywhere.
 *
 * Invariants:
 *  - Never mutates data, never creates ledger rows, never reconciles payments
 *    to invoices.
 *  - Outstanding balance is the canonical A/R figure (customers.available_credit_balance,
 *    the incrementally-maintained ledger result — the same value getAvailableCredit
 *    derives from), NOT re-summed here.
 *  - Account payments are derived from the canonical ACCOUNT LEDGER
 *    (customer_accounts type='payment'), never from order_payments (many of
 *    which are placeholders that never credited A/R).
 *  - Store Credit is a SEPARATE ledger; store_credit_balance is exposed but is
 *    NEVER folded into the outstanding balance, available credit, or account
 *    payments.
 *  - The field named `available_credit_balance` actually stores outstanding A/R
 *    DEBT — a documented naming defect isolated behind this layer. A physical
 *    rename is proposed separately (migration/compat risk).
 */
class CreditAccountSummary
{
    /**
     * Centralized aging-bucket day boundaries. Single source so every screen
     * agrees (the audit found Billing Summary using 4 buckets and the model
     * accessor using 3 — DISP-2).
     */
    public const AGING_BUCKETS = [
        ['label' => 'Current', 'max' => 0],
        ['label' => '1–30 days', 'max' => 30],
        ['label' => '31–45 days', 'max' => 45],
        ['label' => '46–60 days', 'max' => 60],
        ['label' => '61–90 days', 'max' => 90],
        ['label' => '91+ days', 'max' => null],
    ];

    /**
     * Centralized bad-debt aging threshold (days). The audit found FOUR
     * disagreeing definitions: aging {>45} vs {>=60} and credit-rule {OR +
     * limit<=0} vs {AND + null}. This layer standardizes on the
     * getCustomerAccountStatus semantics (>=60, AND, unapproved-with-balance)
     * because that helper already drives the most surfaces (CRM tabs + checkout
     * gate). The rule is NOT yet business-approved — see badDebt()['rule_approved'].
     */
    public const BAD_DEBT_AGING_DAYS = 60;

    public function __construct(private readonly Customer $customer)
    {
    }

    public static function for(Customer $customer): self
    {
        return new self($customer);
    }

    // ── Terms ────────────────────────────────────────────────────────────

    public function isCreditAccount(): bool
    {
        return (int) ($this->customer->is_credit_account ?? 0) === 1;
    }

    public function creditLimit(): ?float
    {
        return $this->customer->credit_limit !== null ? (float) $this->customer->credit_limit : null;
    }

    // ── Balance & available credit ─────────────────────────────────────────

    /** Outstanding A/R debt (the canonical maintained ledger figure). */
    public function outstandingBalance(): float
    {
        return round((float) ($this->customer->available_credit_balance ?? 0), 2);
    }

    /** Canonical available credit — delegated, floored at 0, gated on approval + positive limit. */
    public function availableCredit(): float
    {
        return (float) CustomHelper::getAvailableCredit($this->customer);
    }

    public function isOverLimit(): bool
    {
        $limit = $this->creditLimit();

        return $this->isCreditAccount() && $limit !== null && $limit > 0
            && $this->outstandingBalance() > $limit;
    }

    /** Signed overage retained internally (0 when not over / not applicable). */
    public function amountOverLimit(): float
    {
        return $this->isOverLimit()
            ? round($this->outstandingBalance() - (float) $this->creditLimit(), 2)
            : 0.0;
    }

    /**
     * Utilization as structured state — never a misleading percentage.
     * @return array{percentage: ?float, is_calculable: bool, reason_unavailable: ?string}
     */
    public function utilization(): array
    {
        if (! $this->isCreditAccount()) {
            return ['percentage' => null, 'is_calculable' => false, 'reason_unavailable' => 'not_credit_account'];
        }
        $limit = $this->creditLimit();
        if ($limit === null || $limit <= 0) {
            return ['percentage' => null, 'is_calculable' => false, 'reason_unavailable' => 'no_credit_limit'];
        }

        // Base definition: outstanding ÷ limit × 100. True value (may exceed 100
        // when over limit, may be <0 for a credit balance) — display bars should
        // clamp to [0,100]; the raw value stays honest here.
        return [
            'percentage' => round($this->outstandingBalance() / $limit * 100, 2),
            'is_calculable' => true,
            'reason_unavailable' => null,
        ];
    }

    /** Convenience: display utilization clamped to [0,100], or null when incalculable. */
    public function utilizationDisplayPercent(): ?float
    {
        $u = $this->utilization();

        return $u['is_calculable'] ? max(0.0, min(100.0, $u['percentage'])) : null;
    }

    // ── Store Credit (SEPARATE ledger) ──────────────────────────────────────

    public function storeCreditBalance(): float
    {
        return round(CustomerCreditService::remainingBalance((int) $this->customer->id), 2);
    }

    // ── Payments (from the canonical ACCOUNT LEDGER, not order_payments) ─────

    public function lastPayment(): ?CustomerAccount
    {
        return $this->accountLedger()
            ->where('type', 'payment')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->first();
    }

    public function paymentsDuring(CarbonInterface $start, CarbonInterface $end): Collection
    {
        return $this->accountLedger()
            ->where('type', 'payment')
            ->whereBetween('date', [$start, $end])
            ->orderBy('date')
            ->get();
    }

    public function totalPaymentsDuring(CarbonInterface $start, CarbonInterface $end): float
    {
        return round((float) $this->accountLedger()
            ->where('type', 'payment')
            ->whereBetween('date', [$start, $end])
            ->sum('amount'), 2);
    }

    // ── Aging (days since last account payment; explicit basis) ──────────────

    /**
     * Canonical aging basis = days since the last ACCOUNT payment (the existing
     * Kabba method that drives the shipped status/badge logic). When the
     * customer has never made an account payment but carries an outstanding
     * balance, ages from the oldest outstanding debit posting. Null when there
     * is no outstanding exposure. This does NOT allocate payments to invoices
     * (no reconciliation) — it is time-since-activity, stated explicitly.
     */
    public function daysSinceLastActivity(): ?int
    {
        if ($this->outstandingBalance() <= 0) {
            return null;
        }

        $last = $this->lastPayment();
        if ($last && $last->date) {
            return (int) Carbon::parse($last->date)->startOfDay()->diffInDays(Carbon::now()->startOfDay());
        }

        $oldestDebit = $this->accountLedger()
            ->whereIn('type', ['charge', 'order'])
            ->orderBy('date')
            ->orderBy('id')
            ->first();

        return $oldestDebit && $oldestDebit->date
            ? (int) Carbon::parse($oldestDebit->date)->startOfDay()->diffInDays(Carbon::now()->startOfDay())
            : null;
    }

    public function oldestOutstandingActivityDate(): ?Carbon
    {
        $oldestDebit = $this->accountLedger()
            ->whereIn('type', ['charge', 'order'])
            ->orderBy('date')
            ->orderBy('id')
            ->first();

        return $oldestDebit && $oldestDebit->date ? Carbon::parse($oldestDebit->date) : null;
    }

    public function agingBucket(): string
    {
        $days = $this->daysSinceLastActivity();
        if ($days === null) {
            return 'Current';
        }

        foreach (self::AGING_BUCKETS as $bucket) {
            if ($bucket['max'] === null || $days <= $bucket['max']) {
                return $bucket['label'];
            }
        }

        return '91+ days';
    }

    // ── Bad debt (facts vs classification kept separate; rule unapproved) ────

    /**
     * @return array{
     *   is_bad_debt: bool, rule: string, rule_approved: bool,
     *   aging_days: ?int, alternate_rule_note: string
     * }
     */
    public function badDebt(): array
    {
        $days = $this->daysSinceLastActivity();
        $unapprovedWithBalance = (! $this->isCreditAccount() || ($this->creditLimit() ?? 0) <= 0)
            && $this->outstandingBalance() > 0;

        $isBadDebt = $days !== null
            && $days >= self::BAD_DEBT_AGING_DAYS
            && $unapprovedWithBalance;

        return [
            'is_bad_debt' => $isBadDebt,
            'rule' => 'aging_days >= ' . self::BAD_DEBT_AGING_DAYS . ' AND unapproved-credit-with-balance',
            'rule_approved' => false, // audit found 4 divergent defs; awaiting business sign-off
            'aging_days' => $days,
            'alternate_rule_note' => 'Billing Summary + isBadDebitCustomer use >45 days with OR/limit<=0; '
                . 'getCustomerAccountStatus + Customers index use >=60 days with AND/null. Standardized here on '
                . 'the >=60/AND semantics; final threshold requires business approval.',
        ];
    }

    // ── Aggregate ───────────────────────────────────────────────────────────

    public function toArray(): array
    {
        return [
            'is_credit_account' => $this->isCreditAccount(),
            'credit_limit' => $this->creditLimit(),
            'outstanding_balance' => $this->outstandingBalance(),
            'available_credit' => $this->availableCredit(),
            'is_over_limit' => $this->isOverLimit(),
            'amount_over_limit' => $this->amountOverLimit(),
            'utilization' => $this->utilization(),
            'store_credit_balance' => $this->storeCreditBalance(),
            'aging_days' => $this->daysSinceLastActivity(),
            'aging_bucket' => $this->agingBucket(),
            'bad_debt' => $this->badDebt(),
        ];
    }

    private function accountLedger()
    {
        return CustomerAccount::query()->where('customer_id', $this->customer->id);
    }
}
