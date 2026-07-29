<?php

namespace Tests\Feature\Orders;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Order Management filter cleanup (2026-07-23):
 *  - Payment Status dropdown renamed default "All Payments" → "Payment Status",
 *    switched from all 14 enum cases to OrderPaymentStatus::canonical() (7),
 *    and alphabetized. Obsolete/legacy/system-only values (Account, the 5
 *    Invoice*, Superseded) are no longer offered.
 *  - Payment Type dropdown alphabetized A-Z by label (default stays first).
 *  - "Extension Charge" exposed as a pinned Product-filter sentinel that
 *    isolates extension child orders (Order::scopeExtensionChildren).
 */
class OrderFilterCleanupTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'Filter', 'last_name' => 'Cleanup',
            'email' => 'filter-cleanup@example.com', 'status' => 'Active',
        ]);

        $this->actingAs(User::create([
            'first_name' => 'Filter', 'last_name' => 'Clerk',
            'email' => 'filter-cleanup-clerk@example.com', 'status' => 'Active',
        ]));
    }

    private function order(string $number, array $overrides = []): Order
    {
        return Order::create(array_merge([
            'order_number'  => $number,
            'order_date'    => now()->toDateString(),
            'customer_id'   => $this->customer->id,
            'customer_name' => 'Filter Cleanup',
            'grand_total'   => 500,
        ], $overrides));
    }

    private function ajaxGet(array $query = [])
    {
        return $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('admin.order-management.orders.index', $query));
    }

    private function pageGet()
    {
        return $this->get(route('admin.order-management.orders.index'));
    }

    // ── Part 3 + Part 1 + Part 4: Payment Status dropdown ────────────────

    public function test_payment_status_default_is_renamed_and_only_canonical_statuses_render_sorted(): void
    {
        $res = $this->pageGet()->assertOk();

        // Part 3 — renamed default, kept first.
        $res->assertSee('Payment Status');
        $res->assertDontSee('>All Payments<', false);

        // Part 1 + 4 — canonical 7, alphabetized by label, default first.
        $res->assertSeeInOrder([
            'Payment Status',        // default (first)
            'Failed',
            'Paid in Full',
            'Partially Paid',
            'Partially Refunded',
            'Pending',
            'Refunded',
            'Voided',
        ]);
    }

    public function test_payment_status_dropdown_omits_obsolete_and_legacy_values(): void
    {
        $res = $this->pageGet()->assertOk();

        // The 5 legacy Invoice* labels + system-only Superseded are gone.
        $res->assertDontSee('Paid by CC on File');       // Invoice Card
        $res->assertDontSee('Paid by Cash');             // Invoice Cash
        $res->assertDontSee('Paid by Direct Bank');      // Invoice Online
        $res->assertDontSee('Paid by Check');            // Invoice Cheque
        $res->assertDontSee('Other Invoice Payment');    // Invoice Other
        $res->assertDontSee('Superseded (Paid via Other Method)');

        // None of the removed option VALUES are present either.
        foreach (['Invoice Card', 'Invoice Cash', 'Invoice Online', 'Invoice Cheque', 'Invoice Other', 'Superseded'] as $removed) {
            $res->assertDontSee('value="' . $removed . '"', false);
        }
    }

    // ── Part 1: Payment Type dropdown ────────────────────────────────────

    public function test_payment_type_dropdown_is_alphabetized_with_default_first(): void
    {
        $res = $this->pageGet()->assertOk();

        // Default first, then labels A-Z (COD's "Pay on Delivery" sorts to P).
        $res->assertSeeInOrder([
            'All Payment Types',
            'Cash',
            'Check',
            'Credit / Debit Card',
            'Gift Card',
            'Pay on Delivery',
            'Store Credit',
            'Tap to Pay',
            'Zelle / Venmo',
        ]);
    }

    // ── Part 2: Extension Charge product filter ──────────────────────────

    public function test_extension_charge_option_is_present_and_pinned_in_product_filter(): void
    {
        $res = $this->pageGet()->assertOk();

        $res->assertSee('value="extension"', false);
        $res->assertSee('Order Enhancement');
        // Pinned data hook the shared JS reads to keep it atop the list.
        $res->assertSee('data-pinned-products', false);
    }

    public function test_extension_filter_isolates_extension_child_orders(): void
    {
        // Parent + its extension child (order_number = parent + '-A').
        $parent = $this->order('OFCPARENT');
        $child  = $this->order('OFCPARENT-A', ['reference_order_number' => 'OFCPARENT']);
        // A plain, non-extension order.
        $plain  = $this->order('OFCPLAIN');

        $html = $this->ajaxGet(['product' => 'extension'])->assertOk()->json('html');

        $this->assertStringContainsString('OFCPARENT-A', $html, 'extension child must appear');
        $this->assertStringNotContainsString('OFCPLAIN', $html, 'a non-extension order must not appear');
    }

    public function test_extension_charge_plus_pending_shows_only_unpaid_extensions(): void
    {
        // The mission's headline workflow: Product=Extension Charge + Payment
        // Status=Pending → every extension charge not yet paid.
        // (reference_order_number is a FK to orders.order_number — parents first.)
        $this->order('OFCUNPAID');
        $this->order('OFCPAID');

        $unpaid = $this->order('OFCUNPAID-A', ['reference_order_number' => 'OFCUNPAID']);
        $unpaid->payments()->create([
            'payment_method' => OrderPaymentMethod::COD->value, 'payment_datetime' => now(),
            'amount' => 500, 'status' => OrderPaymentStatus::Pending->value,
        ]);

        $paid = $this->order('OFCPAID-A', ['reference_order_number' => 'OFCPAID']);
        $paid->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now(),
            'amount' => 500, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $html = $this->ajaxGet(['product' => 'extension', 'payment_status' => 'Pending'])
            ->assertOk()->json('html');

        $this->assertStringContainsString('OFCUNPAID-A', $html);
        $this->assertStringNotContainsString('OFCPAID-A', $html);
    }

    public function test_normal_product_filter_still_works_alongside_the_sentinel(): void
    {
        // A numeric product id still normalizes and filters as before — the
        // sentinel handling must not disturb the existing path.
        $res = $this->ajaxGet(['product' => '999999'])->assertOk();
        $res->assertJsonFragment(['success' => true]);
    }
}
