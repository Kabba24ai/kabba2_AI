<?php

namespace Tests\Unit\Services;

use App\Services\CustomerCreditService;
use PHPUnit\Framework\TestCase;

/**
 * These tests cover only the parts of CustomerCreditService that execute
 * before any database access — every method validates its amount argument
 * as its very first statement, before the idempotency check or any Eloquent
 * call. That ordering is what makes these genuine, framework-free unit
 * tests possible at all.
 *
 * Every database-dependent behavior (actual grant/redemption persistence,
 * balance computation, history ordering, idempotent duplicate return,
 * insufficient-balance rejection against real data) is validated instead
 * via a live, rolled-back-transaction run against the local database,
 * following the same methodology established in Phases 2.4/2.6A/2.7/2.7A —
 * see docs/financial-engine-consolidation/PHASE_3_0_COMPLETION_REPORT.md for
 * that methodology and its results. This split, and the reason for it
 * (this project's standard RefreshDatabase test harness cannot run cleanly
 * here — the full migration set includes MySQL-only migrations incompatible
 * with SQLite), is documented honestly rather than silently worked around.
 */
class CustomerCreditServiceTest extends TestCase
{
    public function test_create_financial_credit_rejects_zero_amount_before_touching_the_database(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CustomerCreditService::createFinancialCredit(1, 0.0, 'test');
    }

    public function test_create_financial_credit_rejects_negative_amount_before_touching_the_database(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CustomerCreditService::createFinancialCredit(1, -25.00, 'test');
    }

    public function test_redeem_rejects_zero_amount_before_touching_the_database(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CustomerCreditService::redeem(1, 0.0, 'test');
    }

    public function test_redeem_rejects_negative_amount_before_touching_the_database(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CustomerCreditService::redeem(1, -10.00, 'test');
    }

    public function test_can_redeem_returns_false_for_zero_amount_without_touching_the_database(): void
    {
        $this->assertFalse(CustomerCreditService::canRedeem(1, 0.0));
    }

    public function test_can_redeem_returns_false_for_negative_amount_without_touching_the_database(): void
    {
        $this->assertFalse(CustomerCreditService::canRedeem(1, -5.00));
    }
}
