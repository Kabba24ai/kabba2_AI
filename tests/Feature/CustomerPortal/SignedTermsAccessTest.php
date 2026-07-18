<?php

namespace Tests\Feature\CustomerPortal;

use App\Models\Customers\Customer;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Models\ProductManagement\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Customer Portal Phase 1 — signed Terms & Conditions access.
 *
 * The portal exposes the EXISTING canonical record (orders.accepted_terms_content,
 * the frozen snapshot captured at signing) through the same view the admin order
 * screen opens. The Terms icon appears ONLY when a signed document exists; the
 * viewer route is scoped to the authenticated customer's own orders.
 */
class SignedTermsAccessTest extends TestCase
{
    use RefreshDatabase;

    private const SIGNED_MARKER = 'HISTORICAL-SIGNED-AGREEMENT-MARKER-2026';

    private Customer $customer;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        // Dashboard invoice tab requires the payment gateway settings
        foreach (['payment_api_public_key', 'payment_api_key'] as $name) {
            \App\Models\Configurations\Setting::create([
                'setting_type' => 'Payment Settings', 'value_type' => 'password',
                'setting_name' => $name, 'setting_title' => $name, 'setting_value' => 'test',
            ]);
        }

        $this->customer = Customer::create([
            'first_name' => 'Portal', 'last_name' => 'Customer',
            'email' => 'portal-customer@example.com', 'status' => 'Active',
        ]);

        $this->product = Product::create([
            'product_name' => 'Portal Test Excavator',
            'slug'         => 'portal-test-excavator-' . uniqid(),
            'product_type' => 'Rental',
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    /**
     * Production-shaped portal order: created exactly the way web checkout
     * creates it — through the Customer::orders() morph relationship
     * (created_by_type/id = the customer) WITH customer_id also set
     * (Front\Checkout\PostController does both).
     */
    private function makeOrder(Customer $customer, array $overrides = []): Order
    {
        static $n = 6000;
        $n++;

        $order = $customer->orders()->create(array_merge([
            'order_number'  => (string) $n,
            'customer_id'   => $customer->id,
            'customer_name' => $customer->first_name . ' ' . $customer->last_name,
        ], $overrides));

        OrderProduct::create([
            'order_id'     => $order->id,
            'product_id'   => $this->product->id,
            'product_name' => $this->product->product_name,
            'price'        => 100,
            'quantity'     => 1,
            'total'        => 100,
            'product_data' => [
                'product_type'                => 'Rental',
                'product_variant'             => 'daily',
                'product_rental_items_prices' => [],
                'product_option_items'        => [],
            ],
        ]);

        return $order;
    }

    private function makeSignedOrder(Customer $customer): Order
    {
        return $this->makeOrder($customer, [
            'terms_status'           => 'Accepted',
            'accepted_terms_content' => '<p>' . self::SIGNED_MARKER . '</p>',
            'terms_accepted_at'      => now()->subDay(),
            'pending_terms_content'  => '<p>original pending copy</p>',
        ]);
    }

    private function dashboard(): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->customer, 'customer')
            ->get(route('front.customer.dashboard.index'));
    }

    private function termsUrl(Order $order): string
    {
        return route('front.customer.dashboard.order.terms', $order->unique_id);
    }

    // ── Recent Orders column ─────────────────────────────────────────────

    public function test_signed_order_shows_the_terms_icon(): void
    {
        $order = $this->makeSignedOrder($this->customer);

        $this->dashboard()
            ->assertOk()
            ->assertSee('View Signed Terms &amp; Conditions', false)
            ->assertSee($this->termsUrl($order), false);
    }

    public function test_unsigned_order_shows_an_empty_cell(): void
    {
        $order = $this->makeOrder($this->customer); // terms_status defaults / no content

        $this->dashboard()
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertDontSee('View Signed Terms', false)
            ->assertDontSee($this->termsUrl($order), false)
            // No placeholder text of any kind
            ->assertDontSee('Not Signed');
    }

    public function test_legacy_order_without_signature_shows_nothing(): void
    {
        // Legacy orders predate the signing flow: default Pending status and
        // no accepted content (the column is non-nullable — no null state exists)
        $order = $this->makeOrder($this->customer, [
            'terms_status'           => 'Pending',
            'accepted_terms_content' => null,
        ]);

        $this->dashboard()
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertDontSee('View Signed Terms', false);
    }

    public function test_declined_and_exempt_orders_show_nothing(): void
    {
        $this->makeOrder($this->customer, ['terms_status' => 'Declined']);
        $this->makeOrder($this->customer, ['terms_status' => 'Exempt']);

        $this->dashboard()
            ->assertOk()
            ->assertDontSee('View Signed Terms', false);
    }

    // ── Viewer route ─────────────────────────────────────────────────────

