<?php

namespace Tests\Feature\Goodwill;

use App\Enums\Goodwill\GoodwillReason;
use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Customers\Customer;
use App\Models\Discounts\ProductDiscount;
use App\Models\Goodwill\OrderGoodwillAdjustment;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Services\AuthorizeNetService;
use App\Services\Goodwill\GoodwillAdjustmentService;
use App\Services\Goodwill\GoodwillApplyRequest;
use App\Services\Goodwill\GoodwillException;
use App\Services\Goodwill\GoodwillFailure;
use App\Services\Goodwill\GoodwillPermissions;
use Database\Seeders\Iam\GoodwillPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use PDO;
use PDOException;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * The concurrent-payment race, proven against a live second connection.
 *
 * ── WHY THIS CLASS DOES NOT WRAP IN A TRANSACTION ─────────────────────────
 *
 * Every other suite relies on `RefreshDatabase` rolling each test back. That
 * makes a second connection useless: it would not see the fixtures, and the two
 * would deadlock rather than demonstrate anything. `$connectionsToTransact = []`
 * disables the wrapping so rows are really committed and a second session can
 * really contend for them. `tearDown()` clears the tables afterwards, so the
 * database is left exactly as the other suites expect to find it.
 *
 * ── WHAT IS BEING PROVEN ──────────────────────────────────────────────────
 *
 * Goodwill sizes a concession against what has been collected. If a payment
 * lands between reading that total and committing, the order is settled against
 * a figure that was already obsolete — silently, and in the customer's
 * disfavour or the business's, depending on direction.
 *
 * Isolation alone does not prevent it: a repeatable read gives a stable view,
 * not exclusion from inserts. The protection has to be a locking read over the
 * order's payment range, and the tests below interleave a second session at
 * exactly the moment the shared engine writes its discount row — which is
 * inside the transaction and after the lock is taken — to show it holds.
 */
class GoodwillPaymentLockTest extends TestCase
{
    use RefreshDatabase;

    /** No wrapping transaction: a second connection has to see committed rows. */
    protected $connectionsToTransact = [];

    private GoodwillAdjustmentService $service;

    private Customer $customer;

    private User $manager;

    private int $productId;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(GoodwillAdjustmentService::class);

        $this->customer = Customer::create([
            'first_name' => 'Lock', 'last_name' => 'Probe',
            'email' => 'lock-probe@test.local', 'status' => 'Active',
        ]);

        $this->productId = (int) DB::table('products')->insertGetId([
            'unique_id' => 'PRD-LOCK-1', 'product_name' => 'Lock Probe',
            'slug' => 'lock-probe', 'created_at' => now(), 'updated_at' => now(),
        ]);

        (new GoodwillPermissionSeeder())->run();

        $this->manager = User::create([
            'first_name' => 'Lock', 'last_name' => 'Manager',
            'email' => 'lock-manager@test.local', 'status' => 'Active',
        ]);
        $this->manager->givePermissionTo(GoodwillPermissions::APPLY);
        $this->manager->givePermissionTo(GoodwillPermissions::REVERSE);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function tearDown(): void
    {
        ProductDiscount::flushEventListeners();

        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        foreach ([
            'order_goodwill_adjustments', 'order_product_discount_allocations', 'product_discounts',
            'order_payments', 'order_products', 'receipt_items', 'receipts', 'orders',
            'customer_credits', 'customers', 'products',
            'model_has_permissions', 'model_has_roles', 'role_has_permissions',
            'permissions', 'modules', 'module_categories', 'users',
        ] as $table) {
            DB::table($table)->truncate();
        }

        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

        parent::tearDown();
    }

    // ── 1. A payment before the lock is seen, and refuses as stale ─────────

    public function test_a_payment_recorded_before_the_lock_is_included_and_refuses_as_stale(): void
    {
        $order = $this->order(200.00, 19.50);
        $this->pay($order, 190.00);

        $preview = $this->service->preview($order->fresh(), $this->manager);

        // Lands after the preview, before apply takes its lock.
        $this->pay($order, 4.00);

        try {
            $this->service->apply($this->request($order, $preview->concession(), $preview->acceptedPaymentTotal()));
            $this->fail('Applying against an obsolete collected total must refuse.');
        } catch (GoodwillException $e) {
            $this->assertSame(GoodwillFailure::StaleState, $e->failure);
            // The locked read saw the new payment, not the previewed figure.
            $this->assertStringContainsString('194.00', $e->getMessage());
            $this->assertStringContainsString('190.00', $e->getMessage());
        }

        $this->assertSame('0.00', (string) $order->fresh()->pretax_discount_total);
        $this->assertSame(0, OrderGoodwillAdjustment::count());
    }

