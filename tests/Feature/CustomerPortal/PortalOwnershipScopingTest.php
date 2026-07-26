<?php

namespace Tests\Feature\CustomerPortal;

use App\Models\Configurations\Setting;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerAccount;
use App\Models\Customers\Invoice;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Models\ProductManagement\Product;
use App\Models\Customers\Receipt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 0 — Customer Portal broken-access-control remediation.
 *
 * Every id-accepting portal endpoint must resolve its record through the
 * AUTHENTICATED customer's own relationship (orders() morph / invoices() /
 * accounts()), never by a blind lookup on a request-supplied id. These tests
 * pin the security property: customer A, while logged in, cannot read or
 * mutate customer B's data by supplying B's id — and a legitimate owner is
 * unaffected. Mirrors the canonical ownership pattern already proven for the
 * Terms endpoint (see SignedTermsAccessTest).
 */
class PortalOwnershipScopingTest extends TestCase
{
    use RefreshDatabase;

    private Customer $a;   // the attacker / authenticated session
    private Customer $b;   // the victim
    private Product $product;
    private int $staffId;  // FK target for invoices.invoice_created_by

    protected function setUp(): void
    {
        parent::setUp();

        // Invoice/payment surfaces read the payment gateway settings.
        foreach (['payment_api_public_key', 'payment_api_key'] as $name) {
            Setting::create([
                'setting_type' => 'Payment Settings', 'value_type' => 'password',
                'setting_name' => $name, 'setting_title' => $name, 'setting_value' => 'test',
            ]);
        }

        $this->a = Customer::create([
            'first_name' => 'Alice', 'last_name' => 'Attacker',
            'email' => 'alice@example.com', 'status' => 'Active',
            'password' => Hash::make('alice-original'),
        ]);

        $this->b = Customer::create([
            'first_name' => 'Bob', 'last_name' => 'Victim',
            'email' => 'bob@example.com', 'status' => 'Active',
            'password' => Hash::make('bob-original'),
        ]);

        $this->product = Product::create([
            'product_name' => 'Scoping Test Excavator',
            'slug'         => 'scoping-test-excavator-' . uniqid(),
            'product_type' => 'Rental',
        ]);

        // invoices.invoice_created_by is a FK to users.id (unique_id and
        // employee_code auto-generate on the User model's creating hook).
        $this->staffId = User::create([
            'first_name' => 'Portal', 'last_name' => 'Staff',
            'email' => 'portal-staff@example.com', 'password' => bcrypt('password'),
        ])->id;
    }

    // ── Fixtures (production-shaped) ─────────────────────────────────────