    public function test_icon_target_renders_the_exact_historical_agreement(): void
    {
        $order = $this->makeSignedOrder($this->customer);

        $this->actingAs($this->customer, 'customer')
            ->get($this->termsUrl($order))
            ->assertOk()
            // The frozen snapshot — not the pending copy, not regenerated terms
            ->assertSee(self::SIGNED_MARKER)
            ->assertDontSee('original pending copy')
            ->assertSee('Order ID ' . $order->order_number);
    }

    public function test_customer_cannot_open_another_customers_agreement(): void
    {
        $other = Customer::create([
            'first_name' => 'Other', 'last_name' => 'Customer',
            'email' => 'other-customer@example.com', 'status' => 'Active',
        ]);
        $foreignOrder = $this->makeSignedOrder($other);

        // Direct URL with a REAL unique_id belonging to someone else → 404
        $this->actingAs($this->customer, 'customer')
            ->get($this->termsUrl($foreignOrder))
            ->assertNotFound();
    }

    // ── Canonical ownership = Customer::orders() (created_by morph) ──────
    // The Terms endpoint resolves through the SAME relationship that decides
    // whether an order appears in the customer's portal at all — never a
    // second interpretation of ownership.

    public function test_matching_customer_id_without_the_portal_relationship_is_denied(): void
    {
        // REAL production state: admin-created orders (e.g. extension child
        // orders from Extension\StoreController) carry the customer_id but are
        // NOT created through Customer::orders() — the portal never lists
        // them, so the Terms endpoint must not serve them either.
        $order = Order::create([
            'order_number'           => '9500',
            'customer_id'            => $this->customer->id, // matches!
            'customer_name'          => 'Portal Customer',
            'terms_status'           => 'Accepted',
            'accepted_terms_content' => '<p>' . self::SIGNED_MARKER . '</p>',
        ]);
        $this->assertNull($order->fresh()->created_by_id, 'fixture must be admin-shaped: no portal morph');

        // Not listed in the portal…
        $this->dashboard()->assertOk()->assertDontSee($this->termsUrl($order), false);

        // …and not reachable by direct URL
        $this->actingAs($this->customer, 'customer')
            ->get($this->termsUrl($order))
            ->assertNotFound();
    }

    public function test_portal_relationship_governs_even_when_customer_id_diverges(): void
    {
        // Defensive canonical-definition check: if the morph and customer_id
        // ever disagree, the portal listing follows the morph — so the Terms
        // endpoint does too. (Web checkout always sets both identically; this
        // pins the rule, not a common state.)
        $other = Customer::create([
            'first_name' => 'Divergent', 'last_name' => 'Record',
            'email' => 'divergent-record@example.com', 'status' => 'Active',
        ]);
        $order = $this->makeSignedOrder($this->customer);
        DB::table('orders')->where('id', $order->id)->update(['customer_id' => $other->id]);

        // Still listed in this customer's portal (morph-owned) → still opens
        $this->actingAs($this->customer, 'customer')
            ->get($this->termsUrl($order))
            ->assertOk()
            ->assertSee(self::SIGNED_MARKER);

        // The customer_id holder does NOT get access through the portal route
        $this->actingAs($other, 'customer')
            ->get($this->termsUrl($order))
            ->assertNotFound();
    }

    public function test_unsigned_order_terms_url_is_not_exposed(): void
    {
        $order = $this->makeOrder($this->customer);

        $this->actingAs($this->customer, 'customer')
            ->get($this->termsUrl($order))
            ->assertNotFound();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $order = $this->makeSignedOrder($this->customer);

        $this->get($this->termsUrl($order))->assertRedirect();
    }

    // ── Existing behavior preserved ──────────────────────────────────────

    public function test_public_signing_route_still_serves_the_agreement_for_admin(): void
    {
        // The admin order screen opens signed terms through this public route
        // (SMS-link signing flow) — it must keep working exactly as before.
        $order = $this->makeSignedOrder($this->customer);

        $this->get(route('front.terms-and-conditions.index', $order->unique_id))
            ->assertOk()
            ->assertSee(self::SIGNED_MARKER);
    }

    public function test_terms_column_adds_no_queries(): void
    {
        // The Terms cell per row is hasSignedTerms() + route(): both must read
        // only columns already on the loaded order rows — zero queries across
        // any number of orders (no N+1 introduced by this feature).
        $this->makeSignedOrder($this->customer);
        $this->makeSignedOrder($this->customer);
        $this->makeOrder($this->customer); // unsigned

        $orders = $this->customer->orders()->get(); // as the dashboard reads them

        DB::enableQueryLog();
        foreach ($orders as $order) {
            if ($order->hasSignedTerms()) {
                route('front.customer.dashboard.order.terms', $order->unique_id);
            }
        }
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertCount(3, $orders);
        $this->assertCount(0, $queries, 'Terms cell must not query per row');
    }
}