    // ── 2–3. A payment attempted after the lock waits ──────────────────────

    public function test_a_concurrent_payment_waits_and_then_sees_the_post_goodwill_order(): void
    {
        $order = $this->order(200.00, 19.50);
        $this->pay($order, 200.00);

        $blocked = null;

        // Fires INSIDE the Goodwill transaction, after the payment range is
        // locked. A second session tries to record a payment for the same order.
        $this->duringApply(function () use ($order, &$blocked) {
            $this->assertGreaterThan(0, DB::transactionLevel(), 'Must be inside the transaction.');
            $blocked = $this->attemptPaymentOnSecondConnection((int) $order->id);
        });

        $adjustment = $this->service->apply($this->requestFor($order));

        $this->assertSame('blocked', $blocked, 'A payment for the same order must wait for the lock.');

        // The concession was sized against the total that was locked, and the
        // audit names exactly those payments — not one that arrived later.
        $this->assertSame('200.00', (string) $adjustment->accepted_payment_total);
        $this->assertCount(1, $adjustment->settled_payments_snapshot);

        // After commit, the same insert proceeds and sees the revised order.
        $this->assertSame('proceeded', $this->attemptPaymentOnSecondConnection((int) $order->id));

        $seen = $this->onSecondConnection(
            fn (PDO $pdo) => $pdo->query("SELECT grand_total FROM orders WHERE id = {$order->id}")->fetchColumn()
        );
        $this->assertSame('200.00', (string) $seen, 'The waiting session sees the post-Goodwill total.');
    }

    public function test_goodwill_cannot_commit_against_a_total_a_concurrent_payment_has_changed(): void
    {
        $order = $this->order(200.00, 19.50);
        $this->pay($order, 200.00);

        $this->duringApply(function () use ($order) {
            $this->attemptPaymentOnSecondConnection((int) $order->id, 20.00);
        });

        $adjustment = $this->service->apply($this->requestFor($order));

        // The blocked insert rolled back rather than being silently absorbed
        // into the figure the concession was sized against.
        $this->assertSame('200.00', (string) $adjustment->accepted_payment_total);
        $this->assertSame(200.00, round((float) $order->fresh()->total_paid, 2));
        $this->assertSame(
            round((float) $order->fresh()->grand_total + (float) $adjustment->rounding_residual, 2),
            round((float) $order->fresh()->total_paid, 2),
            'Revised total plus residual must equal the collected total that was locked.'
        );
    }

    // ── 2b. A settling status transition on the same order waits ───────────

    public function test_a_pending_to_paid_transition_on_the_same_order_waits(): void
    {
        // The hazard the parent-order lock alone cannot see: no row is
        // inserted, but the settled total changes underneath the decision.
        $order = $this->order(200.00, 19.50);
        $this->pay($order, 200.00);
        $pending = $this->pay($order, 40.00, OrderPaymentStatus::Pending);

        $result = null;

        $this->duringApply(function () use ($pending, &$result) {
            $result = $this->attemptOnSecondConnection(
                "UPDATE order_payments SET status = 'Paid' WHERE id = {$pending->id}"
            );
        });

        $adjustment = $this->service->apply($this->requestFor($order));

        $this->assertSame('blocked', $result, 'A settling transition must wait for the decision.');

        // The concession was sized against the settled total that was locked —
        // the Pending row was correctly excluded and could not change.
        $this->assertSame('200.00', (string) $adjustment->accepted_payment_total);
        $this->assertSame('200.00', (string) $order->fresh()->grand_total);
    }

    // ── 3–4. Unrelated orders are unaffected, insert and update alike ──────

