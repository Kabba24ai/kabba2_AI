<?php

namespace Tests\Feature\Orders;

use App\Enums\Discounts\DiscountTargetType;
use App\Enums\Goodwill\GoodwillReason;
use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Enums\Orders\RefundCalculationType;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerCredit;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Services\Discounts\DiscountApplicationService;
use App\Services\Goodwill\GoodwillAdjustmentService;
use App\Services\Goodwill\GoodwillApplyRequest;
use App\Services\Goodwill\GoodwillPermissions;
use App\Services\Orders\PaymentAllocationService;
use App\Services\Orders\TaxableBasisResolver;
use Database\Seeders\Iam\GoodwillPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * The refund tax split, against the true taxable basis.
 *
 * ── THE DEFECT THIS SUITE CLOSES ──────────────────────────────────────────
 *
 * `proportionalTaxRefund()` derived its rate as `tax_amount ÷ subtotal`.
 * `subtotal` is GROSS: it includes tax-free lines that never generated tax, and
 * it does not move when a pre-tax adjustment reduces `tax_amount`. Both errors
 * understate the rate, so the tax portion of every affected refund was too
 * small. The customer's TOTAL refund was always right — the base/tax split was
 * not, and that split is what tax remittance is reported from.
 *
 * ── HOW EACH CASE IS CHECKED ──────────────────────────────────────────────
 *
 * Every expected rate below is derived INDEPENDENTLY in the test, from the
 * order's own construction, and compared against what the service produces. A
 * test that called the resolver to compute its own expectation would prove only
 * that the resolver agrees with itself.
 *
 * Several cases also assert what the OLD formula would have produced, so the
 * size and direction of the correction are on the record rather than implied.
 */
class RefundTaxBasisTest extends TestCase
{
    use RefreshDatabase;

    private const RATE = 0.0975;

    private Customer $customer;

    private User $manager;

