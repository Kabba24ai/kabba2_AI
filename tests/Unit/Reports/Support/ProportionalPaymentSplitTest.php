<?php

namespace Tests\Unit\Reports\Support;

use App\Services\Reports\Support\ProportionalPaymentSplit;
use PHPUnit\Framework\TestCase;

/**
 * Pure (DB-free) coverage for the cash-basis per-payment tax/discount split
 * shared by every financial report's collected-revenue side.
 *
 * Signature: split(orderTax, orderDiscount, orderTotal (grand_total), amounts[]).
 */
class ProportionalPaymentSplitTest extends TestCase
{
    public function test_single_full_payment_receives_the_full_order_tax_and_discount(): void
    {
        // $1000 + $100 tax, grand_total $1100, paid in one $1100 payment.
        $split = ProportionalPaymentSplit::split(100.0, 0.0, 1100.0, [1100.0]);

        $this->assertCount(1, $split);
        $this->assertSame(100.0, $split[0]['tax']);
    }

    public function test_two_payments_split_tax_proportionally_by_the_canonical_total(): void
    {
        // Denominator is the order total (1100), NOT the sum of these payments.
        $split = ProportionalPaymentSplit::split(100.0, 0.0, 1100.0, [700.0, 400.0]);

        $this->assertSame(63.64, $split[0]['tax']);   // 700/1100 * 100
        $this->assertSame(36.36, $split[1]['tax']);   // completing payment absorbs the remainder
    }

    public function test_temporal_stability_a_later_payment_does_not_restate_an_earlier_one(): void
    {
        // June alone: only the $700 payment exists yet.
        $juneOnly = ProportionalPaymentSplit::split(100.0, 0.0, 1100.0, [700.0]);
        $this->assertSame(63.64, $juneOnly[0]['tax'], 'partial $700 of a $1100 order gets 63.64, NOT the full 100');

        // July arrives: the $700 allocation must be byte-for-byte identical.
        $both = ProportionalPaymentSplit::split(100.0, 0.0, 1100.0, [700.0, 400.0]);
        $this->assertSame($juneOnly[0]['tax'], $both[0]['tax'], 'recording July must not change June');
        $this->assertSame(36.36, $both[1]['tax']);
        $this->assertSame(100.0, round($both[0]['tax'] + $both[1]['tax'], 2), 'lifetime tax = order tax');
    }

    public function test_temporal_stability_holds_for_discounts_too(): void
    {
        // $1000 + $100 tax − $50 discount → grand_total $1050. $700 then $350.
        $firstOnly = ProportionalPaymentSplit::split(100.0, 50.0, 1050.0, [700.0]);
        $both      = ProportionalPaymentSplit::split(100.0, 50.0, 1050.0, [700.0, 350.0]);

        $this->assertSame($firstOnly[0]['discount'], $both[0]['discount'], 'recording the later payment must not restate the first discount');
        $this->assertSame(50.0, round($both[0]['discount'] + $both[1]['discount'], 2), 'lifetime discount = order discount');
        $this->assertSame(100.0, round($both[0]['tax'] + $both[1]['tax'], 2), 'lifetime tax = order tax');
    }

    public function test_overpayment_never_allocates_more_than_the_order_stored_tax(): void
    {
        // $1200 collected on a $1100 order (100 tax). Proportional would be
        // 100 * 1200/1100 = 109.09; it must be capped at the order's 100.
        $split = ProportionalPaymentSplit::split(100.0, 0.0, 1100.0, [1200.0]);

        $this->assertSame(100.0, $split[0]['tax']);
    }

    public function test_overpayment_across_two_payments_caps_at_order_totals(): void
    {
        // $700 then $600 = $1300 on a $1100 order.
        $split = ProportionalPaymentSplit::split(100.0, 0.0, 1100.0, [700.0, 600.0]);

        $this->assertSame(63.64, $split[0]['tax']);
        $this->assertSame(36.36, $split[1]['tax'], 'the completing payment absorbs only what remains, not its raw proportion');
        $this->assertSame(100.0, round($split[0]['tax'] + $split[1]['tax'], 2));
    }

    public function test_partial_only_order_collects_only_its_proportional_share_of_tax(): void
    {
        // $300 of an $1100 order, never completed → 300/1100 * 100 = 27.27,
        // not the full 100 (the uncollected tax stays uncollected).
        $split = ProportionalPaymentSplit::split(100.0, 0.0, 1100.0, [300.0]);

        $this->assertSame(27.27, $split[0]['tax']);
    }

    public function test_three_way_split_assigns_the_rounding_remainder_to_the_completing_payment(): void
    {
        // $10.00 tax on a $300 grand_total, three equal $100 payments.
        $split = ProportionalPaymentSplit::split(10.0, 0.0, 300.0, [100.0, 100.0, 100.0]);

        $this->assertSame(3.33, $split[0]['tax']);
        $this->assertSame(3.33, $split[1]['tax']);
        $this->assertSame(3.34, $split[2]['tax']);   // completer absorbs the remainder
        $this->assertSame(10.0, round($split[0]['tax'] + $split[1]['tax'] + $split[2]['tax'], 2));
    }

