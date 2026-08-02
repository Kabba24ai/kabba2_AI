<?php

namespace Tests\Feature\Orders;

use App\Enums\Orders\GoodwillReasonCode;
use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Customers\Customer;
use App\Models\Customers\Receipt;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Services\Orders\GoodwillAdjustmentService;
use App\Services\Orders\HistoricalTaxBasisResolver;
use App\Services\ReceiptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Receipt supersession and reissue (Commit 4B).
 *
 * A receipt is a document that was handed to a customer. Rewriting it destroys
 * the evidence of what they were originally told, so nothing here ever edits
 * an existing receipt or receipt item: a NEW row is issued that points back at
 * the one it replaces, and the newest is the current one.
 *
 * `the_original_receipt_and_its_items_are_never_modified` is the load-bearing
 * test — every other guarantee here is worthless if the prior document can be
 * silently altered.
 */
class GoodwillReceiptSupersessionTest extends TestCase
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
            'first_name' => 'Rita', 'last_name' => 'Receipts',
            'email' => 'rita-receipts@example.com', 'status' => 'Active',
        ]);
        $this->manager->givePermissionTo('goodwill.apply', 'goodwill.reverse');

        $this->productId = (int) DB::table('products')->insertGetId([
            'unique_id'    => 'PRD-RECEIPT-1',
            'product_name' => 'Receipt Tester',
            'slug'         => 'receipt-tester',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    // ── Apply ──────────────────────────────────────────────────────────────

    public function test_apply_issues_a_new_receipt_linked_to_the_one_it_replaces(): void
    {
        $order = $this->paidOrder();
        $original = ReceiptService::getOrCreateReceipt($order);

        $result = GoodwillAdjustmentService::apply($order->fresh(), 18500, GoodwillReasonCode::ManagerCourtesy, null, $this->manager, $this->manager);
        $this->assertTrue($result['ok']);

        $this->assertSame(2, Receipt::where('order_id', $order->id)->count(), 'A replacement is added, not an edit.');

        $current = ReceiptService::currentReceipt($order->fresh());
        $this->assertNotSame($original->id, $current->id);
        $this->assertSame($original->id, $current->superseded_receipt_id, 'The new receipt must point back at the old one.');
        $this->assertSame($result['adjustment']->id, $current->goodwill_adjustment_id);
    }

    public function test_the_original_receipt_and_its_items_are_never_modified(): void
    {
        $order = $this->paidOrder();
        $original = ReceiptService::getOrCreateReceipt($order);

        $beforeRow   = DB::table('receipts')->where('id', $original->id)->first();
        $beforeItems = DB::table('receipt_items')->where('receipt_id', $original->id)->orderBy('id')->get()->toArray();

        GoodwillAdjustmentService::apply($order->fresh(), 18500, GoodwillReasonCode::ManagerCourtesy, null, $this->manager, $this->manager);

        $this->assertEquals($beforeRow, DB::table('receipts')->where('id', $original->id)->first(), 'The prior receipt row must be byte-identical.');
        $this->assertEquals($beforeItems, DB::table('receipt_items')->where('receipt_id', $original->id)->orderBy('id')->get()->toArray(), 'Its items must be byte-identical.');
    }

    public function test_only_the_latest_receipt_is_treated_as_current(): void
    {
        $order = $this->paidOrder();
        $original = ReceiptService::getOrCreateReceipt($order);

        GoodwillAdjustmentService::apply($order->fresh(), 18500, GoodwillReasonCode::ManagerCourtesy, null, $this->manager, $this->manager);

        // Every consumer goes through getOrCreateReceipt(), which must return
        // the replacement rather than re-serving the stale original.
        $served = ReceiptService::getOrCreateReceipt($order->fresh());

        $this->assertNotSame($original->id, $served->id);
        $this->assertTrue($original->fresh()->isSuperseded());
        $this->assertFalse($served->isSuperseded());
        $this->assertTrue($served->isSuperseding());
    }

    public function test_the_adjusted_receipt_states_the_goodwill_explicitly_and_reconciles(): void
    {
        $order = $this->paidOrder();
        ReceiptService::getOrCreateReceipt($order);

        $result = GoodwillAdjustmentService::apply($order->fresh(), 18500, GoodwillReasonCode::ManagerCourtesy, null, $this->manager, $this->manager);
        $current = ReceiptService::currentReceipt($order->fresh());

        // The concession is named, not folded into the merchandise total.
        $this->assertSame('31.44', (string) $current->goodwill_amount);

        // subtotal stays the ORIGINAL figure — the goods supplied did not change.
        $this->assertSame('200.00', (string) $current->subtotal);
        $this->assertSame('16.44', (string) $current->sales_tax);
        $this->assertSame('185.00', (string) $current->total);

        // The identity every receipt satisfies.
        $c = fn ($v) => (int) round(((float) $v) * 100);
        $this->assertSame(
            $c($current->total),
            $c($current->subtotal) - $c($current->goodwill_amount) + $c($current->sales_tax),
            'subtotal - goodwill + tax = total'
        );

        $this->assertSame($result['adjustment']->goodwill_amount, $current->goodwill_amount);
    }

    public function test_an_ordinary_receipt_keeps_the_familiar_identity(): void
    {
        // goodwill_amount 0 reduces the identity to subtotal + tax = total,
        // so no existing receipt changes meaning.
        $order = $this->paidOrder();
        $receipt = ReceiptService::getOrCreateReceipt($order);

        $this->assertSame('0.00', (string) $receipt->goodwill_amount);
        $this->assertNull($receipt->superseded_receipt_id);
        $this->assertNull($receipt->goodwill_adjustment_id);

        $c = fn ($v) => (int) round(((float) $v) * 100);
        $this->assertSame($c($receipt->total), $c($receipt->subtotal) + $c($receipt->sales_tax));
    }

    public function test_the_adjustment_records_which_receipt_it_superseded(): void
    {
        $order = $this->paidOrder();
        $original = ReceiptService::getOrCreateReceipt($order);

        $result = GoodwillAdjustmentService::apply($order->fresh(), 18500, GoodwillReasonCode::ManagerCourtesy, null, $this->manager, $this->manager);

        $this->assertSame($original->id, $result['adjustment']->superseded_receipt_id);
    }

    public function test_an_order_with_no_receipt_gets_none_created_by_apply(): void
    {
        // There is nothing to supersede, and the eventual receipt will be
        // built from the already-adjusted order — correct without our help.
        $order = $this->paidOrder();

        $this->assertTrue(GoodwillAdjustmentService::apply($order->fresh(), 18500, GoodwillReasonCode::ManagerCourtesy, null, $this->manager, $this->manager)['ok']);

        $this->assertSame(0, Receipt::where('order_id', $order->id)->count());

        // And when one is finally created it reflects the adjusted totals.
        $receipt = ReceiptService::getOrCreateReceipt($order->fresh());
        $this->assertSame('185.00', (string) $receipt->total);
    }

    // ── Reverse ────────────────────────────────────────────────────────────

    public function test_reversal_issues_its_own_superseding_receipt_restoring_the_original_figures(): void
    {
        $order = $this->paidOrder();
        $original = ReceiptService::getOrCreateReceipt($order);

        GoodwillAdjustmentService::apply($order->fresh(), 18500, GoodwillReasonCode::ManagerCourtesy, null, $this->manager, $this->manager);
        $adjusted = ReceiptService::currentReceipt($order->fresh());

        $this->assertTrue(GoodwillAdjustmentService::reverse($order->fresh(), $this->manager, 'Applied in error')['ok']);

        $this->assertSame(3, Receipt::where('order_id', $order->id)->count(), 'Three documents: original, adjusted, restored.');

        $restored = ReceiptService::currentReceipt($order->fresh());
        $this->assertSame($adjusted->id, $restored->superseded_receipt_id, 'The reversal supersedes the ADJUSTED receipt, not the original.');
        $this->assertSame('0.00', (string) $restored->goodwill_amount, 'The concession no longer applies.');
        $this->assertSame('219.50', (string) $restored->total);
        $this->assertSame('200.00', (string) $restored->subtotal);
    }

    public function test_reversal_leaves_both_earlier_receipts_untouched(): void
    {
        $order = $this->paidOrder();
        ReceiptService::getOrCreateReceipt($order);
        GoodwillAdjustmentService::apply($order->fresh(), 18500, GoodwillReasonCode::ManagerCourtesy, null, $this->manager, $this->manager);

        $before      = DB::table('receipts')->where('order_id', $order->id)->orderBy('id')->get()->toArray();
        $beforeItems = DB::table('receipt_items')->orderBy('id')->get()->toArray();

        GoodwillAdjustmentService::reverse($order->fresh(), $this->manager, 'Applied in error');

        $after = DB::table('receipts')->where('order_id', $order->id)->orderBy('id')->limit(2)->get()->toArray();
        $this->assertEquals($before, $after, 'History is append-only.');

        $afterItems = DB::table('receipt_items')->orderBy('id')->limit(count($beforeItems))->get()->toArray();
        $this->assertEquals($beforeItems, $afterItems);
    }

    public function test_the_supersession_chain_is_walkable(): void
    {
        $order = $this->paidOrder();
        $original = ReceiptService::getOrCreateReceipt($order);
        GoodwillAdjustmentService::apply($order->fresh(), 18500, GoodwillReasonCode::ManagerCourtesy, null, $this->manager, $this->manager);
        GoodwillAdjustmentService::reverse($order->fresh(), $this->manager, 'Applied in error');

        $current = ReceiptService::currentReceipt($order->fresh());

        $this->assertSame($original->id, $current->supersededReceipt->supersededReceipt->id);
        $this->assertNull($current->supersededReceipt->supersededReceipt->superseded_receipt_id, 'The chain terminates at the original.');
    }

    public function test_a_receipt_cannot_supersede_itself(): void
    {
        // The shortest possible cycle. supersede() cannot produce one, but a
        // self-referential row would make isSuperseded() true forever and
        // getOrCreateReceipt() would serve a document claiming to have
        // replaced itself, so the invariant is enforced rather than assumed.
        $order = $this->paidOrder();
        $receipt = ReceiptService::getOrCreateReceipt($order);

        $this->expectException(\LogicException::class);

        $receipt->superseded_receipt_id = $receipt->id;
        $receipt->save();
    }

    public function test_every_receipt_supersedes_only_an_earlier_one(): void
    {
        $order = $this->paidOrder();
        ReceiptService::getOrCreateReceipt($order);
        GoodwillAdjustmentService::apply($order->fresh(), 18500, GoodwillReasonCode::ManagerCourtesy, null, $this->manager, $this->manager);
        GoodwillAdjustmentService::reverse($order->fresh(), $this->manager, 'Applied in error');

        foreach (Receipt::where('order_id', $order->id)->orderBy('id')->get() as $receipt) {
            if ($receipt->superseded_receipt_id === null) {
                continue;
            }

            $this->assertLessThan(
                $receipt->id,
                $receipt->superseded_receipt_id,
                'A receipt may only supersede one issued before it — this is what makes the chain acyclic.'
            );
        }
    }

    // ── Atomicity ──────────────────────────────────────────────────────────

    public function test_a_refused_apply_creates_no_receipt(): void
    {
        $order = $this->paidOrder();
        $original = ReceiptService::getOrCreateReceipt($order);

        // Refused after the calculation succeeds: Other without a note.
        $result = GoodwillAdjustmentService::apply($order->fresh(), 18500, GoodwillReasonCode::Other, null, $this->manager, $this->manager);

        $this->assertFalse($result['ok']);
        $this->assertSame(1, Receipt::where('order_id', $order->id)->count(), 'The rollback must discard the receipt with the adjustment.');
        $this->assertSame($original->id, ReceiptService::currentReceipt($order->fresh())->id);
    }

    public function test_a_refused_reversal_creates_no_receipt(): void
    {
        $order = $this->paidOrder();
        ReceiptService::getOrCreateReceipt($order);
        GoodwillAdjustmentService::apply($order->fresh(), 18500, GoodwillReasonCode::ManagerCourtesy, null, $this->manager, $this->manager);

        // An invoice issued afterwards blocks the reversal.
        $invoiceId = (int) DB::table('invoices')->insertGetId([
            'unique_id'          => (string) \Illuminate\Support\Str::uuid(),
            'invoice_number'     => 'INV-SUP-'.$order->id,
            'invoice_date'       => now()->toDateString(),
            'customer_id'        => $this->customer->id,
            'invoice_created_by' => $this->manager->id,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
        $order->update(['invoice_id' => $invoiceId]);

        $countBefore = Receipt::where('order_id', $order->id)->count();

        $result = GoodwillAdjustmentService::reverse($order->fresh(), $this->manager, 'Too late');

        $this->assertFalse($result['ok']);
        $this->assertSame($countBefore, Receipt::where('order_id', $order->id)->count());
    }

    public function test_a_duplicate_apply_supersedes_only_once(): void
    {
        $order = $this->paidOrder();
        ReceiptService::getOrCreateReceipt($order);

        $token = 'idem-supersede-1';
        GoodwillAdjustmentService::apply($order->fresh(), 18500, GoodwillReasonCode::ManagerCourtesy, null, $this->manager, $this->manager, $token);
        $replay = GoodwillAdjustmentService::apply($order->fresh(), 18500, GoodwillReasonCode::ManagerCourtesy, null, $this->manager, $this->manager, $token);

        $this->assertTrue($replay['replayed']);
        $this->assertSame(2, Receipt::where('order_id', $order->id)->count(), 'A retry must not issue a second replacement.');
    }

    // ── Fixtures ───────────────────────────────────────────────────────────

    /** 200.00 basis @ 9.75% = 19.50; grand total 219.50; 185.00 collected. */
    private function paidOrder(): Order
    {
        $order = Order::create([
            'order_date' => now()->format('Y-m-d'),
            'customer_id' => $this->customer->id,
            'customer_name' => 'Receipt Customer',
            'subtotal' => 200.00, 'tax_amount' => 19.50,
            'discount_amount' => 0, 'grand_total' => 219.50,
        ]);

        $order->products()->create([
            'unique_id' => 'ORD-REC-'.$order->id,
            'product_id' => $this->productId,
            'product_name' => 'Receipt Tester',
            'price' => 200.00, 'quantity' => 1,
            'sub_total' => 200.00, 'tax' => 19.50, 'total' => 219.50,
            'special_tax' => 0, 'added_fees' => 0,
            'product_data' => json_encode(['special_tax' => 0, 'added_fees' => 0]),
        ]);

        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount' => 185.00,
            'status' => OrderPaymentStatus::PartialPayment->value,
        ]);

        return $order->fresh();
    }
}
