<?php

namespace Tests\Feature\Discounts;

use App\Enums\Discounts\DiscountCalculationType;
use App\Enums\Discounts\DiscountTargetType;
use App\Enums\Discounts\DiscountType;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerCredit;
use App\Models\Discounts\ProductDiscount;
use App\Services\CustomerCreditService;
use App\Services\Discounts\Contracts\DiscountTarget;
use App\Services\Discounts\DiscountApplicationService;
use App\Services\Discounts\DiscountCalculator;
use App\Services\Discounts\DiscountException;
use App\Services\Discounts\DiscountResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DiscountApplicationServiceTest extends TestCase
{
    use RefreshDatabase;

    private const RATE = 0.0975;

    private function service(): DiscountApplicationService
    {
        return new DiscountApplicationService(new DiscountCalculator());
    }

    private function customerWithCredit(float $credit): Customer
    {
        $c = Customer::create([
            'first_name' => 'Disc', 'last_name' => 'Target',
            'email' => 'disc-' . uniqid() . '@test.local', 'status' => 'Active',
        ]);
        if ($credit > 0) {
            CustomerCredit::create(['customer_id' => $c->id, 'type' => 'grant', 'amount' => $credit, 'reason' => 'seed']);
        }
        return $c;
    }

    private function target(Customer $c, float $base, float $rate = self::RATE, bool $discountable = true): FakeDiscountTarget
    {
        return new FakeDiscountTarget(DiscountTargetType::Order, 4242, $c->id, $base, $rate, $discountable);
    }

    private function assertNoPaymentRecords(): void
    {
        $this->assertEquals(0, DB::table('order_payments')->count(), 'no order_payments row created by a discount');
        $this->assertEquals(0, DB::table('customer_accounts')->where('type', 'payment')->count(), 'no A/R payment row created');
    }

    public function test_partial_apply_reduces_credit_and_reprices_without_payment(): void
    {
        $c = $this->customerWithCredit(1000);
        $t = $this->target($c, 1000);

        $d = $this->service()->apply($t, DiscountType::StoreCredit, DiscountCalculationType::FixedAmount, 500.0, null,
            idempotencyKey: 'k1', appliedBy: null, expectedCustomerId: $c->id);

        $this->assertEquals(500.00, (float) $d->calculated_discount_amount);
        $this->assertEquals(500.00, (float) $d->discounted_product_value);
        $this->assertEquals(48.75, (float) $d->tax_after);
        $this->assertEquals(500.00, CustomerCreditService::remainingBalance($c->id), 'credit reduced by exactly the discount');
        $this->assertNotNull($d->store_credit_redemption_id, 'redemption linked');
        $this->assertEquals('redemption', CustomerCredit::find($d->store_credit_redemption_id)->type);
        $this->assertEqualsWithDelta(548.75, $t->lastResult->finalAmountDue, 0.001, 'target re-priced');
        $this->assertNoPaymentRecords();
    }

    public function test_full_apply_zeroes_tax_and_total(): void
    {
        $c = $this->customerWithCredit(500);
        $t = $this->target($c, 500);
        $d = $this->service()->apply($t, DiscountType::StoreCredit, DiscountCalculationType::FixedAmount, 500.0, null,
            idempotencyKey: 'k2', appliedBy: null);
        $this->assertEquals(0.00, (float) $d->discounted_product_value);
        $this->assertEquals(0.00, (float) $d->tax_after);
        $this->assertEquals(0.00, CustomerCreditService::remainingBalance($c->id));
    }

    public function test_idempotent_apply_reduces_credit_once(): void
    {
        $c = $this->customerWithCredit(1000);
        $t = $this->target($c, 1000);
        $svc = $this->service();
        $d1 = $svc->apply($t, DiscountType::StoreCredit, DiscountCalculationType::FixedAmount, 300.0, null, idempotencyKey: 'same', appliedBy: null);
        $d2 = $svc->apply($t, DiscountType::StoreCredit, DiscountCalculationType::FixedAmount, 300.0, null, idempotencyKey: 'same', appliedBy: null);
        $this->assertEquals($d1->id, $d2->id, 'same key returns same discount');
        $this->assertEquals(1, ProductDiscount::count());
        $this->assertEquals(700.00, CustomerCreditService::remainingBalance($c->id), 'reduced once');
    }

    public function test_two_distinct_keys_same_amount_both_succeed(): void
    {
        $c = $this->customerWithCredit(1000);
        $svc = $this->service();
        $svc->apply($this->target($c, 1000), DiscountType::StoreCredit, DiscountCalculationType::FixedAmount, 200.0, null, idempotencyKey: 'a', appliedBy: null);
        $svc->apply($this->target($c, 1000), DiscountType::StoreCredit, DiscountCalculationType::FixedAmount, 200.0, null, idempotencyKey: 'b', appliedBy: null);
        $this->assertEquals(2, ProductDiscount::count());
        $this->assertEquals(600.00, CustomerCreditService::remainingBalance($c->id));
    }

    public function test_wrong_customer_target_rejected(): void
    {
        $c = $this->customerWithCredit(500);
        $t = $this->target($c, 500);
        $this->expectException(DiscountException::class);
        $this->service()->apply($t, DiscountType::StoreCredit, DiscountCalculationType::FixedAmount, 100.0, null,
            idempotencyKey: 'wc', appliedBy: null, expectedCustomerId: $c->id + 999);
    }

    public function test_phase1_rejects_goodwill_and_general(): void
    {
        $c = $this->customerWithCredit(500);
        $svc = $this->service();
        try {
            $svc->apply($this->target($c, 500), DiscountType::Goodwill, DiscountCalculationType::FixedAmount, 12.0, null, idempotencyKey: 'g', appliedBy: null);
            $this->fail('goodwill should be rejected');
        } catch (DiscountException $e) {
            $this->assertStringContainsString('not operational', $e->getMessage());
        }
        try {
            $svc->apply($this->target($c, 500), DiscountType::General, DiscountCalculationType::Percentage, null, 10.0, idempotencyKey: 'gn', appliedBy: null);
            $this->fail('general should be rejected');
        } catch (DiscountException $e) {
            $this->assertStringContainsString('not operational', $e->getMessage());
        }
        $this->assertEquals(0, ProductDiscount::count());
        $this->assertEquals(500.00, CustomerCreditService::remainingBalance($c->id), 'no balance touched');
    }

    public function test_over_available_rejected(): void
    {
        $c = $this->customerWithCredit(100);
        $this->expectException(DiscountException::class);
        $this->service()->apply($this->target($c, 1000), DiscountType::StoreCredit, DiscountCalculationType::FixedAmount, 400.0, null, idempotencyKey: 'oa', appliedBy: null);
    }

    public function test_over_eligible_value_rejected(): void
    {
        $c = $this->customerWithCredit(1000);
        $this->expectException(DiscountException::class);
        $this->service()->apply($this->target($c, 300), DiscountType::StoreCredit, DiscountCalculationType::FixedAmount, 400.0, null, idempotencyKey: 'oe', appliedBy: null);
    }

    public function test_not_discountable_target_rejected(): void
    {
        $c = $this->customerWithCredit(500);
        $t = $this->target($c, 500, self::RATE, false);
        $this->expectException(DiscountException::class);
        $this->service()->apply($t, DiscountType::StoreCredit, DiscountCalculationType::FixedAmount, 100.0, null, idempotencyKey: 'nd', appliedBy: null);
    }

    public function test_zero_amount_rejected(): void
    {
        $c = $this->customerWithCredit(500);
        $this->expectException(DiscountException::class);
        $this->service()->apply($this->target($c, 500), DiscountType::StoreCredit, DiscountCalculationType::FixedAmount, 0.0, null, idempotencyKey: 'z', appliedBy: null);
    }

    public function test_concurrency_second_apply_after_exhaustion_is_rejected(): void
    {
        // Serialized proxy for the lock: once balance is spent, a second
        // application that exceeds the now-lower balance is rejected in-txn.
        $c = $this->customerWithCredit(500);
        $svc = $this->service();
        $svc->apply($this->target($c, 1000), DiscountType::StoreCredit, DiscountCalculationType::FixedAmount, 400.0, null, idempotencyKey: 'c1', appliedBy: null);
        $this->assertEquals(100.00, CustomerCreditService::remainingBalance($c->id));
        $this->expectException(DiscountException::class);
        $svc->apply($this->target($c, 1000), DiscountType::StoreCredit, DiscountCalculationType::FixedAmount, 400.0, null, idempotencyKey: 'c2', appliedBy: null);
    }

    public function test_reversal_restores_credit_once_and_flags_reversed(): void
    {
        $c = $this->customerWithCredit(1000);
        $t = $this->target($c, 1000);
        $svc = $this->service();
        $d = $svc->apply($t, DiscountType::StoreCredit, DiscountCalculationType::FixedAmount, 400.0, null, idempotencyKey: 'r1', appliedBy: null);
        $this->assertEquals(600.00, CustomerCreditService::remainingBalance($c->id));

        $reversal = $svc->reverse($d, $t, reversedBy: null);
        $d->refresh();
        $this->assertEquals(ProductDiscount::STATUS_REVERSED, $d->status);
        $this->assertEquals($reversal->id, $d->reversed_by_discount_id);
        $this->assertEquals(1000.00, CustomerCreditService::remainingBalance($c->id), 'balance restored');
        $this->assertTrue($t->reversed, 'target pricing restored');

        // Re-reversal is a no-op (idempotent) — balance not restored twice.
        $svc->reverse($d, $t, reversedBy: null);
        $this->assertEquals(1000.00, CustomerCreditService::remainingBalance($c->id));
    }
}

/** In-test adapter exercising the full service without a real obligation. */
class FakeDiscountTarget implements DiscountTarget
{
    public ?DiscountResult $lastResult = null;
    public bool $reversed = false;

    public function __construct(
        private DiscountTargetType $type,
        private int $id,
        private ?int $customerId,
        private float $base,
        private float $rate,
        private bool $discountable,
    ) {}

    public function targetType(): DiscountTargetType { return $this->type; }
    public function targetId(): int { return $this->id; }
    public function customerId(): ?int { return $this->customerId; }
    public function lockAndRefresh(): void {}
    public function isDiscountable(): bool { return $this->discountable; }
    public function ineligibleReason(): ?string { return $this->discountable ? null : 'Already posted/finalized.'; }
    public function eligibleProductValue(): float { return $this->base; }
    public function taxRate(): float { return $this->rate; }
    public function applyDiscount(DiscountResult $result, ProductDiscount $discount): void { $this->lastResult = $result; }
    public function reverseDiscount(ProductDiscount $original): void { $this->reversed = true; }
}
