<?php

namespace Tests\Feature\Credit;

use App\Models\Customers\Customer;
use App\Models\Customers\CustomerAccount;
use App\Models\Customers\CustomerCredit;
use App\Services\Credit\CreditAccountSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Stage B — canonical read model. Proves the single derivation layer returns
 * consistent, honest values (structured utilization, over-limit retained,
 * Store Credit kept separate, payments read from the account ledger).
 */
class CreditAccountSummaryTest extends TestCase
{
    use RefreshDatabase;

    private function customer(array $overrides = []): Customer
    {
        // available_credit_balance is writer-maintained (not mass-assignable) —
        // set it explicitly via forceFill so the read-model tests can pin it.
        $balance = $overrides['available_credit_balance'] ?? 0;
        unset($overrides['available_credit_balance']);

        $c = Customer::create(array_merge([
            'first_name' => 'Sum', 'last_name' => 'Mary',
            'email' => 'sum-' . uniqid() . '@test.local', 'status' => 'Active',
        ], $overrides));
        $c->forceFill(['available_credit_balance' => $balance])->save();

        return $c->fresh();
    }

    private function ledgerPayment(Customer $c, float $amount, $date): void
    {
        $this->ledgerRow($c, $amount, 'payment', $date);
    }

    private function ledgerDebit(Customer $c, float $amount, $date): void
    {
        $this->ledgerRow($c, $amount, 'charge', $date);
    }

    private function ledgerRow(Customer $c, float $amount, string $type, $date): void
    {
        $row = new CustomerAccount();
        $row->customer_id = $c->id;
        $row->amount = $amount;
        $row->type = $type;
        $row->sales_tax = 0;
        $row->sales_tax_type = 'free';
        $row->date = $date;
        $row->save();
    }

    public function test_balance_available_and_over_limit(): void
    {
        $c = $this->customer(['is_credit_account' => 1, 'credit_limit' => 1000, 'available_credit_balance' => 1200]);
        $s = CreditAccountSummary::for($c);

        $this->assertEquals(1200, $s->outstandingBalance());
        $this->assertEquals(0, $s->availableCredit(), 'floored at 0 when over limit');
        $this->assertTrue($s->isOverLimit());
        $this->assertEquals(200, $s->amountOverLimit());
        $this->assertEquals(120.0, $s->utilization()['percentage']);
        $this->assertEquals(100.0, $s->utilizationDisplayPercent(), 'display clamps to 100');
    }

    public function test_available_credit_when_under_limit(): void
    {
        $c = $this->customer(['is_credit_account' => 1, 'credit_limit' => 1000, 'available_credit_balance' => 300]);
        $s = CreditAccountSummary::for($c);

        $this->assertEquals(700, $s->availableCredit());
        $this->assertFalse($s->isOverLimit());
        $this->assertEquals(0, $s->amountOverLimit());
        $this->assertEquals(30.0, $s->utilization()['percentage']);
    }

    public function test_utilization_not_calculable_for_non_credit_or_zero_limit(): void
    {
        $nonCredit = CreditAccountSummary::for($this->customer(['is_credit_account' => 0, 'available_credit_balance' => 500]));
        $this->assertFalse($nonCredit->utilization()['is_calculable']);
        $this->assertEquals('not_credit_account', $nonCredit->utilization()['reason_unavailable']);
        $this->assertNull($nonCredit->utilizationDisplayPercent());

        $noLimit = CreditAccountSummary::for($this->customer(['is_credit_account' => 1, 'credit_limit' => 0, 'available_credit_balance' => 500]));
        $this->assertFalse($noLimit->utilization()['is_calculable']);
        $this->assertEquals('no_credit_limit', $noLimit->utilization()['reason_unavailable']);
    }

