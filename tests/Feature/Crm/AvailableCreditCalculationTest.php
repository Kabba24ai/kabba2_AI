<?php

namespace Tests\Feature\Crm;

use App\Helpers\CustomHelper;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Available-Credit Consistency: available credit is derived from the ONE
 * canonical outstanding balance —
 *
 *     Available Credit = max(0, credit_limit − available_credit_balance)
 *
 * — so an existing A/R balance immediately consumes part of a newly-approved
 * limit, the value recalculates whenever the balance or limit changes, and an
 * over-limit account clamps to $0 while its real outstanding stays visible.
 * getAvailableCredit() no longer re-sums the ledger (the divergent second
 * source that returned $0 / fully-used after approval).
 */
class AvailableCreditCalculationTest extends TestCase
{
    use RefreshDatabase;

    private function customer(array $overrides = []): Customer
    {
        // available_credit_balance is NOT mass-assignable (production sets it
        // via direct assignment in updateCreditBalance) — forceFill it here.
        $balance = $overrides['available_credit_balance'] ?? 0;
        unset($overrides['available_credit_balance']);

        $c = Customer::create(array_merge([
            'first_name' => 'Acme', 'last_name' => 'Construction',
            'email' => 'credit-' . uniqid() . '@example.com', 'status' => 'Active',
            'is_credit_account' => 1, 'credit_limit' => 10000,
        ], $overrides));

        $c->forceFill(['available_credit_balance' => $balance])->saveQuietly();

        return $c;
    }

    private function setBalance(Customer $c, float $balance): void
    {
        $c->forceFill(['available_credit_balance' => $balance])->saveQuietly();
    }

    private function available(Customer $c): float
    {
        return (float) CustomHelper::getAvailableCredit($c->fresh());
    }

    // ── The 6 mission scenarios ─────────────────────────────────────────

    public function test_existing_balance_before_authorization_consumes_the_limit(): void
    {
        // The production defect: prior A/R balance, then approved for $10k.
        $c = $this->customer(['available_credit_balance' => 1129.48]);

        $this->assertSame(8870.52, $this->available($c));
    }

    public function test_no_existing_balance_gives_the_full_limit(): void
    {
        $c = $this->customer(['available_credit_balance' => 0]);

        $this->assertSame(10000.0, $this->available($c));
    }

    public function test_available_decreases_as_balance_increases(): void
    {
        $c = $this->customer(['available_credit_balance' => 1000]);
        $this->assertSame(9000.0, $this->available($c));

        $this->setBalance($c, 2500);
        $this->assertSame(7500.0, $this->available($c));
    }

    public function test_available_increases_when_a_payment_reduces_balance(): void
    {
        $c = $this->customer(['available_credit_balance' => 2500]);
        $this->assertSame(7500.0, $this->available($c));

        $this->setBalance($c, 1500);
        $this->assertSame(8500.0, $this->available($c));
    }

    public function test_available_recalculates_immediately_on_credit_limit_change(): void
    {
        $c = $this->customer(['available_credit_balance' => 1500, 'credit_limit' => 10000]);
        $this->assertSame(8500.0, $this->available($c));

        $c->update(['credit_limit' => 6000]);
        $this->assertSame(4500.0, $this->available($c));
    }

    public function test_over_limit_clamps_available_to_zero_and_preserves_outstanding(): void
    {
        $c = $this->customer(['available_credit_balance' => 12000, 'credit_limit' => 10000]);

        $this->assertSame(0.0, $this->available($c), 'available credit clamps at 0');
        $this->assertSame(12000.0, (float) $c->fresh()->available_credit_balance, 'true outstanding preserved');
    }

    public function test_non_authorized_customer_has_no_available_credit(): void
    {
        $this->assertSame(0, CustomHelper::getAvailableCredit(
            $this->customer(['is_credit_account' => 0, 'available_credit_balance' => 500])
        ));
        $this->assertSame(0, CustomHelper::getAvailableCredit(
            $this->customer(['credit_limit' => 0, 'available_credit_balance' => 500])
        ));
    }

    // ── Decoupled recompute / repair ────────────────────────────────────

    public function test_recompute_and_fix_derive_outstanding_from_the_ledger(): void
    {
        $c = $this->customer(['available_credit_balance' => 0]); // deliberately stale
        $this->ledger($c, 'charge', 2000);
        $this->ledger($c, 'order', 500);
        $this->ledger($c, 'payment', 1370.52);
        // Outstanding = 2000 + 500 − 1370.52 = 1129.48

        $this->assertSame(1129.48, CustomHelper::recomputeOutstandingBalance($c->id), 'read-only recompute');

        CustomHelper::fixTheRunningBalance($c->id);
        $this->assertSame(1129.48, (float) $c->fresh()->available_credit_balance, 'persisted');
        // And available credit derives from the repaired balance.
        $this->assertSame(8870.52, $this->available($c));
    }

    public function test_fix_running_balance_is_idempotent(): void
    {
        $c = $this->customer(['available_credit_balance' => 999]);
        $this->ledger($c, 'charge', 300);
        $this->ledger($c, 'payment', 100);

        CustomHelper::fixTheRunningBalance($c->id);
        $first = (float) $c->fresh()->available_credit_balance;
        CustomHelper::fixTheRunningBalance($c->id);
        $second = (float) $c->fresh()->available_credit_balance;

        $this->assertSame(200.0, $first);
        $this->assertSame($first, $second, 'idempotent');
    }

    public function test_repair_command_dry_run_reports_without_writing_then_fix_persists(): void
    {
        $c = $this->customer(['available_credit_balance' => 0]); // stale
        $this->ledger($c, 'charge', 400);

        $this->artisan('credit:repair-balances', ['--customer' => $c->id])
            ->assertExitCode(0);
        $this->assertSame(0.0, (float) $c->fresh()->available_credit_balance, 'dry-run wrote nothing');

        $this->artisan('credit:repair-balances', ['--customer' => $c->id, '--fix' => true])
            ->assertExitCode(0);
        $this->assertSame(400.0, (float) $c->fresh()->available_credit_balance, '--fix persisted');
    }

    // ── Regression: ledger direction / updateCreditBalance unchanged ────

    public function test_charge_still_increases_and_payment_decreases_the_balance(): void
    {
        $c = $this->customer(['available_credit_balance' => 0]);

        $charge = $this->ledger($c, 'charge', 250, save: false);
        CustomHelper::updateCreditBalance($charge);
        $this->assertSame(250.0, (float) $c->fresh()->available_credit_balance, 'charge increases debt');

        $payment = $this->ledger($c, 'payment', 100, save: false);
        CustomHelper::updateCreditBalance($payment);
        $this->assertSame(150.0, (float) $c->fresh()->available_credit_balance, 'payment reduces debt');

        // Available credit tracks the canonical balance throughout.
        $this->assertSame(9850.0, $this->available($c));
    }

    private function ledger(Customer $c, string $type, float $amount, bool $save = true): CustomerAccount
    {
        $row = new CustomerAccount();
        $row->customer_id    = $c->id;
        $row->amount         = $amount;
        $row->type           = $type;
        $row->reason         = ucfirst($type);
        $row->date           = now();
        $row->sales_tax      = 0;
        $row->sales_tax_type = 'free';
        if ($save) {
            $row->save();
        }

        return $row;
    }
}
