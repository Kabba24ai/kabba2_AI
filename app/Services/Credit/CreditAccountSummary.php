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
     * Canonical, business-APPROVED Bad Debt aging threshold (calendar days).
     *
     *     Bad Debt = outstanding_balance > 0 AND oldest_outstanding_age_days >= 60
     *
     * Credit-limit configuration is intentionally NOT part of Bad Debt — a $0,
     * negative, or null credit limit describes account configuration, not bad
     * debt. This replaced the four divergent legacy definitions (aging >45 vs
     * >=60; credit-rule OR/limit<=0 vs AND/null). Over-limit and past-due are
     * separate facts (isOverLimit()/amountOverLimit(); agingBucket()).
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

    // ── Oldest outstanding exposure age (the Bad Debt aging basis) ───────────

    /**
     * Age (calendar days) of the OLDEST outstanding account exposure — the
     * canonical basis for Bad Debt. Without invoice/FIFO reconciliation (out of
     * scope), "oldest outstanding exposure" is proxied by the oldest account
     * DEBIT posting (charge/order) while the account carries a positive balance
     * — the Phase 2A methodology, preserved and documented. Null when there is
     * no positive outstanding balance or no debit posting.
     */
    public function oldestOutstandingAgeDays(): ?int
    {
        if ($this->outstandingBalance() <= 0) {
            return null;
        }

        $date = $this->oldestOutstandingActivityDate();

        return $date
            ? (int) $date->copy()->startOfDay()->diffInDays(Carbon::now()->startOfDay())
            : null;
    }

    // ── Bad debt (canonical, business-approved; separate from over-limit) ────

    /**
     * Canonical Bad Debt classification — the single authority for the whole
     * app. Credit-limit configuration is NOT consulted.
     *
     *     is_bad_debt = outstanding_balance > 0 AND oldest_outstanding_age_days >= 60
     *
     * @return array{
     *   is_bad_debt: bool, rule_approved: bool, threshold_days: int,
     *   oldest_outstanding_age_days: ?int, outstanding_balance: float
     * }
     */
    public function badDebt(): array
    {
        $balance = $this->outstandingBalance();
        $age = $this->oldestOutstandingAgeDays();

        return [
            'is_bad_debt' => $balance > 0 && $age !== null && $age >= self::BAD_DEBT_AGING_DAYS,
            'rule_approved' => true,
            'threshold_days' => self::BAD_DEBT_AGING_DAYS,
            'oldest_outstanding_age_days' => $age,
            'outstanding_balance' => $balance,
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
            'oldest_outstanding_age_days' => $this->oldestOutstandingAgeDays(),
            'bad_debt' => $this->badDebt(),
        ];
    }

    private function accountLedger()
    {
        return CustomerAccount::query()->where('customer_id', $this->customer->id);
    }
}