    public function test_a_payment_for_an_unrelated_order_is_not_blocked(): void
    {
        $order = $this->order(200.00, 19.50);
        $this->pay($order, 200.00);

        // Created after the order under adjustment and carrying no payment
        // rows — the shape that a REPEATABLE READ next-key lock would block.
        $other = $this->order(50.00, 0.00);

        $result = null;

        $this->duringApply(function () use ($other, &$result) {
            $result = $this->attemptPaymentOnSecondConnection((int) $other->id);
        });

        $this->service->apply($this->requestFor($order));

        $this->assertSame('proceeded', $result, 'The lock must be scoped to the order under adjustment.');
    }

    public function test_a_payment_update_on_an_unrelated_order_is_not_blocked(): void
    {
        $order = $this->order(200.00, 19.50);
        $this->pay($order, 200.00);

        $other = $this->order(50.00, 0.00);
        $otherPayment = $this->pay($other, 25.00, OrderPaymentStatus::Pending);

        $result = null;

        $this->duringApply(function () use ($otherPayment, &$result) {
            $result = $this->attemptOnSecondConnection(
                "UPDATE order_payments SET status = 'Paid' WHERE id = {$otherPayment->id}"
            );
        });

        $this->service->apply($this->requestFor($order));

        $this->assertSame('proceeded', $result, 'Another order\'s payments must remain writable.');
    }

    // ── 6. Isolation is scoped to the transaction ──────────────────────────

    public function test_read_committed_applies_to_the_transaction_and_does_not_leak(): void
    {
        // PROVEN BEHAVIOURALLY, NOT BY READING A VARIABLE.
        //
        // `SET TRANSACTION ISOLATION LEVEL` with no SESSION or GLOBAL keyword
        // configures the NEXT transaction only, and MySQL does not expose that
        // pending override — `@@transaction_isolation` keeps reporting the
        // session value throughout. Asserting on it would prove nothing either
        // way, so the test asserts the one thing that actually distinguishes
        // the two levels: whether a gap lock blocks a neighbouring order.
        $order = $this->order(200.00, 19.50);
        $this->pay($order, 200.00);

        // Newer, and carrying no payment rows: its insert lands in the gap
        // above the locked order, which REPEATABLE READ locks and READ
        // COMMITTED does not.
        $other = $this->order(50.00, 0.00);

        $sessionBefore = $this->sessionIsolation();
        $this->assertSame('REPEATABLE-READ', $sessionBefore, 'Baseline: the connection default.');

        $duringGoodwill = null;
        $sessionDuring = null;

        $this->duringApply(function () use ($other, &$duringGoodwill, &$sessionDuring) {
            $sessionDuring = $this->sessionIsolation();
            $duringGoodwill = $this->attemptPaymentOnSecondConnection((int) $other->id);
        });

        $this->service->apply($this->requestFor($order));

        $this->assertSame('proceeded', $duringGoodwill, 'READ COMMITTED takes no gap locks.');

        // The SESSION default is untouched throughout — which is what stops the
        // level leaking into anything else on this connection.
        $this->assertSame($sessionBefore, $sessionDuring);
        $this->assertSame($sessionBefore, $this->sessionIsolation());

        // And the proof it really reverted: the identical lock, taken in an
        // ordinary transaction on the same connection afterwards, blocks the
        // neighbour again. Only REPEATABLE READ does that.
        $afterGoodwill = null;

        DB::transaction(function () use ($order, $other, &$afterGoodwill) {
            DB::table('order_payments')->where('order_id', $order->id)->lockForUpdate()->get();
            $afterGoodwill = $this->attemptPaymentOnSecondConnection((int) $other->id);
        });

        $this->assertSame(
            'blocked',
            $afterGoodwill,
            'The next transaction on this connection must be back at the default isolation.'
        );
    }

    private function sessionIsolation(): string
    {
        return (string) DB::selectOne('SELECT @@transaction_isolation AS level')->level;
    }

    // ── 5. No gateway call inside the transaction ──────────────────────────