    /** Order created the way web checkout creates it — through the morph. */
    private function makeOrder(Customer $customer, array $overrides = []): Order
    {
        static $n = 7000;
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
            'price'        => 100, 'quantity' => 1, 'total' => 100,
            'product_data' => [
                'product_type' => 'Rental', 'product_variant' => 'daily',
                'product_rental_items_prices' => [], 'product_option_items' => [],
            ],
        ]);

        return $order;
    }

    private function makeInvoice(Customer $customer): Invoice
    {
        static $n = 8000;
        $n++;

        return Invoice::create([
            'customer_id'        => $customer->id,
            'invoice_number'     => (string) $n,
            'invoice_date'       => now(),
            'invoice_created_by' => $this->staffId,
            'total'              => 100,
        ]);
    }

    private function makeAccount(Customer $customer): CustomerAccount
    {
        return CustomerAccount::create([
            'customer_id' => $customer->id,
            'type'        => 'payment',
            'amount'      => 100,
            'balance'     => 0,
            'date'        => now(),
        ]);
    }

    // ── SEC-1 · Password update (account takeover) ───────────────────────

    public function test_password_update_ignores_body_customer_id_and_targets_session_customer(): void
    {
        $this->actingAs($this->a, 'customer')
            ->postJson(route('front.customer.dashboard.password.update'), [
                'customer_id'           => $this->b->id, // malicious — must be ignored
                'password'              => 'new-secret',
                'password_confirmation' => 'new-secret',
            ])
            ->assertOk();

        // Victim's password is untouched…
        $this->assertTrue(Hash::check('bob-original', $this->b->fresh()->password));
        // …and the change landed on the authenticated customer instead.
        $this->assertTrue(Hash::check('new-secret', $this->a->fresh()->password));
    }

    public function test_password_update_requires_authentication(): void
    {
        $this->post(route('front.customer.dashboard.password.update'), [
            'password' => 'x', 'password_confirmation' => 'x',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('bob-original', $this->b->fresh()->password));
    }

    // ── SEC-3 · Ledger transaction PDF (financial IDOR) ──────────────────

    public function test_ledger_pdf_download_rejects_another_customers_transaction(): void
    {
        $foreign = $this->makeAccount($this->b);

        $this->actingAs($this->a, 'customer')
            ->get(route('front.customer.dashboard.download', $foreign->id))
            ->assertNotFound();
    }

    public function test_ledger_pdf_download_requires_authentication(): void
    {
        $own = $this->makeAccount($this->a);
        $this->get(route('front.customer.dashboard.download', $own->id))->assertRedirect();
    }

    // ── SEC-4 · Profile / PII overwrite ──────────────────────────────────

    public function test_profile_update_ignores_body_unique_id_and_targets_session_customer(): void
    {
        // POST shares the /customer/dashboard URI with the GET index route;
        // route('...index') yields the correct (domain-scoped) URL to POST to.
        $this->actingAs($this->a, 'customer')
            ->post(route('front.customer.dashboard.index'), [
                'unique_id' => $this->b->unique_id, // malicious — must be ignored
                'email'     => 'hijacked@example.com',
                'first_name' => 'Alice',
                'last_name'  => 'Attacker',
            ])
            ->assertRedirect();

        // Victim's email is untouched…
        $this->assertSame('bob@example.com', $this->b->fresh()->email);
        // …and the edit landed on the authenticated customer.
        $this->assertSame('hijacked@example.com', $this->a->fresh()->email);
    }

    // ── SEC-5 · Tax-document overwrite / destruction ─────────────────────

    public function test_tax_document_upload_ignores_body_customer_id(): void
    {
        Storage::fake('public');

        $this->actingAs($this->a, 'customer')
            ->post(route('front.customer.dashboard.taxdoc.upload'), [
                'customer_id'       => $this->b->id, // malicious — must be ignored
                'tax_document_type' => 'W-9',
                'tax_document'      => UploadedFile::fake()->create('w9.pdf', 20, 'application/pdf'),
            ]);

        // Regardless of the upload's own outcome, the victim's tax document
        // record must never be touched by another customer's request.
        $this->assertNull($this->b->fresh()->tax_document_media_id);
    }

    // ── SEC-6 · Invoice disclosure (view + PDF) ──────────────────────────

    public function test_invoice_view_rejects_another_customers_invoice(): void
    {
        $foreign = $this->makeInvoice($this->b);

        $this->actingAs($this->a, 'customer')
            ->get(route('front.customer.dashboard.invoice.view', $foreign->unique_id))
            ->assertNotFound();
    }

    public function test_invoice_pdf_download_rejects_another_customers_invoice(): void
    {
        $foreign = $this->makeInvoice($this->b);

        $this->actingAs($this->a, 'customer')
            ->get(route('front.customer.dashboard.invoice.download', $foreign->unique_id))
            ->assertNotFound();
    }

    // ── SEC-7 · Order disclosure (signatures, damage logs, notes) ────────

    public function test_order_view_rejects_another_customers_order(): void
    {
        $foreign = $this->makeOrder($this->b);

        $this->actingAs($this->a, 'customer')
            ->get(route('front.customer.dashboard.order.view', $foreign->unique_id))
            ->assertNotFound();
    }

    // Owner-still-works coverage for the mutation endpoints is asserted
    // directly in the password (A's hash changes) and profile (A's email
    // changes) tests. The order/invoice GET owner-render is intentionally
    // not asserted here: those admin-derived print/detail blades have
    // pre-existing data dependencies unrelated to ownership scoping, so a
    // render assertion would test blade completeness, not this fix.

    // ── SEC-8 · Receipt download + email trigger ─────────────────────────

    public function test_receipt_download_rejects_another_customers_order(): void
    {
        $foreign = $this->makeOrder($this->b);

        $this->actingAs($this->a, 'customer')
            ->get(route('front.customer.dashboard.order.receipt-download', $foreign->unique_id))
            ->assertNotFound();

        // The disclosure-blocking fix must also prevent the write side-effect
        // (ReceiptService::getOrCreateReceipt) from ever running for a foreign order.
        $this->assertDatabaseMissing('receipts', ['order_id' => $foreign->id]);
    }

    public function test_receipt_email_does_not_fire_for_another_customers_order(): void
    {
        $foreign = $this->makeOrder($this->b);

        // This controller catches the not-found internally and redirects back
        // with a flash error; the security property is that no receipt is
        // created and no email event fires for a foreign order.
        $this->actingAs($this->a, 'customer')
            ->get(route('front.customer.dashboard.order.receipt-email', $foreign->unique_id))
            ->assertRedirect();

        $this->assertDatabaseMissing('receipts', ['order_id' => $foreign->id]);
    }

    // ── SEC-2 · Invoice payment write path ───────────────────────────────

    public function test_invoice_payment_cannot_post_to_another_customers_invoice(): void
    {
        $foreign = $this->makeInvoice($this->b);

        $this->actingAs($this->a, 'customer')
            ->post(route('front.customer.dashboard.invoice.paymentstore'), [
                'customer_id' => $this->b->id,          // malicious — must be ignored
                'invoice_id'  => $foreign->invoice_number, // B's invoice
                'amount'      => '50.00',
            ])
            ->assertRedirect(); // resolved before any charge → not-found → rollback → back

        // No ledger row was written and the victim's invoice was not mutated.
        $this->assertSame(0, CustomerAccount::count());
        $this->assertNull($foreign->fresh()->payment_number_id);
    }
}
