<?php

namespace Tests\Feature\GiftCards;

use App\Enums\GiftCards\GiftCardIssuanceClass;
use App\Enums\GiftCards\GiftCardStatus;
use App\Enums\GiftCards\GiftCardTransactionType;
use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Configurations\Setting;
use App\Models\Customers\Customer;
use App\Models\GiftCards\GiftCard;
use App\Models\GiftCards\GiftCardTransaction;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Models\ProductManagement\Product;
use App\Models\Orders\OrderProduct;
use App\Services\GiftCards\GiftCardException;
use App\Services\GiftCards\GiftCardFailure;
use App\Services\GiftCards\GiftCardPermissions;
use App\Services\GiftCards\GiftCardService;
use Database\Seeders\Iam\GiftCardPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The gift-card ledger: what it guarantees, and what it refuses.
 *
 * The invariant underneath all of it — a card's value is the SUM OF ITS LEDGER,
 * never a column someone updated. Every test here either proves that holds or
 * proves an attempt to violate it is refused.
 */
class GiftCardServiceTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'Gift', 'last_name' => 'Buyer',
            'email' => 'gift-buyer@example.com', 'status' => 'Active',
        ]);

        (new GiftCardPermissionSeeder())->run();

        // Fully privileged, so the tests below exercise BEHAVIOUR rather than
        // authorisation. Authorisation gets its own tests, with users who hold
        // nothing — see the permissions section.
        $this->operator = $this->userWith(array_keys(GiftCardPermissions::all()), 'operator');

        $this->actingAs($this->operator);
    }

    /** A user holding exactly the named permissions and nothing else. */
    private function userWith(array $permissions, string $handle): User
    {
        $user = User::create([
            'first_name' => 'Gift', 'last_name' => ucfirst($handle),
            'email' => "gift-{$handle}-".uniqid().'@example.com',
            'password' => bcrypt('secret'), 'status' => 'Active',
        ]);

        foreach ($permissions as $permission) {
            $user->givePermissionTo($permission);
        }

        return $user->fresh();
    }

    // ── Issuance ──────────────────────────────────────────────────────────

    public function test_purchase_creates_value_equal_to_the_money_taken(): void
    {
        $card = $this->purchase(500);

        // No tax extracted, no face value reduced. $500 paid is $500 available.
        $this->assertSame('500.00', (string) $card->original_value);
        $this->assertSame(500.0, $card->availableBalance());
        $this->assertSame(GiftCardStatus::Active, $card->status);
        $this->assertSame(GiftCardIssuanceClass::Purchased, $card->issuance_class);
    }

    /**
     * The funding tender is the REAL method. `GiftCard` means redemption; a
     * card that could fund itself would create value from nothing and make
     * reconciliation Stream E circular.
     */
    public function test_purchased_issuance_records_the_real_tender_never_giftcard(): void
    {
        $card = $this->purchase(500, OrderPaymentMethod::Card);
        $funding = $card->transactions()->first();

        $this->assertSame(OrderPaymentMethod::Card, $funding->funding_payment_method);
        $this->assertNotSame(OrderPaymentMethod::GiftCard, $funding->funding_payment_method);
        $this->assertSame('500.00', (string) $funding->funding_cash_amount);
        $this->assertTrue($funding->bringsExternalCash());
    }

    public function test_a_gift_card_cannot_be_funded_with_a_gift_card(): void
    {
        $this->expectException(GiftCardException::class);

        GiftCardService::purchase(
            amount: 100,
            fundingMethod: OrderPaymentMethod::GiftCard,
        );
    }

    public function test_granted_issuance_has_no_funding_and_no_cash(): void
    {
        $card = GiftCardService::grant(
            amount: 50,
            reasonCode: 'SERVICE_RECOVERY',
            reasonCategory: 'service_recovery',
            note: 'Late delivery on order 1042.',
        );

        $row = $card->transactions()->first();

        $this->assertSame(GiftCardIssuanceClass::Granted, $card->issuance_class);
        $this->assertSame(GiftCardTransactionType::IssuanceGranted, $row->type);

        // Promotional value is never cash — enforced at the storage layer too.
        $this->assertNull($row->funding_payment_method);
        $this->assertNull($row->funding_cash_amount);
        $this->assertFalse($row->bringsExternalCash());
        $this->assertSame(0.0, $row->settledFundingCash());
    }

    public function test_a_granted_card_requires_a_reason(): void
    {
        try {
            GiftCardService::grant(amount: 50, reasonCode: '', reasonCategory: '');
            $this->fail('a grant with no reason should be refused');
        } catch (GiftCardException $e) {
            $this->assertSame(GiftCardFailure::GrantReasonRequired, $e->failure);
        }
    }

    // ── The ledger is canonical ───────────────────────────────────────────

    /**
     * The column is a cache and nothing more. Corrupt it and the authoritative
     * balance is unmoved — because the balance was never in the column.
     */
    public function test_the_cached_balance_is_only_a_projection_of_the_ledger(): void
    {
        $card = $this->purchase(500);

        // Simulate drift: a crash between the ledger append and the cache
        // write, a stray UPDATE, a partial restore. Kept inside the CHECK
        // constraint's bounds on purpose — a value ABOVE original_value is
        // refused outright by `gc_cached_balance_within_bounds`, which is the
        // cheap always-on guard; this test is about the subtler case the
        // constraint cannot catch, where the cache is merely WRONG.
        \DB::table('gift_cards')->where('id', $card->id)->update(['cached_balance' => 250]);
        $card->refresh();

        $this->assertSame('250.00', (string) $card->cached_balance, 'the cache is corrupt');

        $this->assertSame(500.0, $card->availableBalance(), 'the ledger is unaffected by the cache');
        $this->assertTrue($card->cacheIsStale());

        // And the projection is rebuilt from the ledger, not trusted.
        GiftCardService::refreshProjection($card);
        $this->assertSame('500.00', (string) $card->fresh()->cached_balance);
    }

    public function test_ledger_rows_can_never_be_edited_or_deleted(): void
    {
        $row = $this->purchase(500)->transactions()->first();

        try {
            $row->update(['amount' => 999]);
            $this->fail('a ledger row must not be editable');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('append-only', $e->getMessage());
        }

        try {
            $row->delete();
            $this->fail('a ledger row must not be deletable');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('append-only', $e->getMessage());
        }
    }

    public function test_the_face_value_of_an_issued_card_cannot_be_rewritten(): void
    {
        $card = $this->purchase(500);

        $this->expectException(\LogicException::class);
        $card->update(['original_value' => 900]);
    }

    // ── Redemption ────────────────────────────────────────────────────────

    /**
     * Redemption produces an ORDINARY payment — it must appear in payment
     * history and settle the order like any tender — linked back to the ledger
     * row that authorised it. That link is what lets reporting classify the
     * payment as non-cash without the payment itself being special.
     */
    public function test_redemption_creates_a_linked_ordinary_gift_card_payment(): void
    {
        $card = $this->purchase(500);
        $order = $this->order(550);

        $txn = GiftCardService::redeem(card: $card, order: $order, amount: 500);

        $payment = $txn->orderPayment;
        $this->assertNotNull($payment, 'the redemption must be linked to its payment');
        $this->assertSame(OrderPaymentMethod::GiftCard, $payment->payment_method);
        $this->assertSame('500.00', (string) $payment->amount);

        // Settled in the canonical sense — the same status set
        // OrderPayment::scopeSettled() uses — so this row counts towards the
        // order's paid total exactly like any other tender.
        $this->assertTrue($payment->status->isSettled() || $payment->status === OrderPaymentStatus::PartialPayment);

        // The order genuinely is paid down by it.
        $this->assertSame(50.0, round($order->fresh()->balance_due, 2));

        // And the card genuinely is spent down by it.
        $this->assertSame(0.0, $card->fresh()->availableBalance());
        $this->assertSame(GiftCardStatus::FullyRedeemed, $card->fresh()->status);
    }

    public function test_partial_redemption_leaves_the_remainder_spendable(): void
    {
        $card = $this->purchase(500);
        $order = $this->order(200);

        GiftCardService::redeem(card: $card, order: $order, amount: 200);

        $card->refresh();
        $this->assertSame(300.0, $card->availableBalance());
        $this->assertSame(GiftCardStatus::PartiallyRedeemed, $card->status);
        $this->assertTrue($card->isRedeemable());
    }

    public function test_a_card_cannot_be_overdrawn(): void
    {
        $card = $this->purchase(100);
        $order = $this->order(500);

        try {
            GiftCardService::redeem(card: $card, order: $order, amount: 200);
            $this->fail('a card must not pay more than it holds');
        } catch (GiftCardException $e) {
            $this->assertSame(GiftCardFailure::InsufficientBalance, $e->failure);
        }

        $this->assertSame(100.0, $card->fresh()->availableBalance(), 'nothing was taken');
        $this->assertCount(0, $order->fresh()->payments, 'and no payment was written');
    }

    /**
     * Both bounds matter. A card may not overpay an order any more than an
     * order may overdraw a card — an overpayment here would leave stored value
     * converted into an order credit nobody asked for.
     */
    public function test_a_card_cannot_pay_more_than_the_order_owes(): void
    {
        $card = $this->purchase(500);
        $order = $this->order(100);

        try {
            GiftCardService::redeem(card: $card, order: $order, amount: 300);
            $this->fail('a redemption must not exceed the order balance');
        } catch (GiftCardException $e) {
            $this->assertSame(GiftCardFailure::ExceedsOrderBalance, $e->failure);
        }

        $this->assertSame(500.0, $card->fresh()->availableBalance());
    }

    public function test_a_fully_spent_card_is_refused(): void
    {
        $card = $this->purchase(100);
        GiftCardService::redeem(card: $card, order: $this->order(100), amount: 100);

        try {
            GiftCardService::redeem(card: $card->fresh(), order: $this->order(50), amount: 50);
            $this->fail('a spent card must not be redeemable');
        } catch (GiftCardException $e) {
            $this->assertSame(GiftCardFailure::CardNotRedeemable, $e->failure);
        }
    }

    public function test_a_cancelled_card_is_refused_even_with_a_balance(): void
    {
        $card = $this->purchase(500);
        $card->forceFill(['status' => GiftCardStatus::Cancelled->value])->save();

        try {
            GiftCardService::redeem(card: $card->fresh(), order: $this->order(100), amount: 100);
            $this->fail('a cancelled card must not be redeemable');
        } catch (GiftCardException $e) {
            $this->assertSame(GiftCardFailure::CardNotRedeemable, $e->failure);
        }
    }

    // ── Idempotency ───────────────────────────────────────────────────────

    public function test_a_replayed_purchase_issues_one_card(): void
    {
        $first = GiftCardService::purchase(
            amount: 500, fundingMethod: OrderPaymentMethod::Card, idempotencyKey: 'webhook-abc',
        );
        $second = GiftCardService::purchase(
            amount: 500, fundingMethod: OrderPaymentMethod::Card, idempotencyKey: 'webhook-abc',
        );

        $this->assertSame($first->id, $second->id);

        // Scoped to the key under test, not a global count — a global assertion
        // silently depends on every other test that ever wrote a card.
        $this->assertSame(1, GiftCardTransaction::where('idempotency_key', 'webhook-abc')->count());
        $this->assertSame(500.0, $first->fresh()->availableBalance(), 'value was not issued twice');
    }

    public function test_a_replayed_redemption_debits_once(): void
    {
        $card = $this->purchase(500);
        $order = $this->order(550);

        $a = GiftCardService::redeem(card: $card, order: $order, amount: 200, idempotencyKey: 'click-xyz');
        $b = GiftCardService::redeem(card: $card, order: $order, amount: 200, idempotencyKey: 'click-xyz');

        $this->assertSame($a->id, $b->id);
        $this->assertSame(300.0, $card->fresh()->availableBalance(), 'debited once, not twice');
        $this->assertSame(1, $order->fresh()->payments()->count(), 'and one payment row, not two');
    }

    // ── Concurrency ───────────────────────────────────────────────────────

    /**
     * The same value cannot be spent against two different orders.
     *
     * HONEST SCOPE. This runs sequentially, so it proves the GUARD, not the
     * lock: the second attempt is refused because the first already consumed
     * the value. What makes the same guarantee hold when the two arrive
     * SIMULTANEOUSLY is that {@see GiftCardService::redeem()} takes
     * `lockForUpdate()` on the card BEFORE summing the ledger — so a concurrent
     * request blocks until the first commits and then reads the post-debit
     * balance, rather than both reading $100 and both approving.
     *
     * Proving the parallel case needs two real connections and a controlled
     * interleave; it is not simulated here, and this test does not claim to.
     */
    public function test_the_same_value_cannot_be_spent_against_two_orders(): void
    {
        $this->markTestSkippedIfSqlite();

        $card = $this->purchase(100);
        $orderA = $this->order(100);
        $orderB = $this->order(100);

        $results = [];

        foreach ([[$orderA, 'a'], [$orderB, 'b']] as [$order, $key]) {
            try {
                GiftCardService::redeem(card: $card->fresh(), order: $order, amount: 100, idempotencyKey: "race-$key");
                $results[] = 'ok';
            } catch (GiftCardException $e) {
                $results[] = $e->failure->value;
            }
        }

        // Refused on STATUS, because the first redemption already drove the
        // card to FullyRedeemed. The balance check behind it is the backstop
        // that catches a card whose cached status has drifted.
        $this->assertSame(['ok', GiftCardFailure::CardNotRedeemable->value], $results);

        $this->assertSame(0.0, $card->fresh()->availableBalance(), 'the card was spent exactly once');
        $this->assertSame(
            -100.0,
            round((float) GiftCardTransaction::redemptions()->where('gift_card_id', $card->id)->sum('amount'), 2),
            'exactly one redemption exists in the ledger for THIS card'
        );
        $this->assertSame(100.0, round($orderB->fresh()->balance_due, 2), 'the second order was not paid');
    }

    /**
     * The balance guard survives a stale status.
     *
     * Forcing the card back to Active with an empty ledger isolates the second
     * check: without it, a card whose projection had drifted would be
     * redeemable for value it no longer holds.
     */
    public function test_the_balance_guard_holds_even_when_the_status_says_active(): void
    {
        $card = $this->purchase(100);
        GiftCardService::redeem(card: $card, order: $this->order(100), amount: 100);

        // Drift the status without touching the ledger.
        \DB::table('gift_cards')->where('id', $card->id)
            ->update(['status' => GiftCardStatus::Active->value]);

        try {
            GiftCardService::redeem(card: $card->fresh(), order: $this->order(50), amount: 50);
            $this->fail('an empty card must be refused whatever its status column says');
        } catch (GiftCardException $e) {
            $this->assertSame(GiftCardFailure::InsufficientBalance, $e->failure);
        }
    }

    // ── Card identity ─────────────────────────────────────────────────────

    public function test_the_card_number_prefix_comes_from_company_settings(): void
    {
        Setting::create([
            'setting_type' => 'Company Settings',
            'setting_name' => 'gift_card_prefix',
            'setting_value' => 'RNK',
        ]);

        $this->assertMatchesRegularExpression('/^RNK-\d{4}-\d{4}$/', GiftCardService::generateCardNumber());
    }

    public function test_the_card_number_is_never_a_sequential_id(): void
    {
        $numbers = collect(range(1, 5))->map(fn () => GiftCardService::generateCardNumber());

        $this->assertCount(5, $numbers->unique(), 'numbers must not collide');
        foreach ($numbers as $number) {
            $this->assertDoesNotMatchRegularExpression('/^\d+$/', $number);
        }
    }

    public function test_the_lookup_token_is_distinct_from_the_card_number(): void
    {
        $card = $this->purchase(100);

        // A QR scan must be able to show a balance without exposing the number
        // that, with a PIN, redeems the card.
        $this->assertNotSame($card->card_number, $card->lookup_token);
        $this->assertGreaterThanOrEqual(32, strlen($card->lookup_token));
        $this->assertStringEndsWith(substr($card->card_number, -4), $card->maskedNumber());
        $this->assertStringNotContainsString(substr($card->card_number, 0, 3), $card->maskedNumber());
    }

    // ── Permissions ───────────────────────────────────────────────────────

    /**
     * The one that matters most.
     *
     * `grant()` creates spendable value that nobody paid for. Before this
     * check existed, any caller reaching the service could issue unlimited
     * money. A user with every OTHER gift-card permission still cannot.
     */
    public function test_granting_value_requires_its_own_permission(): void
    {
        $everythingElse = array_values(array_diff(
            array_keys(GiftCardPermissions::all()),
            [GiftCardPermissions::GRANT],
        ));

        $this->actingAs($this->userWith($everythingElse, 'nogrant'));

        $before = GiftCard::count();

        try {
            GiftCardService::grant(amount: 500, reasonCode: 'PROMO', reasonCategory: 'promotion');
            $this->fail('granting value without gift-card.grant must be refused');
        } catch (GiftCardException $e) {
            $this->assertSame(GiftCardFailure::NotAuthorized, $e->failure);
        }

        $this->assertSame($before, GiftCard::count(), 'no value was created');
    }

    /**
     * Selling and granting are separated deliberately: taking $500 and issuing
     * $500 is a clerk's job; issuing $500 from nothing is not.
     */
    public function test_selling_a_card_does_not_confer_the_power_to_grant_one(): void
    {
        $this->actingAs($this->userWith([GiftCardPermissions::SELL], 'clerk'));

        // Selling is fine.
        $card = GiftCardService::purchase(amount: 100, fundingMethod: OrderPaymentMethod::Cash);
        $this->assertSame(100.0, $card->availableBalance());

        // Granting is not.
        $this->expectException(GiftCardException::class);
        GiftCardService::grant(amount: 100, reasonCode: 'PROMO', reasonCategory: 'promotion');
    }

    public function test_manual_adjustment_requires_its_own_permission(): void
    {
        $card = $this->purchase(500);
        $this->actingAs($this->userWith([GiftCardPermissions::REDEEM], 'redeemer'));

        try {
            GiftCardService::adjustIncrease($card, 100, 'Trying it on');
            $this->fail('adjusting a balance without gift-card.adjust must be refused');
        } catch (GiftCardException $e) {
            $this->assertSame(GiftCardFailure::NotAuthorized, $e->failure);
        }

        $this->assertSame(500.0, $card->fresh()->availableBalance());
    }

    public function test_an_unauthenticated_caller_can_do_nothing(): void
    {
        auth()->logout();

        $this->expectException(GiftCardException::class);
        GiftCardService::grant(amount: 500, reasonCode: 'PROMO', reasonCategory: 'promotion');
    }

    public function test_every_value_changing_operation_demands_a_reason(): void
    {
        $card = $this->purchase(500);

        foreach ([
            'adjustIncrease' => fn () => GiftCardService::adjustIncrease($card, 10, '  '),
            'cancel' => fn () => GiftCardService::cancel($card, ''),
            'suspend' => fn () => GiftCardService::suspend($card, ''),
            'replace' => fn () => GiftCardService::replace($card, ''),
        ] as $operation => $call) {
            try {
                $call();
                $this->fail("{$operation}() must not proceed without a reason");
            } catch (GiftCardException $e) {
                $this->assertSame(GiftCardFailure::ReasonRequired, $e->failure, $operation);
            }
        }
    }

    // ── Refund routing ────────────────────────────────────────────────────

    /**
     * Value returns to the card it came from.
     *
     * Refunding it as cash would hand the customer $500 of money for $500 of
     * stored value — converting a liability the business owes into cash it
     * never took for that transaction.
     */
    public function test_a_gift_card_funded_payment_refunds_back_to_the_same_card(): void
    {
        $card = $this->purchase(500);
        $order = $this->order(500);
        $txn = GiftCardService::redeem(card: $card, order: $order, amount: 500);

        $this->assertSame(0.0, $card->fresh()->availableBalance());

        GiftCardService::refundToCard(
            giftCardPayment: $txn->orderPayment,
            amount: 500,
            reason: 'Order cancelled',
        );

        $card->refresh();
        $this->assertSame(500.0, $card->availableBalance(), 'value returned to the card, not the till');
        $this->assertTrue($card->isRedeemable(), 'and is spendable again');
    }

    public function test_a_refund_can_never_exceed_what_the_order_took_from_the_card(): void
    {
        $card = $this->purchase(500);
        $txn = GiftCardService::redeem(card: $card, order: $this->order(100), amount: 100);

        try {
            GiftCardService::refundToCard($txn->orderPayment, 500, 'Typed the order total by mistake');
            $this->fail('a refund must be bounded by what was actually redeemed');
        } catch (GiftCardException $e) {
            $this->assertSame(GiftCardFailure::RefundExceedsRedeemed, $e->failure);
        }

        $this->assertSame(400.0, $card->fresh()->availableBalance(), 'no value was invented');
    }

    public function test_a_replayed_refund_credits_the_card_once(): void
    {
        $card = $this->purchase(500);
        $txn = GiftCardService::redeem(card: $card, order: $this->order(500), amount: 500);

        GiftCardService::refundToCard($txn->orderPayment, 200, 'Partial return', 'refund-key-1');
        GiftCardService::refundToCard($txn->orderPayment, 200, 'Partial return', 'refund-key-1');

        $this->assertSame(200.0, $card->fresh()->availableBalance(), 'credited once, not twice');
    }

    public function test_partial_refunds_cannot_cumulatively_exceed_the_redemption(): void
    {
        $card = $this->purchase(500);
        $txn = GiftCardService::redeem(card: $card, order: $this->order(500), amount: 500);

        GiftCardService::refundToCard($txn->orderPayment, 300, 'First', 'r1');

        try {
            GiftCardService::refundToCard($txn->orderPayment, 300, 'Second', 'r2');
            $this->fail('the second refund would exceed the $500 originally redeemed');
        } catch (GiftCardException $e) {
            $this->assertSame(GiftCardFailure::RefundExceedsRedeemed, $e->failure);
        }

        $this->assertSame(300.0, $card->fresh()->availableBalance());
    }

    /** A cash payment has no card behind it — "refund to card" is meaningless. */
    public function test_a_payment_that_never_touched_a_card_cannot_be_refunded_to_one(): void
    {
        $order = $this->order(100);
        $cash = $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(), 'amount' => 100, 'status' => 'Paid',
        ]);

        $this->assertFalse(GiftCardService::requiresRefundToCard($cash));

        try {
            GiftCardService::refundToCard($cash, 100, 'Nope');
            $this->fail('a cash payment cannot be refunded to a gift card');
        } catch (GiftCardException $e) {
            $this->assertSame(GiftCardFailure::NothingToRefund, $e->failure);
        }
    }

    /** The refund screen must know to route back to the card by default. */
    public function test_a_gift_card_payment_is_flagged_as_requiring_card_routing(): void
    {
        $txn = GiftCardService::redeem(
            card: $this->purchase(100), order: $this->order(100), amount: 100,
        );

        $this->assertTrue(GiftCardService::requiresRefundToCard($txn->orderPayment));
    }

    // ── Lifecycle ─────────────────────────────────────────────────────────

    public function test_suspending_blocks_redemption_without_destroying_value(): void
    {
        $card = $this->purchase(500);

        GiftCardService::suspend($card, 'Reported stolen');
        $card->refresh();

        $this->assertSame(GiftCardStatus::Suspended, $card->status);
        $this->assertSame(500.0, $card->availableBalance(), 'the money is still there');
        $this->assertSame('Reported stolen', $card->suspension_reason);
        $this->assertSame($this->operator->id, $card->suspended_by);
        $this->assertNotNull($card->suspended_at);

        try {
            GiftCardService::redeem(card: $card, order: $this->order(100), amount: 100);
            $this->fail('a suspended card must not redeem');
        } catch (GiftCardException $e) {
            $this->assertSame(GiftCardFailure::CardNotRedeemable, $e->failure);
        }
    }

    public function test_reinstating_returns_the_card_to_circulation_and_is_audited(): void
    {
        $card = $this->purchase(500);
        GiftCardService::suspend($card, 'Suspected fraud');
        GiftCardService::reinstate($card->fresh(), 'Dispute resolved in the customer’s favour');

        $card->refresh();
        $this->assertTrue($card->isRedeemable());
        $this->assertSame(500.0, $card->availableBalance());
        $this->assertSame('Dispute resolved in the customer’s favour', $card->reinstatement_reason);
        $this->assertSame($this->operator->id, $card->reinstated_by);
    }

    /**
     * Cancellation writes the remaining value OFF in the ledger rather than
     * zeroing a column — otherwise money disappears with no record of who
     * removed it.
     */
    public function test_cancelling_writes_off_the_balance_as_an_auditable_ledger_row(): void
    {
        $card = $this->purchase(500);

        GiftCardService::cancel($card, 'Issued in error');
        $card->refresh();

        $this->assertSame(GiftCardStatus::Cancelled, $card->status);
        $this->assertSame(0.0, $card->availableBalance());

        $writeOff = $card->transactions()->where('type', GiftCardTransactionType::Cancellation->value)->first();
        $this->assertNotNull($writeOff, 'the write-off must be visible in the ledger');
        $this->assertSame('-500.00', (string) $writeOff->amount);
        $this->assertSame('Issued in error', $writeOff->reason);
        $this->assertSame($this->operator->id, $writeOff->created_by_id);
    }

    /**
     * Two rows, not one — value leaves the old card and arrives on the new,
     * each visible on its own history. One row would make money appear to
     * vanish from one side.
     */
    public function test_replacing_moves_the_balance_and_retires_the_original(): void
    {
        $old = $this->purchase(500);
        GiftCardService::redeem(card: $old, order: $this->order(200), amount: 200);

        $new = GiftCardService::replace($old->fresh(), 'Card damaged in the post');

        $this->assertSame(300.0, $new->availableBalance(), 'the remaining value moved across');
        $this->assertSame(0.0, $old->fresh()->availableBalance());
        $this->assertSame(GiftCardStatus::Replaced, $old->fresh()->status);
        $this->assertSame($new->id, $old->fresh()->replaced_by_gift_card_id);
        $this->assertNotSame($old->card_number, $new->card_number);

        // A recovered original must not be spendable alongside its replacement.
        try {
            GiftCardService::redeem(card: $old->fresh(), order: $this->order(50), amount: 50);
            $this->fail('a replaced card must not redeem');
        } catch (GiftCardException $e) {
            $this->assertSame(GiftCardFailure::CardNotRedeemable, $e->failure);
        }
    }

    /**
     * A replaced GRANTED card must not quietly become purchased liability —
     * that would turn promotional value the business gave away into money it
     * owes.
     */
    public function test_a_replacement_inherits_the_original_issuance_class(): void
    {
        $granted = GiftCardService::grant(
            amount: 100, reasonCode: 'SERVICE_RECOVERY', reasonCategory: 'service_recovery',
        );

        $new = GiftCardService::replace($granted, 'Lost card');

        $this->assertSame(GiftCardIssuanceClass::Granted, $new->issuance_class);
    }

    // ── Manual adjustment ─────────────────────────────────────────────────

    public function test_an_increase_adds_value_and_records_who_and_why(): void
    {
        $card = $this->purchase(500);

        GiftCardService::adjustIncrease($card, 50, 'Goodwill top-up', 'Approved by store manager');

        $card->refresh();
        $this->assertSame(550.0, $card->availableBalance());

        $row = $card->transactions()->where('type', GiftCardTransactionType::AdjustmentIncrease->value)->first();
        $this->assertSame('50.00', (string) $row->amount);
        $this->assertSame('Goodwill top-up', $row->reason);
        $this->assertSame($this->operator->id, $row->created_by_id);
    }

    public function test_a_decrease_cannot_drive_the_balance_negative(): void
    {
        $card = $this->purchase(100);

        try {
            GiftCardService::adjustDecrease($card, 200, 'Correcting an over-issue');
            $this->fail('a balance must never go negative');
        } catch (GiftCardException $e) {
            $this->assertSame(GiftCardFailure::InsufficientBalance, $e->failure);
        }

        $this->assertSame(100.0, $card->fresh()->availableBalance());
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function purchase(float $amount, ?OrderPaymentMethod $method = null): GiftCard
    {
        return GiftCardService::purchase(
            amount: $amount,
            fundingMethod: $method ?? OrderPaymentMethod::Card,
            purchaserCustomerId: $this->customer->id,
            idempotencyKey: 'purchase-'.uniqid(),
        );
    }

    private function order(float $grandTotal): Order
    {
        $order = Order::create([
            'order_date' => '2026-06-24',
            'customer_id' => $this->customer->id,
            'customer_name' => 'Gift Buyer',
            'subtotal' => $grandTotal,
            'tax_amount' => 0,
            'grand_total' => $grandTotal,
        ]);

        $product = Product::create([
            'product_name' => 'Gift Service Product',
            'slug' => 'gift-service-product-'.uniqid(),
            'product_type' => 'Rental',
        ]);

        OrderProduct::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => 'Gift Service Product',
            'price' => $grandTotal,
            'quantity' => 1,
            'sub_total' => $grandTotal,
            'tax' => 0,
            'total' => $grandTotal,
            'product_data' => ['product_type' => 'Rental'],
        ]);

        return $order->fresh();
    }

    private function markTestSkippedIfSqlite(): void
    {
        if (\DB::connection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped('Row locking requires MySQL/MariaDB.');
        }
    }
}
