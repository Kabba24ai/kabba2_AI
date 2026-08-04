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
use App\Services\GiftCards\GiftCardException;
use App\Services\GiftCards\GiftCardFailure;
use App\Services\GiftCards\GiftCardPermissions;
use App\Services\GiftCards\GiftCardService;
use Database\Seeders\Iam\GiftCardPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The locking strategy itself, exercised across two real connections.
 *
 * ── WHY THIS CANNOT BE AN ORDINARY TEST ───────────────────────────────────
 *
 * `GiftCardServiceTest::test_the_same_value_cannot_be_spent_against_two_orders`
 * runs sequentially. It proves the GUARD — the second attempt is refused
 * because the first already consumed the value — but it cannot prove the LOCK,
 * because nothing is ever contended.
 *
 * The dangerous case is simultaneous: two operators redeem the same card at the
 * same instant. Without `lockForUpdate()` both read $100 before either writes,
 * both approve $100, and $200 leaves a $100 card. No sequential test can
 * distinguish a service that locks from one that does not.
 *
 * So this class opens a SECOND CONNECTION and interleaves deliberately:
 *
 *     Connection A            Connection B
 *     ───────────────────     ───────────────────
 *     BEGIN
 *     SELECT … FOR UPDATE
 *                             redeem()  → must BLOCK
 *     (still holding)
 *     COMMIT / ROLLBACK
 *                             observes the committed state
 *
 * ── WHY THE TRANSACTION WRAPPER IS DISABLED ───────────────────────────────
 *
 * `RefreshDatabase` normally wraps each test in a transaction and rolls it
 * back. A second connection cannot see uncommitted work, so under that wrapper
 * connection B would find no card at all and the race would be untestable.
 * `$connectionsToTransact = []` disables the wrapper; each test therefore
 * cleans up after itself and uses its own data.
 */
class GiftCardConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    /** No wrapping transaction — see the header. */
    protected $connectionsToTransact = [];

    private const PROBE = 'gc_lock_probe';

    private Customer $customer;

    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Row-level locking requires MySQL/MariaDB.');
        }

        // A genuinely separate connection to the same database. Same config,
        // different PDO handle — so it has its own transaction and its own
        // view of uncommitted work, which is the whole point.
        Config::set('database.connections.'.self::PROBE, Config::get('database.connections.mysql'));

        (new GiftCardPermissionSeeder())->run();

        $this->customer = Customer::create([
            'first_name' => 'Race', 'last_name' => 'Probe',
            'email' => 'race-probe-'.uniqid().'@example.com', 'status' => 'Active',
        ]);

        $this->operator = User::create([
            'first_name' => 'Race', 'last_name' => 'Operator',
            'email' => 'race-operator-'.uniqid().'@example.com',
            'password' => bcrypt('secret'), 'status' => 'Active',
        ]);

        foreach (array_keys(GiftCardPermissions::all()) as $permission) {
            $this->operator->givePermissionTo($permission);
        }

        $this->actingAs($this->operator->fresh());
    }

    protected function tearDown(): void
    {
        // The probe connection may still hold a transaction if an assertion
        // failed mid-test; an open transaction would hold locks and hang the
        // next test rather than fail it.
        try {
            if (DB::connection(self::PROBE)->transactionLevel() > 0) {
                DB::connection(self::PROBE)->rollBack();
            }
            DB::purge(self::PROBE);
        } catch (\Throwable) {
            // Nothing to unwind.
        }

        DB::statement('SET SESSION innodb_lock_wait_timeout = 50');

        // These tests COMMIT for real — that is the whole point of disabling
        // the wrapping transaction — so nothing rolls back for them. Without
        // this, rows survive into every later test in the run and quietly
        // corrupt anything that counts globally.
        //
        // Deleted child-first, since the ledger's FK to gift_cards is RESTRICT.
        DB::table('gift_card_transactions')->delete();
        DB::table('gift_cards')->update(['replaced_by_gift_card_id' => null]);
        DB::table('gift_cards')->delete();
        DB::table('order_payments')->delete();
        DB::table('order_products')->delete();
        DB::table('orders')->delete();

        parent::tearDown();
    }

    /**
     * The card row is read with `FOR UPDATE` — a LOCKING read.
     *
     * ── WHY THIS TEST EXISTS SEPARATELY FROM THE TWO BELOW ────────────────
     *
     * Both of the interleave tests below still pass when `lockForUpdate()` is
     * removed from the service. That was verified by deleting it and re-running
     * them, and it is not a flaw in those tests so much as a limit on what a
     * single-process interleave can observe:
     *
     *   - Under READ COMMITTED an unlocked `SELECT` is a non-locking consistent
     *     read, so it does not block; the transaction instead blocks LATER, on
     *     the `UPDATE` to the same row. Contention is still surfaced, so the
     *     blocking test cannot tell the two apart.
     *   - The stale-object test passes either way because the service re-reads
     *     the ledger regardless of whether that read takes a lock.
     *
     * The property that actually prevents an overspend is that the balance is
     * read UNDER THE LOCK — between reading and writing, no other transaction
     * may read it too. Two processes racing in a test harness cannot demonstrate
     * that reliably, so it is asserted directly on the emitted SQL instead.
     *
     * A regression that drops the lock fails here, and only here.
     */
    public function test_the_card_balance_is_read_under_a_locking_read(): void
    {
        $card = $this->purchase(100);
        $order = $this->order(100);

        $statements = [];
        DB::listen(function ($query) use (&$statements) {
            $statements[] = strtolower($query->sql);
        });

        GiftCardService::redeem(card: $card, order: $order, amount: 100, idempotencyKey: 'lock-sql-probe');

        DB::flushQueryLog();

        $cardReads = array_values(array_filter(
            $statements,
            fn (string $sql) => str_contains($sql, 'select') && str_contains($sql, '`gift_cards`')
        ));

        $this->assertNotEmpty($cardReads, 'the service must read the card');

        $this->assertTrue(
            collect($cardReads)->contains(fn (string $sql) => str_contains($sql, 'for update')),
            'the card must be read with FOR UPDATE — an unlocked read lets two '
            .'concurrent redemptions both see the same balance and both approve it'
        );

        // And the ledger sum that authorises the debit happens AFTER that lock
        // is taken — reading the balance before locking would defeat the point.
        $lockIndex = collect($statements)->search(
            fn (string $sql) => str_contains($sql, '`gift_cards`') && str_contains($sql, 'for update')
        );
        $sumIndex = collect($statements)->search(
            fn (string $sql) => str_contains($sql, 'sum(`amount`)') && str_contains($sql, 'gift_card_transactions')
        );

        $this->assertIsInt($lockIndex);
        $this->assertIsInt($sumIndex);
        $this->assertLessThan($sumIndex, $lockIndex, 'the lock must be taken before the balance is summed');
    }

    /**
     * A redemption BLOCKS while another connection holds the card, and the
     * timeout surfaces as `Contended` rather than a raw database error.
     *
     * SCOPE, HONESTLY. This proves contention is HANDLED, not that the balance
     * read is locked — without `lockForUpdate()` the transaction still blocks,
     * just later, on the `UPDATE`. The locking read itself is pinned by
     * {@see self::test_the_card_balance_is_read_under_a_locking_read()}.
     */
    public function test_a_redemption_blocks_while_another_connection_holds_the_card(): void
    {
        $card = $this->purchase(100);
        $order = $this->order(100);

        // ── Connection A: take and hold the card lock ─────────────────────
        DB::connection(self::PROBE)->beginTransaction();
        DB::connection(self::PROBE)->table('gift_cards')
            ->where('id', $card->id)->lockForUpdate()->first();

        // ── Connection B: wait only briefly, so "blocked" is observable ───
        DB::statement('SET SESSION innodb_lock_wait_timeout = 2');

        $blocked = false;

        try {
            GiftCardService::redeem(card: $card, order: $order, amount: 100, idempotencyKey: 'race-blocked');
        } catch (GiftCardException $e) {
            $blocked = true;
            $this->assertSame(
                GiftCardFailure::Contended,
                $e->failure,
                'a lock-wait timeout must surface as Contended, not as a raw database error'
            );
        }

        $this->assertTrue($blocked, 'redeem() must block on a card another connection holds');

        // ── A releases ────────────────────────────────────────────────────
        DB::connection(self::PROBE)->rollBack();
        DB::statement('SET SESSION innodb_lock_wait_timeout = 50');

        // Nothing was written while blocked.
        $this->assertSame(100.0, $card->fresh()->availableBalance());
        $this->assertSame(0, $order->fresh()->payments()->count());

        // And the same request now succeeds.
        GiftCardService::redeem(card: $card->fresh(), order: $order, amount: 100, idempotencyKey: 'race-after');
        $this->assertSame(0.0, $card->fresh()->availableBalance());
    }

    /**
     * The second transaction reads the FIRST's committed result, not the
     * balance it would have seen had it read before the lock.
     *
     * This is the overspend itself: connection A commits a full redemption
     * while B is still holding the card object it loaded BEFORE that happened.
     * B must re-read under its own lock and refuse — one card, one spend, one
     * payment, one ledger row.
     *
     * SCOPE. This proves the service re-reads committed state rather than
     * trusting the object it was handed. It does not, on its own, prove the
     * re-read is locked — see the note on
     * {@see self::test_the_card_balance_is_read_under_a_locking_read()}.
     */
    public function test_a_committed_redemption_is_visible_to_the_next_transaction(): void
    {
        $card = $this->purchase(100);
        $orderA = $this->order(100);
        $orderB = $this->order(100);

        // B's stale view: loaded while the card was still worth $100.
        $staleCard = GiftCard::find($card->id);
        $this->assertSame(100.0, $staleCard->availableBalance());

        // ── Connection A: a complete redemption, committed ────────────────
        DB::connection(self::PROBE)->beginTransaction();
        DB::connection(self::PROBE)->table('gift_cards')
            ->where('id', $card->id)->lockForUpdate()->first();

        $paymentId = DB::connection(self::PROBE)->table('order_payments')->insertGetId([
            'unique_id' => 'ORD-PAY-RACE-'.uniqid(),
            'order_id' => $orderA->id,
            'payment_method' => OrderPaymentMethod::GiftCard->value,
            'payment_datetime' => now(),
            'amount' => 100,
            'status' => 'Paid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::connection(self::PROBE)->table('gift_card_transactions')->insert([
            'gift_card_id' => $card->id,
            'type' => GiftCardTransactionType::Redemption->value,
            'amount' => -100,
            'balance_before' => 100,
            'balance_after' => 0,
            'order_id' => $orderA->id,
            'order_payment_id' => $paymentId,
            'idempotency_key' => 'race-connection-a',
            'created_at' => now(),
        ]);

        DB::connection(self::PROBE)->table('gift_cards')
            ->where('id', $card->id)->update(['cached_balance' => 0]);

        DB::connection(self::PROBE)->commit();

        // ── Connection B: acts on the stale object it loaded earlier ──────
        try {
            GiftCardService::redeem(card: $staleCard, order: $orderB, amount: 100, idempotencyKey: 'race-connection-b');
            $this->fail('the second redemption must not spend value connection A already took');
        } catch (GiftCardException $e) {
            $this->assertSame(GiftCardFailure::InsufficientBalance, $e->failure);
        }

        // One spend, one payment, one ledger row — no overspend, no duplicates.
        $this->assertSame(0.0, $card->fresh()->availableBalance());
        $this->assertSame(
            1,
            GiftCardTransaction::where('gift_card_id', $card->id)
                ->where('type', GiftCardTransactionType::Redemption->value)->count(),
            'exactly one redemption exists in the ledger'
        );
        $this->assertSame(0, $orderB->fresh()->payments()->count(), 'the second order got no payment');
        $this->assertSame(100.0, round($orderB->fresh()->balance_due, 2), 'and still owes its full balance');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function purchase(float $amount): GiftCard
    {
        return GiftCardService::purchase(
            amount: $amount,
            fundingMethod: OrderPaymentMethod::Card,
            purchaserCustomerId: $this->customer->id,
            idempotencyKey: 'race-purchase-'.uniqid(),
        );
    }

    private function order(float $grandTotal): Order
    {
        $order = Order::create([
            'order_date' => '2026-06-24',
            'customer_id' => $this->customer->id,
            'customer_name' => 'Race Probe',
            'subtotal' => $grandTotal,
            'tax_amount' => 0,
            'grand_total' => $grandTotal,
        ]);

        $product = Product::create([
            'product_name' => 'Race Product',
            'slug' => 'race-product-'.uniqid(),
            'product_type' => 'Rental',
        ]);

        OrderProduct::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => 'Race Product',
            'price' => $grandTotal,
            'quantity' => 1,
            'sub_total' => $grandTotal,
            'tax' => 0,
            'total' => $grandTotal,
            'product_data' => ['product_type' => 'Rental'],
        ]);

        return $order->fresh();
    }
}