    private int $productId;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'Refund', 'last_name' => 'Basis',
            'email' => 'refund-basis@test.local', 'status' => 'Active',
        ]);

        $this->productId = (int) DB::table('products')->insertGetId([
            'unique_id' => 'PRD-RTB-1', 'product_name' => 'Refund Basis Probe',
            'slug' => 'refund-basis-probe', 'created_at' => now(), 'updated_at' => now(),
        ]);

        (new GoodwillPermissionSeeder())->run();

        $this->manager = User::create([
            'first_name' => 'Refund', 'last_name' => 'Manager',
            'email' => 'refund-manager@test.local', 'status' => 'Active',
        ]);
        $this->manager->givePermissionTo(GoodwillPermissions::APPLY);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    // ── The undiscounted baselines ─────────────────────────────────────────

    public function test_a_wholly_taxable_order_is_unchanged_by_the_fix(): void
    {
        // Where subtotal HAPPENED to equal the taxable basis, the old formula
        // was already right. The fix must not move it.
        $order = $this->order([['sub' => 1000.00, 'tax' => 97.50]]);

        $this->assertBasis($order, 1000.00);
        $this->assertTaxSplit($order, 219.50, self::RATE);
    }

    public function test_a_mixed_taxable_and_tax_free_order_uses_only_the_taxable_lines(): void
    {
        // $100 taxable + $100 tax-free. The old formula divided $9.75 by the
        // gross $200 and produced 4.875% — exactly half the real rate.
        $order = $this->order([
            ['sub' => 100.00, 'tax' => 9.75],
            ['sub' => 100.00, 'tax' => 0.00],
        ]);

        $this->assertBasis($order, 100.00, 'Only the taxable line counts.');
        $this->assertTaxSplit($order, 109.75, self::RATE);

        // The correction, quantified.
        $wrongRate = 9.75 / 200.00;
        $this->assertEqualsWithDelta(0.04875, $wrongRate, 0.000001);
        $this->assertGreaterThan(
            round(109.75 - (109.75 / (1 + $wrongRate)), 2),
            PaymentAllocationService::proportionalTaxRefund($order, 109.75),
            'The corrected split must recover MORE tax than the old formula did.'
        );
    }

    public function test_a_special_tax_order_derives_ordinary_tax_only(): void
    {
        // Special tax is a separate component and is not part of the ordinary
        // sales-tax rate. Its presence must not disturb the denominator.
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50, 'special' => 4.00]]);

        $this->assertBasis($order, 200.00, 'Special tax does not change the ordinary basis.');
        $this->assertTaxSplit($order, 219.50, self::RATE);
    }

    // ── After a pre-tax adjustment ─────────────────────────────────────────

    public function test_refund_after_store_credit_uses_the_discounted_basis(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $this->applyStoreCredit($order, 50.00);
        $order->refresh();

        // 150.00 surviving basis, tax recomputed to 14.63 — the rate is
        // preserved, which is the property the resolver relies on.
        $this->assertSame('14.63', (string) $order->tax_amount);
        $this->assertBasis($order, 150.00);
        $this->assertTaxSplit($order, 164.63, 14.63 / 150.00);

        $this->assertGreaterThan(
            $this->oldFormula($order, 164.63),
            PaymentAllocationService::proportionalTaxRefund($order, 164.63),
            'Before the fix the reduced tax was divided by the gross subtotal.'
        );
    }

    public function test_refund_after_goodwill_uses_the_discounted_basis(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $this->pay($order, 175.00);
        $this->applyGoodwill($order);
        $order->refresh();

        $this->assertSame('175.00', (string) $order->grand_total);

        $surviving = (float) $order->subtotal - (float) $order->pretax_discount_total;
        $this->assertBasis($order, $surviving);
        $this->assertTaxSplit($order, 175.00, (float) $order->tax_amount / $surviving);

        // The rate the engine preserved is the ORIGINAL rate.
        $this->assertEqualsWithDelta(
            self::RATE,
            TaxableBasisResolver::effectiveTaxRate($order),
            0.0002,
            'A proportional concession preserves the effective rate.'
        );
    }

    public function test_refund_after_stacked_store_credit_and_goodwill(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50, 'special' => 4.00, 'fees' => 6.00]]);
        $this->applyStoreCredit($order, 40.00);
        $order->refresh();

        $this->pay($order, (float) $order->grand_total - 25.00);
        $this->applyGoodwill($order);
        $order->refresh();

        $surviving = round((float) $order->subtotal - (float) $order->pretax_discount_total, 2);

        $this->assertBasis($order, $surviving);
        $this->assertEqualsWithDelta(
            self::RATE,
            TaxableBasisResolver::effectiveTaxRate($order),
            0.0005,
            'Two stacked concessions still preserve the rate.'
        );

        $this->assertTaxSplit($order, (float) $order->grand_total, (float) $order->tax_amount / $surviving);
    }

    public function test_a_mixed_order_after_a_concession_reduces_only_the_taxable_share(): void
    {
        // The compound case: tax-free lines AND a concession. The old formula
        // was wrong twice over here.
        $order = $this->order([
            ['sub' => 100.00, 'tax' => 9.75],
            ['sub' => 100.00, 'tax' => 0.00],
        ]);
        $this->applyStoreCredit($order, 50.00);
        $order->refresh();

        // A $50 concession across $200 gross leaves 75% of the taxable $100.
        $this->assertBasis($order, 75.00);
        $this->assertEqualsWithDelta(
            self::RATE,
            TaxableBasisResolver::effectiveTaxRate($order),
            0.0005
        );

        $wrong = (float) $order->tax_amount / (float) $order->subtotal;
        $this->assertLessThan(
            self::RATE / 2,
            $wrong,
            'The old denominator was gross AND undiscounted — understating by more than half.'
        );
    }

    // ── Refund shapes ──────────────────────────────────────────────────────

    public function test_a_partial_refund_splits_at_the_same_rate_as_a_full_one(): void
    {
        $order = $this->order([
            ['sub' => 100.00, 'tax' => 9.75],
            ['sub' => 100.00, 'tax' => 0.00],
        ]);

        $full = PaymentAllocationService::proportionalTaxRefund($order, 109.75);
        $part = PaymentAllocationService::proportionalTaxRefund($order, 54.875);

        $this->assertEqualsWithDelta($full / 2, $part, 0.01, 'The rate does not depend on the amount.');
        $this->assertEqualsWithDelta(9.75, $full, 0.01);
    }

    public function test_a_tax_only_refund_is_all_tax_and_never_consults_the_basis(): void
    {
        // Sales Tax Only is a different calculation type: the whole amount IS
        // tax, so no rate is applied at all. Guarded because a basis change
        // must not leak into a path that never used one.
        $order = $this->order([
            ['sub' => 100.00, 'tax' => 9.75],
            ['sub' => 100.00, 'tax' => 0.00],
        ]);

        $split = PaymentAllocationService::calculateAllocationSplits(
            $order,
            new Collection(),
            [['original_order_payment_id' => 1, 'amount' => 9.75]],
            RefundCalculationType::SalesTaxOnly,
        );

        $this->assertEqualsWithDelta(9.75, $split['total_tax'], 0.001);
        $this->assertEqualsWithDelta(0.0, $split['total_base'], 0.001);
    }

    public function test_a_multi_row_refund_reconciles_to_the_cent(): void
    {
        // Deterministic remainder-to-last-row rounding must still land the
        // aggregate exactly on a single-shot computation at the new rate.
        $order = $this->order([
            ['sub' => 300.00, 'tax' => 29.25],
            ['sub' => 100.00, 'tax' => 0.00],
        ]);

        $split = PaymentAllocationService::calculateAllocationSplits(
            $order,
            new Collection(),
            [
                ['original_order_payment_id' => 1, 'amount' => 100.01],
                ['original_order_payment_id' => 2, 'amount' => 100.01],
                ['original_order_payment_id' => 3, 'amount' => 133.31],
            ],
            RefundCalculationType::Standard,
        );

        $expected = PaymentAllocationService::proportionalTaxRefund($order, 333.33);

        $this->assertEqualsWithDelta($expected, $split['total_tax'], 0.001);
        $this->assertGreaterThan(0, $split['total_tax'], 'The rate must be non-zero for this order.');

        // Every row is internally exact, and the rows sum to the whole.
        $baseCents = 0;
        $taxCents = 0;

        foreach ($split['rows'] as $row) {
            $this->assertSame(
                $this->cents($row['amount']),
                $this->cents($row['base']) + $this->cents($row['tax']),
                'base + tax must equal the row amount exactly.'
            );
            $baseCents += $this->cents($row['base']);
            $taxCents += $this->cents($row['tax']);
        }

        $this->assertSame($this->cents(333.33), $baseCents + $taxCents, 'No cent may be created or lost.');
        $this->assertSame($this->cents($split['total_tax']), $taxCents);
    }

    // ── Line-less orders ───────────────────────────────────────────────────

    public function test_an_extension_child_resolves_from_its_own_subtotal(): void
    {
        // Extension children carry no lines by design; their subtotal IS the
        // discounted taxable base, written that way at creation. Refunds on
        // them must keep working — an earlier resolver design made adjusted
        // orders un-refundable, which is the failure this shape guards against.
        $extension = $this->extensionChild(500.00, 48.75);

        $this->assertBasis($extension, 500.00, 'Explicit extension handling, not a generic fallback.');
        $this->assertTaxSplit($extension, 548.75, self::RATE);
    }

    public function test_a_tax_free_extension_child_splits_to_zero_tax(): void
    {
        $extension = $this->extensionChild(500.00, 0.00);

        $this->assertSame(0, TaxableBasisResolver::resolveCents($extension));
        $this->assertSame(0.0, PaymentAllocationService::proportionalTaxRefund($extension, 500.00));
    }

    public function test_a_line_less_order_that_is_not_an_extension_is_unresolvable(): void
    {
        // NO generic tax_amount / subtotal fallback. An unresolvable basis
        // reports zero tax and logs, rather than reinstating the known-wrong
        // denominator exactly where the record is least trustworthy.
        $order = Order::create([
            'order_date' => now()->toDateString(),
            'customer_id' => $this->customer->id,
            'customer_name' => 'Orphan',
            'subtotal' => 200.00, 'tax_amount' => 19.50,
            'discount_amount' => 0, 'pretax_discount_total' => 0,
            'grand_total' => 219.50,
        ]);

        $this->assertNull(TaxableBasisResolver::resolveCents($order));
        $this->assertNull(TaxableBasisResolver::effectiveTaxRate($order));
        $this->assertSame(0.0, PaymentAllocationService::proportionalTaxRefund($order, 219.50));
    }

    public function test_the_refund_is_never_blocked_by_an_unresolvable_basis(): void
    {
        // The lesson from FD-002 Amendment 7: a read-only reconstruction must
        // never make money unreturnable.
        $order = Order::create([
            'order_date' => now()->toDateString(),
            'customer_id' => $this->customer->id,
            'customer_name' => 'Orphan',
            'subtotal' => 200.00, 'tax_amount' => 19.50,
            'discount_amount' => 0, 'pretax_discount_total' => 0,
            'grand_total' => 219.50,
        ]);

        $split = PaymentAllocationService::calculateAllocationSplits(
            $order,
            new Collection(),
            [['original_order_payment_id' => 1, 'amount' => 219.50]],
            RefundCalculationType::Standard,
        );

        $this->assertEqualsWithDelta(219.50, $split['total_amount'], 0.001, 'The full amount is still refundable.');
        $this->assertEqualsWithDelta(0.0, $split['total_tax'], 0.001);
        $this->assertEqualsWithDelta(219.50, $split['total_base'], 0.001);
    }

    // ── Edge cases ─────────────────────────────────────────────────────────

    public function test_an_order_with_no_tax_resolves_to_a_zero_basis_not_an_unknown_one(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 0.00]]);

        $this->assertSame(0, TaxableBasisResolver::resolveCents($order), 'Zero, not null — nothing is unknown.');
        $this->assertSame(0.0, TaxableBasisResolver::effectiveTaxRate($order));
        $this->assertSame(0.0, PaymentAllocationService::proportionalTaxRefund($order, 200.00));
    }

    public function test_tax_charged_with_no_taxable_line_is_unresolvable(): void
    {
        // A self-contradicting record. Inventing a basis would launder the
        // contradiction into a figure a report would treat as authoritative.
        $order = $this->order([['sub' => 200.00, 'tax' => 0.00]]);
        $order->update(['tax_amount' => 19.50]);

        $this->assertNull(TaxableBasisResolver::resolveCents($order->fresh()));
        $this->assertSame(0.0, PaymentAllocationService::proportionalTaxRefund($order->fresh(), 100.00));
    }

    public function test_taxability_is_read_from_the_frozen_snapshot_not_the_live_column(): void
    {
        // A concession scales a line's live tax down, and on a small line that
        // can round to zero — which would make a genuinely taxable line look
        // exempt and silently shrink the basis.
        $order = $this->order([
            ['sub' => 500.00, 'tax' => 48.75],
            ['sub' => 0.04, 'tax' => 0.01],
        ]);

        $this->applyStoreCredit($order, 495.00);
        $order->refresh();

        $small = $order->products()->orderBy('id')->get()->last();
        $this->assertSame('0.00', (string) $small->tax, 'Its live tax has rounded away…');

        // …but it is still counted, because the snapshot says it was taxable.
        $surviving = (float) $order->subtotal - (float) $order->pretax_discount_total;
        $this->assertBasis($order, $surviving);
    }

    public function test_a_zero_or_negative_refund_amount_yields_no_tax(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);

        $this->assertSame(0.0, PaymentAllocationService::proportionalTaxRefund($order, 0.0));
        $this->assertSame(0.0, PaymentAllocationService::proportionalTaxRefund($order, -50.0));
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private function assertBasis(Order $order, float $expected, string $message = ''): void
    {
        $this->assertSame(
            $this->cents($expected),
            TaxableBasisResolver::resolveCents($order->fresh()),
            $message ?: "Taxable basis should be {$expected}."
        );
    }

    /** The service's split must match an independently computed rate. */
    private function assertTaxSplit(Order $order, float $refund, float $expectedRate): void
    {
        $this->assertEqualsWithDelta(
            round($refund - ($refund / (1 + $expectedRate)), 2),
            PaymentAllocationService::proportionalTaxRefund($order->fresh(), $refund),
            0.01
        );
    }

    /** What the pre-fix formula would have produced, for comparison only. */
    private function oldFormula(Order $order, float $refund): float
    {
        $rate = (float) $order->tax_amount / (float) $order->subtotal;

        return round($refund - ($refund / (1 + $rate)), 2);
    }

    private function cents($value): int
    {
        return (int) round(((float) $value) * 100);
    }

    private function applyStoreCredit(Order $order, float $amount): void
    {
        CustomerCredit::create([
            'customer_id' => $this->customer->id,
            'type' => 'grant', 'amount' => $amount, 'reason' => 'refund basis seed',
        ]);

        app(DiscountApplicationService::class)->applyStoreCredit(
            DiscountTargetType::Order, (int) $order->id, $amount,
            'rtb-sc-'.$order->id.'-'.(++$this->sequence), null, null, null, $this->customer->id,
        );
    }

    private function applyGoodwill(Order $order): void
    {
        $service = app(GoodwillAdjustmentService::class);
        $preview = $service->preview($order->fresh(), $this->manager);

        $service->apply(new GoodwillApplyRequest(
            order: $order->fresh(),
            reason: GoodwillReason::ServiceFailure,
            note: null,
            idempotencyKey: 'rtb-gw-'.$order->id.'-'.(++$this->sequence),
            operator: $this->manager,
            approver: $this->manager,
            expectedGoodwillAmount: $preview->concession(),
            expectedAcceptedPaymentTotal: $preview->acceptedPaymentTotal(),
        ));
    }

    private function pay(Order $order, float $amount): void
    {
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount' => $amount,
            'status' => OrderPaymentStatus::Paid->value,
        ]);
    }

    /** A line-less extension child, linked by its BillingCharge. */
    private function extensionChild(float $base, float $tax): Order
    {
        $parent = $this->order([['sub' => 100.00, 'tax' => 9.75]]);

        $child = Order::create([
            'order_date' => now()->toDateString(),
            'customer_id' => $this->customer->id,
            'customer_name' => 'Extension Child',
            'reference_order_number' => $parent->order_number,
            'subtotal' => $base, 'tax_amount' => $tax,
            'discount_amount' => 0, 'pretax_discount_total' => 0,
            'grand_total' => $base + $tax,
            'is_tax_exempt' => $tax > 0 ? 'No' : 'Yes',
        ]);

        DB::table('billing_charges')->insert([
            'unique_id' => 'BC-RTB-'.(++$this->sequence),
            'customer_id' => $this->customer->id,
            'parent_order_id' => $parent->id,
            'child_order_id' => $child->id,
            'billing_charge_type' => \App\Enums\Billing\BillingChargeType::Extension->value,
            'amount' => $base,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $child->fresh();
    }

    /** @param array<int,array<string,float>> $lines */
    private function order(array $lines): Order
    {
        $sub = array_sum(array_column($lines, 'sub'));
        $tax = array_sum(array_column($lines, 'tax'));
        $special = array_sum(array_map(fn ($l) => $l['special'] ?? 0, $lines));
        $fees = array_sum(array_map(fn ($l) => $l['fees'] ?? 0, $lines));

        $order = Order::create([
            'order_date' => now()->toDateString(),
            'customer_id' => $this->customer->id,
            'customer_name' => 'Refund Basis',
            'subtotal' => $sub, 'tax_amount' => $tax,
            'special_tax_amount' => $special, 'added_fees_amount' => $fees,
            'discount_amount' => 0, 'pretax_discount_total' => 0,
            'grand_total' => $sub + $tax + $special + $fees,
        ]);

        foreach ($lines as $i => $l) {
            $order->products()->create([
                'unique_id' => 'ORD-RTB-'.$order->id.'-'.$i,
                'product_id' => $this->productId,
                'product_name' => 'Refund Basis Probe',
                'price' => $l['sub'], 'quantity' => 1,
                'sub_total' => $l['sub'], 'tax' => $l['tax'],
                'special_tax' => $l['special'] ?? 0, 'added_fees' => $l['fees'] ?? 0,
                'total' => $l['sub'] + $l['tax'] + ($l['special'] ?? 0) + ($l['fees'] ?? 0),
                'product_data' => json_encode([
                    'sub_total' => $l['sub'], 'tax' => $l['tax'],
                    'special_tax' => $l['special'] ?? 0, 'added_fees' => $l['fees'] ?? 0,
                ]),
            ]);
        }

        return $order->fresh();
    }
}
