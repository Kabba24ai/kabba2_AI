<?php

namespace Tests\Feature\GiftCards;

use App\Enums\GiftCards\GiftCardTransactionType;
use App\Enums\Orders\OrderPaymentMethod;
use App\Models\Customers\Customer;
use App\Models\GiftCards\GiftCard;
use App\Models\GiftCards\GiftCardTransaction;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Models\ProductManagement\Product;
use App\Services\GiftCards\GiftCardPermissions;
use App\Services\GiftCards\GiftCardService;
use Database\Seeders\Iam\GiftCardPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Gift cards meeting the order payment workflow.
 *
 * ── WHAT THIS PROTECTS ────────────────────────────────────────────────────
 *
 * Before this integration, selecting "Gift Card" at the payment screen wrote
 * an ordinary payment row with the card number typed into the notes field.
 * That row looked exactly like cash to every downstream report and drew down
 * no card at all — the money was counted twice and the card stayed full.
 *
 * These tests assert the two halves of the fix: the payment now moves the
 * LEDGER, and the resulting payment row is LINKED to the movement that
 * authorised it. That link is what lets reporting classify the payment as
 * non-cash later without treating gift cards as a special case at the till.
 */
class GiftCardPaymentIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        (new GiftCardPermissionSeeder())->run();

        $this->customer = Customer::create([
            'first_name' => 'Pay', 'last_name' => 'Customer',
            'email' => 'gc-pay-'.uniqid().'@example.com', 'status' => 'Active',
        ]);

        $this->cashier = User::create([
            'first_name' => 'Till', 'last_name' => 'Cashier',
            'email' => 'gc-cashier-'.uniqid().'@example.com',
            'password' => bcrypt('secret'), 'status' => 'Active',
        ]);

        foreach (array_keys(GiftCardPermissions::all()) as $permission) {
            $this->cashier->givePermissionTo($permission);
        }

        $this->cashier = $this->cashier->fresh();
    }

    private function card(float $amount = 500): GiftCard
    {
        return GiftCardService::purchase(
            amount: $amount,
            fundingMethod: OrderPaymentMethod::Cash,
            recipientName: 'Daniel Reyes',
            actor: $this->cashier,
        );
    }

    private function order(float $grandTotal = 550): Order
    {
        $order = Order::create([
            'order_date' => now()->toDateString(),
            'customer_id' => $this->customer->id,
            'customer_name' => 'Pay Customer',
            'subtotal' => $grandTotal,
            'tax_amount' => 0,
            'grand_total' => $grandTotal,
        ]);

        $product = Product::create([
            'product_name' => 'Rental Item',
            'slug' => 'rental-item-'.uniqid(),
            'product_type' => 'Rental',
        ]);

        OrderProduct::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => 'Rental Item',
            'price' => $grandTotal,
            'quantity' => 1,
            'sub_total' => $grandTotal,
            'tax' => 0,
            'total' => $grandTotal,
            'product_data' => ['product_type' => 'Rental'],
        ]);

        return $order->fresh();
    }

    private function pay(Order $order, array $payload)
    {
        return $this->actingAs($this->cashier)->putJson(
            route('admin.order-management.orders.receive-payment', $order->unique_id),
            array_merge([
                'payment_type' => 'GiftCard',
                'responsible_person' => $this->cashier->id,
            ], $payload),
        );
    }

    // ── The happy path ────────────────────────────────────────────────────

    /**
     * The whole point: paying with a gift card must move the ledger, create
     * an ordinary payment row, and LINK the two.
     */
    public function test_paying_with_a_gift_card_debits_the_card_and_links_the_payment(): void
    {
        $card = $this->card(500);
        $order = $this->order(500);

        $this->pay($order, ['gift_card_number' => $card->card_number])
            ->assertOk()
            ->assertJson(['success' => true]);

        // The card was actually spent.
        $this->assertSame(0.0, $card->fresh()->availableBalance());

        // An ordinary payment row exists, and it settles the order like any
        // other tender.
        $payment = $order->fresh()->payments()->first();
        $this->assertNotNull($payment);
        $this->assertSame(OrderPaymentMethod::GiftCard, $payment->payment_method);
        $this->assertSame('500.00', (string) $payment->amount);
        $this->assertSame(0.0, round((float) $order->fresh()->balance_due, 2));

        // And it is linked to the ledger movement that authorised it. Without
        // this link the payment is indistinguishable from cash downstream.
        $redemption = GiftCardTransaction::where('order_payment_id', $payment->id)
            ->where('type', GiftCardTransactionType::Redemption->value)
            ->first();

        $this->assertNotNull($redemption, 'the payment must be linked to its ledger entry');
        $this->assertSame($card->id, $redemption->gift_card_id);
        $this->assertSame('-500.00', (string) $redemption->amount);
    }

    /**
     * Partial payment and mixed tender must keep working. A gift card that
     * cannot cover the order is the ordinary case, not an error.
     */
    public function test_a_gift_card_can_pay_part_of_an_order_with_cash_covering_the_rest(): void
    {
        $card = $this->card(200);
        $order = $this->order(500);

        $this->pay($order, [
            'gift_card_number' => $card->card_number,
            'partial_payment' => 1,
            'payment_amount' => 200,
        ])->assertOk();

        $this->assertSame(0.0, $card->fresh()->availableBalance());
        $this->assertSame(300.0, round((float) $order->fresh()->balance_due, 2));

        // The remaining balance settles by ordinary means, untouched by any
        // of this.
        $this->actingAs($this->cashier)->putJson(
            route('admin.order-management.orders.receive-payment', $order->unique_id),
            ['payment_type' => 'Cash', 'responsible_person' => $this->cashier->id],
        )->assertOk();

        $order->refresh();
        $this->assertSame(0.0, round((float) $order->balance_due, 2));
        $this->assertSame(2, $order->payments()->count());
    }

    // ── Attribution is not authority ──────────────────────────────────────

    /**
     * The "responsible person" dropdown says whose sale this was. It does not
     * say who is allowed to redeem a gift card — that is whoever is signed in.
     *
     * Both directions matter. Checking the named employee instead would let a
     * clerk escalate by naming a manager, AND would refuse a legitimate
     * redemption whenever the named employee happened not to hold a permission
     * they never needed. This test pins the second direction; the first is
     * pinned below.
     */
    public function test_the_named_responsible_person_need_not_hold_the_redeem_permission(): void
    {
        $card = $this->card(500);
        $order = $this->order(500);

        // A salesperson with no gift card permissions at all.
        $salesperson = User::create([
            'first_name' => 'Nora', 'last_name' => 'Sales',
            'email' => 'gc-sales-'.uniqid().'@example.com',
            'password' => bcrypt('secret'), 'status' => 'Active',
        ]);

        $this->actingAs($this->cashier)->putJson(
            route('admin.order-management.orders.receive-payment', $order->unique_id),
            [
                'payment_type' => 'GiftCard',
                'gift_card_number' => $card->card_number,
                'responsible_person' => $salesperson->id,
            ],
        )->assertOk();

        $this->assertSame(0.0, $card->fresh()->availableBalance());

        // Credited to the salesperson, authorised by the cashier.
        $this->assertSame($salesperson->id, $order->fresh()->payments()->first()->created_by_id);
    }

    /**
     * The other direction: naming a privileged colleague does not lend the
     * signed-in user their authority.
     */
    public function test_naming_a_privileged_colleague_does_not_grant_redemption_rights(): void
    {
        $card = $this->card(500);
        $order = $this->order(500);

        $unprivileged = User::create([
            'first_name' => 'Casual', 'last_name' => 'User',
            'email' => 'gc-nobody-'.uniqid().'@example.com',
            'password' => bcrypt('secret'), 'status' => 'Active',
        ]);

        $this->actingAs($unprivileged)->putJson(
            route('admin.order-management.orders.receive-payment', $order->unique_id),
            [
                'payment_type' => 'GiftCard',
                'gift_card_number' => $card->card_number,
                // The fully-permissioned cashier, named by someone who is not them.
                'responsible_person' => $this->cashier->id,
            ],
        )->assertStatus(403);

        $this->assertSame(500.0, $card->fresh()->availableBalance(), 'the card was not touched');
        $this->assertSame(0, $order->fresh()->payments()->count());
    }

    // ── Refusals ──────────────────────────────────────────────────────────

    public function test_an_unknown_card_number_is_refused_and_nothing_is_written(): void
    {
        $order = $this->order(500);

        $this->pay($order, ['gift_card_number' => 'GC-9999-9999'])
            ->assertNotFound()
            ->assertJson(['code' => 'card_not_found']);

        $this->assertSame(0, $order->fresh()->payments()->count());
    }

    /**
     * The card's balance bounds the payment. A $100 card cannot settle a $500
     * order, and the attempt must leave the order untouched.
     */
    public function test_a_card_cannot_pay_more_than_it_holds(): void
    {
        $card = $this->card(100);
        $order = $this->order(500);

        $this->pay($order, [
            'gift_card_number' => $card->card_number,
            'partial_payment' => 1,
            'payment_amount' => 300,
        ])
            ->assertStatus(422)
            ->assertJson(['code' => 'insufficient_balance']);

        $this->assertSame(100.0, $card->fresh()->availableBalance(), 'nothing was taken');
        $this->assertSame(0, $order->fresh()->payments()->count(), 'and no payment was written');
    }

    public function test_a_cancelled_card_is_refused_at_the_till(): void
    {
        $card = $this->card(500);
        GiftCardService::cancel($card, 'Issued in error.', $this->cashier);

        $order = $this->order(500);

        $this->pay($order, ['gift_card_number' => $card->card_number])
            ->assertStatus(422)
            ->assertJson(['code' => 'card_not_redeemable']);

        $this->assertSame(0, $order->fresh()->payments()->count());
    }

    public function test_the_card_number_is_required_when_gift_card_is_selected(): void
    {
        $order = $this->order(500);

        $this->pay($order, [])->assertStatus(422);

        $this->assertSame(0, $order->fresh()->payments()->count());
    }

    /**
     * A double-clicked submit must debit the card once. The client token is
     * carried into the ledger's own idempotency key, so the second attempt
     * returns the first result rather than spending the card again.
     */
    public function test_a_replayed_submission_debits_the_card_once(): void
    {
        $card = $this->card(500);
        $order = $this->order(500);

        $payload = [
            'gift_card_number' => $card->card_number,
            'idempotency_token' => 'double-click-token',
        ];

        $this->pay($order, $payload)->assertOk();
        $this->pay($order, $payload)->assertOk();

        $this->assertSame(0.0, $card->fresh()->availableBalance());
        $this->assertSame(1, $order->fresh()->payments()->count(), 'one payment, not two');
        $this->assertSame(
            1,
            GiftCardTransaction::where('gift_card_id', $card->id)
                ->where('type', GiftCardTransactionType::Redemption->value)->count(),
            'one redemption, not two',
        );
    }

    // ── Refund routing ────────────────────────────────────────────────────

    /**
     * The safe default: a gift-card-funded payment is recognised as such, so
     * the refund workflow can route its value back to the card it came from
     * instead of handing the customer cash.
     */
    public function test_a_gift_card_funded_payment_is_flagged_for_refund_to_card(): void
    {
        $card = $this->card(500);
        $order = $this->order(500);

        $this->pay($order, ['gift_card_number' => $card->card_number])->assertOk();

        $payment = $order->fresh()->payments()->first();

        $this->assertTrue(
            GiftCardService::requiresRefundToCard($payment),
            'the refund workflow must be able to see that this payment came off a card',
        );
    }

    public function test_an_ordinary_cash_payment_is_not_flagged_for_refund_to_card(): void
    {
        $order = $this->order(500);

        $this->actingAs($this->cashier)->putJson(
            route('admin.order-management.orders.receive-payment', $order->unique_id),
            ['payment_type' => 'Cash', 'responsible_person' => $this->cashier->id],
        )->assertOk();

        $payment = $order->fresh()->payments()->first();

        $this->assertFalse(GiftCardService::requiresRefundToCard($payment));
    }
}
