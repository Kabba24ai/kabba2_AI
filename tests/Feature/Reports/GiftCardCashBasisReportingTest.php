<?php

namespace Tests\Feature\Reports;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Models\GiftCards\GiftCard;
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductCategory;
use App\Services\GiftCards\GiftCardPermissions;
use App\Services\GiftCards\GiftCardService;
use Database\Seeders\Iam\GiftCardPermissionSeeder;
use App\Services\Reports\PaymentReconciliationLedger;
use App\Services\Reports\SalesReportEngineV2;
use App\Services\Reports\SalesTaxReportEngine;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Gift cards on the cash-basis reporting surfaces.
 *
 * ── THE DEFECT ────────────────────────────────────────────────────────────
 *
 * A gift card is the first tender in this system for which CASH COLLECTED and
 * REVENUE RECOGNISED are different numbers. Every other tender moves money at
 * the moment it is applied; a gift card moved its money earlier — when it was
 * bought — possibly in a different period, possibly from a different person.
 *
 * `CollectedRevenueQuery` derives revenue FROM allocated payment amounts, and
 * `SalesReportEngineV2` then computes
 *
 *     gross_collections = gross_sales + tax_collected − discounts
 *
 * so cash and revenue are the same figure, computed once. That is correct for
 * every tender that is real money. For a gift card it double-counts: the cash
 * was already collected and reported when the card was funded.
 *
 * ── WHY THE STORE CREDIT FIX DOES NOT APPLY ───────────────────────────────
 *
 * Store Credit is excluded from the qualifying-payment universe because it is
 * a PRE-TAX DISCOUNT — by the time reporting runs, the order's subtotal, tax
 * and grand total are already lower, so a StoreCredit payment row duplicates a
 * reduction already recorded.
 *
 * A gift card reduces NOTHING. The order is fully priced and fully taxed, and
 * the revenue is real. Excluding the redemption row the same way would delete
 * that revenue — and the sales tax owed on it — from the reports. See
 * `test_excluding_the_redemption_row_would_destroy_revenue_and_tax`, which
 * pins that specifically so nobody "fixes" this with a `!=` predicate.
 *
 * The fix is a new TERM in the collections identity, never a filter:
 *
 *     gross_collections = gross_sales + tax_collected − discounts − gift_card_redeemed
 *
 * ── STATE OF THESE TESTS ──────────────────────────────────────────────────
 *
 * Scenarios 2 and 3 need nothing that does not already exist — `GiftCard` has
 * been a selectable payment method since 2026_07_13_193529 — so they fail TODAY
 * with the exact wrong figures, which is the point of writing them first.
 * Scenarios 1 and 4 additionally need the Phase 1 funding record and fail until
 * it exists.
 *
 * @see docs/gift-cards/REPORTING_TREATMENT.md
 */
class GiftCardCashBasisReportingTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'Gift', 'last_name' => 'Holder',
            'email' => 'gift-holder@example.com', 'status' => 'Active',
        ]);

        (new GiftCardPermissionSeeder())->run();

        $clerk = User::create([
            'first_name' => 'Gift', 'last_name' => 'Clerk',
            'email' => 'gift-clerk@example.com', 'password' => bcrypt('secret'), 'status' => 'Active',
        ]);

        foreach (array_keys(GiftCardPermissions::all()) as $permission) {
            $clerk->givePermissionTo($permission);
        }

        $this->actingAs($clerk->fresh());

        $category = ProductCategory::create(['title' => 'Gift Card Reporting Category']);
        $product = Product::create([
            'product_name' => 'Gift Card Reporting Product',
            'slug' => 'gift-card-reporting-product-'.uniqid(),
            'product_type' => 'Rental',
        ]);
        $product->categories()->attach($category->id);

        // $500 subtotal + $50 tax (10%) = $550 grand total. A REAL rental —
        // the gift card only changes how it is paid for, never what it costs
        // or what tax it owes.
        $this->order = Order::create([
            'order_number' => 'GIFT-1',
            'order_date' => '2026-06-24',
            'customer_id' => $this->customer->id,
            'customer_name' => 'Gift Holder',
            'subtotal' => 500,
            'tax_amount' => 50,
            'grand_total' => 550,
        ]);

        OrderProduct::create([
            'order_id' => $this->order->id,
            'product_id' => $product->id,
            'product_name' => 'Gift Card Reporting Product',
            'price' => 500,
            'quantity' => 1,
            'sub_total' => 500,
            'tax' => 50,
            'total' => 550,
            'product_data' => ['product_type' => 'Rental'],
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ── Scenario 2 — redemption ────────────────────────────────────────────

    /**
     * Redemption creates sales revenue and tax, but no new external cash.
     *
     * Fails today: collections report $550 of cash that nobody handed over.
     */
    public function test_redemption_recognises_revenue_and_tax_but_collects_no_new_cash(): void
    {
        $this->payWithGiftCard(550);

        $kpis = app(SalesReportEngineV2::class)->kpis($this->filters());

        // Revenue is REAL — a $500 rental happened and owes $50 of tax.
        $this->assertSame(500.0, round($kpis['gross_sales'], 2),
            'the rental revenue is real and must be recognised in full');
        $this->assertSame(50.0, round($kpis['tax_collected'], 2),
            'tax is owed on the full sale — the tender never changes the taxable basis');

        // Cash is NOT. It was collected when the card was funded.
        $this->assertSame(0.0, round($kpis['total_collected'], 2),
            'no external cash moved at redemption — it was already collected at funding');
        $this->assertSame(550.0, round($kpis['gift_card_redeemed'], 2),
            'the non-cash tender must be surfaced on its own, as overpayments already are');
    }

    /**
     * The redemption stays VISIBLE in the reconciliation ledger — an operator
     * must still see how the order was paid — but carries no settlement
     * expectation, because no processor will ever settle it.
     */
    public function test_redemption_is_auditable_in_the_ledger_with_no_settlement_expectation(): void
    {
        $this->payWithGiftCard(550);

        $ledger = app(PaymentReconciliationLedger::class)->rows($this->filters());
        $row = $ledger->firstWhere('payment_method', OrderPaymentMethod::GiftCard->value);

        $this->assertNotNull($row, 'the redemption must remain visible, not silently dropped');
        $this->assertSame(0.0, round($row->grand_total, 2),
            'a gift-card redemption has no external settlement to reconcile against');
    }

    /**
     * The load-bearing guard on the shape of the fix.
     *
     * Store Credit is excluded from the qualifying-payment universe. If anyone
     * ever applies that same treatment to Gift Card, allocation sees a $0 order
     * and the revenue AND the sales tax vanish. This test exists to fail loudly
     * the moment that happens.
     */
    public function test_excluding_the_redemption_row_would_destroy_revenue_and_tax(): void
    {
        $this->payWithGiftCard(550);

        $tax = app(SalesTaxReportEngine::class)->salesRows([
            'start_date' => '2026-06-01', 'end_date' => '2026-06-30',
        ]);

        $this->assertCount(1, $tax,
            'a gift-card-funded sale is still a taxable sale and must appear on the tax report');
        $this->assertSame(500.0, round($tax->first()->subtotal, 2));
        $this->assertSame(50.0, round($tax->first()->tax_amount, 2),
            'excluding gift cards from the tax report would under-remit tax genuinely owed');
    }

    // ── Scenario 3 — mixed tender ──────────────────────────────────────────

    /**
     * $500 gift card + $50 cash on a $550 order.
     *
     * Revenue and tax stay whole; collections contain the $50 only.
     */
    public function test_mixed_gift_card_and_cash_keeps_revenue_whole_but_collects_only_the_cash(): void
    {
        $this->payWithGiftCard(500);
        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => '2026-06-24 11:00:00',
            'amount' => 50,
            'status' => OrderPaymentStatus::Paid->value,
        ]);

        $kpis = app(SalesReportEngineV2::class)->kpis($this->filters());

        $this->assertSame(500.0, round($kpis['gross_sales'], 2),
            'the full rental is revenue regardless of how it was tendered');
        $this->assertSame(50.0, round($kpis['tax_collected'], 2));

        $this->assertSame(50.0, round($kpis['total_collected'], 2),
            'only the cash portion is money the business actually received today');
        $this->assertSame(500.0, round($kpis['gift_card_redeemed'], 2));

        // The invariant every collected-revenue surface is built on.
        $ledger = app(PaymentReconciliationLedger::class)->rows($this->filters());
        $this->assertSame(
            round($kpis['total_collected'], 2),
            round($ledger->sum('grand_total'), 2),
            'ledger must still reconcile to the KPI engine once gift cards exist'
        );
    }

    // ── Scenario 1 — purchased issuance (needs Phase 1 funding record) ─────

    /**
     * Buying a $500 card takes $500 of real money and sells nothing.
     *
     * Cash and liability, but no revenue and no tax — the sale of stored value
     * is not a sale of goods.
     */
    public function test_purchased_issuance_creates_cash_and_liability_but_no_revenue_or_tax(): void
    {
        $this->purchaseGiftCard(500, '2026-06-10 09:00:00');

        $kpis = app(SalesReportEngineV2::class)->kpis(
            $this->filters('2026-06-01', '2026-06-30')
        );

        $this->assertSame(0.0, round($kpis['gross_sales'], 2),
            'issuing stored value sells no product — it can never be sales revenue');
        $this->assertSame(0.0, round($kpis['tax_collected'], 2),
            'no sales tax is due on the sale of a gift card');

        $this->assertSame(500.0, round($kpis['total_collected'], 2),
            '$500 of real money was received and must be reported as collected');
        $this->assertSame(500.0, round($kpis['gift_card_liability_issued'], 2));
        $this->assertSame(500.0, round($kpis['gift_card_liability_outstanding'], 2));

        // Real money, real processor transaction — it must reconcile.
        $ledger = app(PaymentReconciliationLedger::class)->rows(
            $this->filters('2026-06-01', '2026-06-30')
        );
        $funding = $ledger->where('stream', 'gift_card')->values();

        $this->assertCount(1, $funding, 'funding cash belongs to its own stream');
        $this->assertSame(500.0, round($funding->first()->grand_total, 2));
        $this->assertSame(0.0, round($funding->first()->tax_amount, 2));
        $this->assertSame(0.0, round($funding->first()->base_amount, 2),
            'funding carries cash only — never revenue');
    }

    // ── Scenario 4 — the whole life of one card ───────────────────────────

    /**
     * Funding and redemption together: each figure counted exactly once.
     *
     * $500 in at issuance, $500 of revenue at redemption, $50 cash for the
     * remainder. Collections total $550 across both periods — NOT $1,050.
     */
    public function test_funding_and_redemption_together_never_double_count(): void
    {
        // ONE card, funded and spent inside the same window — so both events
        // land in the same figures and any double-count has nowhere to hide.
        $card = $this->purchaseGiftCard(500, '2026-06-10 09:00:00');
        $this->redeem($card, 500);

        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => '2026-06-24 11:00:00',
            'amount' => 50,
            'status' => OrderPaymentStatus::Paid->value,
        ]);

        $kpis = app(SalesReportEngineV2::class)->kpis(
            $this->filters('2026-06-01', '2026-06-30')
        );

        // $500 (funding) + $50 (cash remainder). The redeemed $500 is NOT cash.
        $this->assertSame(550.0, round($kpis['total_collected'], 2),
            '$550 of real money entered the business across the card’s whole life');

        // The rental, once.
        $this->assertSame(500.0, round($kpis['gross_sales'], 2));
        $this->assertSame(50.0, round($kpis['tax_collected'], 2));

        // Liability opened and fully drawn down.
        $this->assertSame(500.0, round($kpis['gift_card_liability_issued'], 2));
        $this->assertSame(500.0, round($kpis['gift_card_liability_redeemed'], 2));
        $this->assertSame(0.0, round($kpis['gift_card_liability_outstanding'], 2));

        $ledger = app(PaymentReconciliationLedger::class)->rows(
            $this->filters('2026-06-01', '2026-06-30')
        );
        $this->assertSame(
            round($kpis['total_collected'], 2),
            round($ledger->sum('grand_total'), 2),
            'the ledger identity must hold across funding and redemption together'
        );
    }

    // ── Granted cards — merchant-funded promotional value ─────────────────

    /**
     * Granting takes no money and sells nothing.
     *
     * Unlike a purchased card there is no funding row, so no cash and no
     * liability — the business owes nobody anything. What it has done is commit
     * to giving something away, which is an EXPENSE, tracked separately.
     */
    public function test_granted_issuance_creates_no_cash_and_no_liability(): void
    {
        $this->grantGiftCard(200);

        $kpis = app(SalesReportEngineV2::class)->kpis($this->filters());

        $this->assertSame(0.0, round($kpis['total_collected'], 2),
            'nobody paid anything — there is no cash to report');
        $this->assertSame(0.0, round($kpis['gross_sales'], 2));
        $this->assertSame(0.0, round($kpis['tax_collected'], 2));

        // Promotional value, never liability.
        $this->assertSame(200.0, round($kpis['promotional_value_issued'], 2));
        $this->assertSame(0.0, round($kpis['gift_card_liability_issued'], 2),
            'a granted card is not money the business owes');
        $this->assertSame(0.0, round($kpis['gift_card_liability_outstanding'], 2));

        // Nothing to settle — no processor was ever involved.
        $ledger = app(PaymentReconciliationLedger::class)->rows($this->filters());
        $this->assertCount(0, $ledger->where('stream', 'gift_card'),
            'a granted card has no funding cash and must not appear in Stream E');
    }

    /**
     * Redeeming a granted card is a REAL sale, fully taxed.
     *
     * The customer received goods; the state is owed its tax whether or not the
     * business charged for them. What differs from a purchased card is only the
     * funding: this draws down promotional value, never liability.
     */
    public function test_granted_redemption_is_fully_taxed_revenue_but_not_liability(): void
    {
        $card = $this->grantGiftCard(550);
        $this->redeem($card, 550);

        $kpis = app(SalesReportEngineV2::class)->kpis($this->filters());

        // A real rental happened, and it owes real tax.
        $this->assertSame(500.0, round($kpis['gross_sales'], 2));
        $this->assertSame(50.0, round($kpis['tax_collected'], 2),
            'tender never changes the taxable basis — a comped rental is still taxed');

        // No cash, at issuance or redemption.
        $this->assertSame(0.0, round($kpis['total_collected'], 2));

        // Classified as promotion, and kept out of liability entirely.
        $this->assertSame(550.0, round($kpis['promotional_value_redeemed'], 2));
        $this->assertSame(0.0, round($kpis['gift_card_liability_redeemed'], 2),
            'granted redemption must never draw down purchased-card liability');
    }

    /**
     * The distinction survives when both kinds are spent in the same period.
     *
     * This is the case a single "gift cards redeemed" total would destroy —
     * money the business OWES and money it CHOSE TO GIVE AWAY presented as one
     * obligation.
     */
    public function test_purchased_and_granted_redemption_are_never_merged(): void
    {
        $purchased = $this->purchaseGiftCard(300, '2026-05-15 09:00:00');
        $granted = $this->grantGiftCard(250);

        $this->redeem($purchased, 300);
        $this->redeem($granted, 250);

        $kpis = app(SalesReportEngineV2::class)->kpis($this->filters());

        // Together they cover the whole order.
        $this->assertSame(550.0, round($kpis['gift_card_redeemed'], 2));

        // Apart, they are correctly attributed.
        $this->assertSame(300.0, round($kpis['gift_card_liability_redeemed'], 2));
        $this->assertSame(250.0, round($kpis['promotional_value_redeemed'], 2));

        // Revenue and tax are whole; no cash arrived in June.
        $this->assertSame(500.0, round($kpis['gross_sales'], 2));
        $this->assertSame(50.0, round($kpis['tax_collected'], 2));
        $this->assertSame(0.0, round($kpis['total_collected'], 2));

        // The liability opened in May is fully drawn down; promotion is not
        // part of that figure at all.
        $this->assertSame(0.0, round($kpis['gift_card_liability_outstanding'], 2));
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function filters(string $start = '2026-06-01', string $end = '2026-06-30'): array
    {
        return [
            'date_range' => 'custom',
            'start_date' => $start,
            'end_date' => $end,
            'payment_status' => 'paid',
        ];
    }

    /**
     * Redeem against the order.
     *
     * The card is funded in MAY by default — deliberately outside the June
     * reporting window. That is the truest form of the problem: the cash and
     * the revenue belong to different periods, so a June report that counts the
     * redemption as cash is claiming money that was banked last month.
     */
    private function payWithGiftCard(float $amount, string $fundedAt = '2026-05-15 09:00:00'): GiftCard
    {
        $card = $this->purchaseGiftCard($amount, $fundedAt);
        $this->redeem($card, $amount);

        return $card;
    }

    /**
     * Buy a card. Standalone — never an order, never an `order_product`, never
     * a catalogue item, so it cannot reach the sales or tax reports through the
     * `order_products` requirement in
     * CollectedRevenueQuery::qualifyingPayments(). The tender is a REAL method
     * (Card here); never `GiftCard`, which means redemption, not funding.
     */
    private function purchaseGiftCard(float $amount, string $at): GiftCard
    {
        return GiftCardService::purchase(
            amount: $amount,
            fundingMethod: OrderPaymentMethod::Card,
            purchaserCustomerId: $this->customer->id,
            recipientName: 'Daniel Reyes',
            senderName: 'The Whitfield Family',
            fundingTransactionId: 'AUTHNET-'.uniqid(),
            fundedAt: $at,
            idempotencyKey: 'gc-fund-'.uniqid(),
        );
    }

    /**
     * Give a card away. No funding, no cash, no liability — merchant-funded
     * promotional value, distinguishable from purchased value everywhere.
     */
    private function grantGiftCard(float $amount): GiftCard
    {
        Carbon::setTestNow('2026-06-12 09:00:00');

        $card = GiftCardService::grant(
            amount: $amount,
            reasonCode: 'SERVICE_RECOVERY',
            reasonCategory: 'service_recovery',
            note: 'Equipment arrived late.',
            recipientCustomerId: $this->customer->id,
            idempotencyKey: 'gc-grant-'.uniqid(),
        );

        Carbon::setTestNow();

        return $card;
    }

    /** Spend the card against the order, dated inside the June window. */
    private function redeem(GiftCard $card, float $amount): void
    {
        Carbon::setTestNow('2026-06-24 10:00:00');

        GiftCardService::redeem(
            card: $card,
            order: $this->order,
            amount: $amount,
            idempotencyKey: 'gc-redeem-'.uniqid(),
        );

        Carbon::setTestNow();
    }
}