    public function test_no_gateway_call_is_made_inside_the_goodwill_transaction(): void
    {
        // A strict mock: any call at all throws, so the assertion is not
        // "we did not observe one" but "one could not have happened".
        $this->instance(AuthorizeNetService::class, Mockery::mock(AuthorizeNetService::class));

        $order = $this->order(200.00, 19.50);
        $this->pay($order, 200.00);

        $insideTransaction = false;

        $this->duringApply(function () use (&$insideTransaction) {
            $insideTransaction = DB::transactionLevel() > 0;
        });

        $adjustment = $this->service->apply($this->requestFor($order));

        $this->assertTrue($insideTransaction, 'The probe must have run inside the transaction.');
        $this->assertNotNull($adjustment->id);
        $this->assertSame('200.00', (string) $order->fresh()->grand_total);
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    /** Run $callback inside the Goodwill transaction, once, after the lock. */
    private function duringApply(callable $callback): void
    {
        ProductDiscount::created(function () use ($callback) {
            static $done = false;
            if (! $done) {
                $done = true;
                $callback();
            }
        });
    }

    /**
     * Try to insert a payment on an independent session with a one-second lock
     * timeout. Returns 'blocked' or 'proceeded'; the row is always rolled back
     * so it cannot pollute what the test asserts afterwards.
     */
    private function attemptPaymentOnSecondConnection(int $orderId, float $amount = 10.00): string
    {
        return $this->attemptOnSecondConnection(
            'INSERT INTO order_payments (unique_id, order_id, payment_method, payment_datetime, amount, status, created_at, updated_at) '
            ."VALUES ('LCK-".bin2hex(random_bytes(5))."', {$orderId}, 'Cash', NOW(), {$amount}, 'Paid', NOW(), NOW())"
        );
    }

    /**
     * Run one statement on an independent session with a one-second lock
     * timeout. Returns 'blocked' or 'proceeded'; always rolled back, so it
     * cannot pollute what the test asserts afterwards.
     */
    private function attemptOnSecondConnection(string $sql): string
    {
        return $this->onSecondConnection(function (PDO $pdo) use ($sql) {
            $pdo->exec('START TRANSACTION');

            try {
                $pdo->exec($sql);
                $pdo->exec('ROLLBACK');

                return 'proceeded';
            } catch (PDOException $e) {
                $pdo->exec('ROLLBACK');

                if (! str_contains($e->getMessage(), 'Lock wait timeout')) {
                    throw $e;
                }

                return 'blocked';
            }
        });
    }

    private function onSecondConnection(callable $callback)
    {
        $config = config('database.connections.mysql');

        $pdo = new PDO(
            "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']}",
            $config['username'],
            $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );

        // Short, so a genuine block is observable rather than hanging the suite.
        $pdo->exec('SET innodb_lock_wait_timeout = 1');

        try {
            return $callback($pdo);
        } finally {
            $pdo = null;
        }
    }

    private function requestFor(Order $order): GoodwillApplyRequest
    {
        $preview = $this->service->preview($order->fresh(), $this->manager);

        return $this->request($order, $preview->concession(), $preview->acceptedPaymentTotal());
    }

    private function request(Order $order, float $amount, float $accepted): GoodwillApplyRequest
    {
        return new GoodwillApplyRequest(
            order: $order->fresh(),
            reason: GoodwillReason::ServiceFailure,
            note: null,
            idempotencyKey: 'gw-lock-'.$order->id.'-'.(++$this->sequence),
            operator: $this->manager,
            approver: $this->manager,
            expectedGoodwillAmount: $amount,
            expectedAcceptedPaymentTotal: $accepted,
        );
    }

    private function pay(Order $order, float $amount, ?OrderPaymentStatus $status = null)
    {
        return $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount' => $amount,
            'status' => ($status ?? OrderPaymentStatus::Paid)->value,
        ]);
    }

    private function order(float $sub, float $tax): Order
    {
        $order = Order::create([
            'order_date' => now()->toDateString(),
            'customer_id' => $this->customer->id,
            'customer_name' => 'Lock Probe',
            'subtotal' => $sub, 'tax_amount' => $tax,
            'special_tax_amount' => 0, 'added_fees_amount' => 0,
            'discount_amount' => 0, 'pretax_discount_total' => 0,
            'grand_total' => $sub + $tax,
        ]);

        $order->products()->create([
            'unique_id' => 'ORD-LOCK-'.$order->id,
            'product_id' => $this->productId,
            'product_name' => 'Lock Probe',
            'price' => $sub, 'quantity' => 1,
            'sub_total' => $sub, 'tax' => $tax,
            'special_tax' => 0, 'added_fees' => 0,
            'total' => $sub + $tax,
            'product_data' => json_encode(['sub_total' => $sub, 'tax' => $tax, 'special_tax' => 0, 'added_fees' => 0]),
        ]);

        return $order->fresh();
    }
}