    public function test_empty_payment_set_returns_no_rows(): void
    {
        $this->assertSame([], ProportionalPaymentSplit::split(100.0, 0.0, 1100.0, []));
    }

    // ── allocate() — the single-figure core every surface distributes through ──

    public function test_allocate_is_the_same_arithmetic_split_composes(): void
    {
        $tax = ProportionalPaymentSplit::allocate(100.0, 1100.0, [700.0, 400.0]);
        $split = ProportionalPaymentSplit::split(100.0, 0.0, 1100.0, [700.0, 400.0]);

        $this->assertSame([$split[0]['tax'], $split[1]['tax']], $tax);
    }

    public function test_allocate_is_temporally_stable(): void
    {
        $firstAlone = ProportionalPaymentSplit::allocate(55.0, 1100.0, [700.0]);
        $both       = ProportionalPaymentSplit::allocate(55.0, 1100.0, [700.0, 400.0]);

        $this->assertSame($firstAlone[0], $both[0]);
        $this->assertSame(55.0, round($both[0] + $both[1], 2));
    }

    // ── applied() — overpayment detection ──────────────────────────────────────

    public function test_applied_caps_at_the_canonical_order_total(): void
    {
        // $700 then $600 = $1300 on a $1100 order: the second payment applies
        // only $400; the extra $200 is overpayment.
        $applied = ProportionalPaymentSplit::applied(1100.0, [700.0, 600.0]);

        $this->assertSame(700.0, $applied[0]['applied']);
        $this->assertSame(0.0, $applied[0]['overpayment']);
        $this->assertSame(400.0, $applied[1]['applied']);
        $this->assertSame(200.0, $applied[1]['overpayment']);
    }

    public function test_applied_single_overpayment(): void
    {
        $applied = ProportionalPaymentSplit::applied(1100.0, [1200.0]);

        $this->assertSame(1100.0, $applied[0]['applied']);
        $this->assertSame(100.0, $applied[0]['overpayment']);
    }

    public function test_applied_is_temporally_stable(): void
    {
        $firstAlone = ProportionalPaymentSplit::applied(1100.0, [700.0]);
        $both       = ProportionalPaymentSplit::applied(1100.0, [700.0, 400.0]);

        $this->assertSame($firstAlone[0], $both[0], 'a later payment must not change an earlier applied amount');
    }

    // ── distribute() — line-attribution partitioning of one payment figure ─────

    public function test_distribute_partitions_by_weight_and_sums_exactly_to_the_target(): void
    {
        // A payment's $30 tax across lines weighted [60, 40] (their line taxes).
        $parts = ProportionalPaymentSplit::distribute(30.0, [60.0, 40.0]);

        $this->assertSame([18.0, 12.0], $parts);
        $this->assertSame(30.0, round(array_sum($parts), 2));
    }

    public function test_distribute_gives_zero_weight_lines_nothing(): void
    {
        // Mixed taxable / non-taxable: the non-taxable line (weight 0) must
        // receive NO tax; the taxable lines carry it all.
        $parts = ProportionalPaymentSplit::distribute(30.0, [60.0, 0.0, 40.0]);

        $this->assertSame(0.0, $parts[1]);
        $this->assertSame(30.0, round(array_sum($parts), 2));
    }

    public function test_distribute_remainder_goes_to_the_last_nonzero_weight_line_deterministically(): void
    {
        // $10.00 across three equal weights: 3.33 / 3.33 / 3.34 — and the
        // remainder line is the LAST NONZERO weight even when a zero-weight
        // line sits after it.
        $equal = ProportionalPaymentSplit::distribute(10.0, [100.0, 100.0, 100.0]);
        $this->assertSame([3.33, 3.33, 3.34], $equal);

        $trailingZero = ProportionalPaymentSplit::distribute(10.0, [100.0, 100.0, 100.0, 0.0]);
        $this->assertSame([3.33, 3.33, 3.34, 0.0], $trailingZero);

        // Determinism: identical inputs always yield identical outputs.
        $this->assertSame($equal, ProportionalPaymentSplit::distribute(10.0, [100.0, 100.0, 100.0]));
    }

    public function test_distribute_degenerate_all_zero_weights_keeps_additivity(): void
    {
        // Inconsistent data (target with no weight anywhere): the last line
        // takes it all — Σ parts == target must never break.
        $parts = ProportionalPaymentSplit::distribute(5.0, [0.0, 0.0]);

        $this->assertSame([0.0, 5.0], $parts);
    }

    public function test_distribute_empty_lines_returns_empty(): void
    {
        $this->assertSame([], ProportionalPaymentSplit::distribute(5.0, []));
    }
}