    public function test_zero_balance_reads_zero_utilization_not_hundred(): void
    {
        // DISP-1 regression: a non-over, $0-balance credit account must read 0%,
        // never 100%.
        $c = $this->customer(['is_credit_account' => 1, 'credit_limit' => 1000, 'available_credit_balance' => 0]);
        $s = CreditAccountSummary::for($c);
        $this->assertEquals(0.0, $s->utilization()['percentage']);
        $this->assertEquals(0.0, $s->utilizationDisplayPercent());
    }

    public function test_store_credit_is_separate_from_ar(): void
    {
        $c = $this->customer(['is_credit_account' => 1, 'credit_limit' => 1000, 'available_credit_balance' => 400]);
        CustomerCredit::create(['customer_id' => $c->id, 'type' => 'grant', 'amount' => 150, 'reason' => 'Goodwill']);

        $s = CreditAccountSummary::for($c);
        $this->assertEquals(150, $s->storeCreditBalance());
        $this->assertEquals(400, $s->outstandingBalance(), 'store credit must NOT change A/R balance');
        $this->assertEquals(600, $s->availableCredit(), 'store credit must NOT change available credit');
    }

    public function test_payments_come_from_the_account_ledger(): void
    {
        $c = $this->customer(['is_credit_account' => 1, 'credit_limit' => 1000, 'available_credit_balance' => 100]);
        $this->ledgerPayment($c, 200, now()->subDays(3));
        $this->ledgerPayment($c, 50, now()->subDays(1));

        $s = CreditAccountSummary::for($c);
        $this->assertNotNull($s->lastPayment());
        $this->assertEquals(50, (float) $s->lastPayment()->amount, 'most recent ledger payment');
        $this->assertEquals(250, $s->totalPaymentsDuring(now()->subDays(10), now()));
        $this->assertEquals(50, $s->totalPaymentsDuring(now()->subDays(2), now()));
    }

    public function test_aging_bucket_from_last_payment(): void
    {
        $c = $this->customer(['is_credit_account' => 1, 'credit_limit' => 1000, 'available_credit_balance' => 500]);
        $this->ledgerPayment($c, 100, now()->subDays(70));

        $s = CreditAccountSummary::for($c);
        $this->assertEqualsWithDelta(70, $s->daysSinceLastActivity(), 1);
        $this->assertEquals('61–90 days', $s->agingBucket());
    }

    public function test_bad_debt_is_canonical_and_approved(): void
    {
        // Positive balance + oldest outstanding exposure >= 60d → bad debt,
        // regardless of credit-limit configuration.
        $c = $this->customer(['is_credit_account' => 1, 'credit_limit' => 1000, 'available_credit_balance' => 500]);
        $this->ledgerDebit($c, 500, now()->subDays(75));

        $bad = CreditAccountSummary::for($c)->badDebt();
        $this->assertTrue($bad['is_bad_debt']);
        $this->assertTrue($bad['rule_approved']);
        $this->assertEquals(60, $bad['threshold_days']);
        $this->assertEqualsWithDelta(75, $bad['oldest_outstanding_age_days'], 1);
        $this->assertEquals(500, $bad['outstanding_balance']);

        // Approved credit account, recent exposure → not bad debt.
        $good = $this->customer(['is_credit_account' => 1, 'credit_limit' => 1000, 'available_credit_balance' => 200]);
        $this->ledgerDebit($good, 200, now()->subDays(5));
        $this->assertFalse(CreditAccountSummary::for($good)->badDebt()['is_bad_debt']);
    }

    public function test_toArray_never_folds_store_credit_into_balance(): void
    {
        $c = $this->customer(['is_credit_account' => 1, 'credit_limit' => 1000, 'available_credit_balance' => 400]);
        CustomerCredit::create(['customer_id' => $c->id, 'type' => 'grant', 'amount' => 999, 'reason' => 'x']);

        $arr = CreditAccountSummary::for($c)->toArray();
        $this->assertEquals(400, $arr['outstanding_balance']);
        $this->assertEquals(600, $arr['available_credit']);
        $this->assertEquals(999, $arr['store_credit_balance']);
    }
}
