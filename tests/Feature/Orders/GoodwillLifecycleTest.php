<?php

namespace Tests\Feature\Orders;

use App\Enums\Orders\GoodwillReasonCode;
use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Services\Orders\GoodwillAdjustmentService;
use App\Services\Orders\HistoricalTaxBasisResolver;
use App\Services\Orders\PaymentAllocationService;
use App\Services\ReceiptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Release-readiness walk: one test per representative financial lifecycle.
 *
 * The unit and feature suites prove each mechanism in isolation. This file
 * proves the mechanisms COMPOSE — that an order can be taxed, short-paid,
 * adjusted, receipted, refunded and reversed in sequence without any step
 * leaving the next one unable to reconstruct what it needs.
 *
 * `refund_after_goodwill_resolves_against_the_revised_basis` is the one that
 * matters most: a Goodwill adjustment that silently made an order
 * un-refundable would be a far worse defect than refusing the adjustment in
 * the first place.
 */
class GoodwillLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private User $manager;
    private int $productId;

    protected function setUp(): void
    {
        parent::setUp();
        HistoricalTaxBasisResolver::flushSchemaMemo();

        Permission::findOrCreate('goodwill.apply', 'web');
        Permission::findOrCreate('goodwill.reverse', 'web');

        $this->customer = Customer::factory()->create();

        $this->manager = User::create([
            'first_name' => 'Lee', 'last_name' => 'Lifecycle',
            'email' => 'lee-lifecycle@example.com', 'status' => 'Active',
        ]);
        $this->manager->givePermissionTo('goodwill.apply', 'goodwill.reverse');

        $this->productId = (int) DB::table('products')->insertGetId([
            'unique_id' => 'PRD-LIFE-1', 'product_name' => 'Lifecycle Tester',
            'slug' => 'lifecycle-tester', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    // 1 ── Ordinary taxable short pay ───────────────────────────────────────

    public function test_ordinary_taxable_short_pay(): void
    {
        $order = $this->order(200.00, 19.50, 219.50);
        $this->line($order, 200.00, 19.50);
        $this->pay($order, 185.00);

        $this->assertTrue($this->apply($order, 18500)['ok']);

        $order->refresh();
        $this->assertSame('168.56', (string) $order->subtotal);
        $this->assertSame('16.44', (string) $order->tax_amount);
        $this->assertSame('185.00', (string) $order->grand_total);
        $this->assertTrue($order->is_paid);
        $this->assertSame(1, $order->payments()->count());
    }

    // 2 ── Zero-tax order ───────────────────────────────────────────────────

    public function test_zero_tax_order(): void
    {
        $order = $this->order(200.00, 0.00, 200.00);
        $this->line($order, 200.00, 0.00);
        $this->pay($order, 185.00);

        $this->assertTrue($this->apply($order, 18500)['ok']);

        $order->refresh();
        $this->assertSame('185.00', (string) $order->subtotal);
        $this->assertSame('0.00', (string) $order->tax_amount, 'Reducing an untaxed order must never create tax.');
        $this->assertSame('185.00', (string) $order->grand_total);
    }

    // 3 ── Mixed taxable / tax-free ─────────────────────────────────────────

    public function test_mixed_taxable_and_tax_free_order(): void
    {
        // 100.00 taxable @ 9.75% = 9.75 ; 100.00 tax-free. Total 209.75.
        $order = $this->order(200.00, 9.75, 209.75);
        $this->line($order, 100.00, 9.75);
        $this->line($order, 100.00, 0.00);
        $this->pay($order, 190.00);

        $this->assertTrue($this->apply($order, 19000)['ok']);

        $order->refresh();
        $this->assertSame('190.00', (string) $order->grand_total);

        // The rate must come from the TAXABLE basis only, never from subtotal.
        $lines = $order->products()->orderBy('id')->get();
        $this->assertGreaterThan(0, (float) $lines[0]->tax, 'The taxable line keeps tax.');
        $this->assertSame('0.00', (string) $lines[1]->tax, 'The tax-free line stays untaxed however much it is reduced.');
        $this->assertLessThan(100.00, (float) $lines[1]->sub_total, 'Untaxed merchandise is still reducible.');
    }

    // 4 ── Special-tax order ────────────────────────────────────────────────

    public function test_special_tax_order(): void
    {
        // 200.00 @ 9.75% = 19.50 ordinary ; special 4.00 ; total 223.50.
        $order = $this->order(200.00, 19.50, 223.50, specialTax: 4.00);
        $this->line($order, 200.00, 19.50, specialTax: 4.00);
        $this->pay($order, 200.00);

        $this->assertTrue($this->apply($order, 20000)['ok']);

        $order->refresh();
        $c = fn ($v) => (int) round(((float) $v) * 100);

        $this->assertSame(
            20000,
            $c($order->subtotal) + $c($order->tax_amount) + $c($order->special_tax_amount) + $c($order->added_fees_amount),
            'Every component must sum to what was accepted.'
        );
        $this->assertGreaterThan(0, $c($order->special_tax_amount));
        $this->assertLessThan(400, $c($order->special_tax_amount), 'Special tax moves with its own basis.');
    }

    // 5 ── Extension refund ─────────────────────────────────────────────────

    public function test_extension_child_refund_basis_still_resolves(): void
    {
        // Extension children own no lines by design; the linked billing charge
        // is the authoritative record. Goodwill must not have broken this.
        $parent = $this->order(500.00, 48.75, 548.75);
        $this->line($parent, 500.00, 48.75);

        // scopeExtensionChildren() identifies a child by its order_number
        // being the parent's with a '-n' suffix, so the fixture must follow
        // the same convention Extension\StoreController uses.
        $child = $this->order(100.00, 9.75, 109.75);
        $child->update([
            'reference_order_number' => $parent->order_number,
            'order_number'           => $parent->order_number.'-1',
        ]);

        DB::table('billing_charges')->insert([
            'unique_id' => 'BC-LIFE-1',
            'billing_charge_type' => \App\Enums\Billing\BillingChargeType::Extension->value,
            'status' => 'pending',
            'parent_order_id' => $parent->id,
            'child_order_id' => $child->id,
            'customer_id' => $this->customer->id,
            'amount' => 100.00, 'tax_amount' => 9.75, 'tax_type' => 'add',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $basis = HistoricalTaxBasisResolver::resolve($child->fresh());

        $this->assertTrue($basis->succeeded(), $basis->failure?->message() ?? '');
        $this->assertEqualsWithDelta(0.0975, $basis->ordinaryRate(), 0.0000001);
        $this->assertEqualsWithDelta(
            0.89,
            PaymentAllocationService::proportionalTaxRefund($child->fresh(), 10.00, $basis),
            0.01
        );
    }

    // 6 ── Receipt supersession ─────────────────────────────────────────────

    public function test_receipt_supersession_across_the_whole_lifecycle(): void
    {
        $order = $this->order(200.00, 19.50, 219.50);
        $this->line($order, 200.00, 19.50);
        $this->pay($order, 185.00);

        $original = ReceiptService::getOrCreateReceipt($order);
        $this->apply($order, 18500);
        $adjusted = ReceiptService::currentReceipt($order->fresh());

        $this->assertSame($original->id, $adjusted->superseded_receipt_id);
        $this->assertSame('31.44', (string) $adjusted->goodwill_amount);
        $this->assertSame('219.50', (string) $original->fresh()->total, 'The original is untouched.');

        GoodwillAdjustmentService::reverse($order->fresh(), $this->manager, 'Lifecycle');
        $restored = ReceiptService::currentReceipt($order->fresh());

        $this->assertSame($adjusted->id, $restored->superseded_receipt_id);
        $this->assertSame(3, \App\Models\Customers\Receipt::where('order_id', $order->id)->count());
    }

    // 7 ── Refund after Goodwill ────────────────────────────────────────────

    public function test_refund_after_goodwill_resolves_against_the_revised_basis(): void
    {
        // The load-bearing composition. An adjusted order MUST remain
        // refundable: its columns are self-consistent after the adjustment,
        // so the basis is reconstructable from them.
        $order = $this->order(200.00, 19.50, 219.50);
        $this->line($order, 200.00, 19.50);
        $this->pay($order, 185.00);

        $this->assertTrue($this->apply($order, 18500)['ok']);

        $basis = HistoricalTaxBasisResolver::resolve($order->fresh());

        $this->assertTrue(
            $basis->succeeded(),
            'A Goodwill adjustment must not make an order un-refundable. Got: '.($basis->failure?->message() ?? '')
        );

        // The rate is unchanged — Goodwill reduces the basis, not the rate.
        $this->assertEqualsWithDelta(0.0975, $basis->ordinaryRate(), 0.0001);

        // A refund of the full revised total returns the revised tax.
        $tax = PaymentAllocationService::proportionalTaxRefund($order->fresh(), 185.00);
        $this->assertEqualsWithDelta(16.44, $tax, 0.02, 'Tax refunded must be capped at the ADJUSTED tax.');
    }

    // 8 ── Reversal ─────────────────────────────────────────────────────────

    public function test_reversal_restores_everything_and_reopens_the_balance(): void
    {
        $order = $this->order(200.00, 19.50, 223.50, specialTax: 4.00);
        $this->line($order, 200.00, 19.50, specialTax: 4.00);
        $this->pay($order, 200.00);

        $this->apply($order, 20000);
        $this->assertTrue(GoodwillAdjustmentService::reverse($order->fresh(), $this->manager, 'Lifecycle')['ok']);

        $order->refresh();
        $this->assertSame('200.00', (string) $order->subtotal);
        $this->assertSame('19.50', (string) $order->tax_amount);
        $this->assertSame('4.00', (string) $order->special_tax_amount);
        $this->assertSame('223.50', (string) $order->grand_total);
        $this->assertFalse($order->is_paid);
        $this->assertEqualsWithDelta(23.50, $order->balance_due, 0.001);
        $this->assertSame(1, $order->payments()->count(), 'Real tender is never touched.');

        // And the restored order is refundable again.
        $this->assertTrue(HistoricalTaxBasisResolver::resolve($order->fresh())->succeeded());
    }

    // ── Fixtures ───────────────────────────────────────────────────────────

    private function order(float $sub, float $tax, float $total, float $specialTax = 0.0): Order
    {
        return Order::create([
            'order_date' => now()->format('Y-m-d'),
            'customer_id' => $this->customer->id,
            'customer_name' => 'Lifecycle Customer',
            'subtotal' => $sub, 'tax_amount' => $tax,
            'special_tax_amount' => $specialTax, 'added_fees_amount' => 0,
            'discount_amount' => 0, 'grand_total' => $total,
        ]);
    }

    private function line(Order $order, float $sub, float $tax, float $specialTax = 0.0): void
    {
        static $n = 0;
        $n++;

        $order->products()->create([
            'unique_id' => 'ORD-LIFE-'.$n.'-'.$order->id,
            'product_id' => $this->productId,
            'product_name' => 'Lifecycle Tester',
            'price' => $sub, 'quantity' => 1,
            'sub_total' => $sub, 'tax' => $tax,
            'special_tax' => $specialTax, 'added_fees' => 0,
            'total' => $sub + $tax + $specialTax,
            'product_data' => json_encode(['special_tax' => $specialTax, 'added_fees' => 0]),
        ]);
    }

    private function pay(Order $order, float $amount): void
    {
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount' => $amount,
            'status' => OrderPaymentStatus::PartialPayment->value,
        ]);
    }

    private function apply(Order $order, int $acceptedCents): array
    {
        return GoodwillAdjustmentService::apply(
            $order->fresh(), $acceptedCents, GoodwillReasonCode::ManagerCourtesy,
            null, $this->manager, $this->manager,
        );
    }
}
