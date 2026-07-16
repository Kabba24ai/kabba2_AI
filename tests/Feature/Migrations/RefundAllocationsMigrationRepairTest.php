<?php

namespace Tests\Feature\Migrations;

use App\Models\Customers\Customer;
use App\Models\Orders\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Deployment-safety review requested before the Phase 3C push: a real
 * server was found with order_payment_refund_allocations partially
 * created — table + a valid allocation row + the refund_order_payment_id
 * foreign key present (all committed before the identifier-length bug was
 * hit), but original_order_payment_id's foreign key and the composite
 * index missing, and the migration itself not recorded as run (Laravel
 * reports it Pending). See
 * database/migrations/orders/2026_07_15_000001_create_order_payment_refund_allocations_table.php's
 * own docblock for the full mechanism (MySQL DDL is not transactional, so
 * each statement in the original Schema::create() committed independently
 * until the over-long identifier was hit).
 *
 * These tests prove the now-idempotent migration handles all three
 * deployment states safely:
 *   1. Fresh database — unchanged full create.
 *   2. The exact partial state above — repairs only what's missing.
 *   3. An already-fully-correct table — a safe no-op.
 *
 * WRITTEN BUT NOT EXECUTED in this environment: this sandbox has no MySQL
 * server (the same pre-existing, documented limitation as every other
 * Feature test in this codebase — RefreshDatabase fails with "Connection
 * refused" here). Requires a real MySQL test database to run. The static
 * verification actually performed in this sandbox (compiling the real
 * Laravel MySqlGrammar against this migration's Blueprint calls, without a
 * live connection) is reported separately, not represented as a test
 * result here.
 */
class RefundAllocationsMigrationRepairTest extends TestCase
{
    use RefreshDatabase;

    private const TABLE = 'order_payment_refund_allocations';

    private function migrationInstance(): \Illuminate\Database\Migrations\Migration
    {
        return require database_path('migrations/orders/2026_07_15_000001_create_order_payment_refund_allocations_table.php');
    }

    /**
     * Reverts the table (already fully created by RefreshDatabase's normal
     * migrate run, via this migration's own fresh-create path) back to a
     * state equivalent to the confirmed partial server, and un-records the
     * migration so Laravel would treat it as Pending again. The surviving
     * foreign key is left under whatever name the fresh-create path just
     * used (opra_refund_payment_fk) rather than the real server's literal
     * historical auto-generated name — irrelevant to what's being proven
     * here, since the repair checks that column BY COLUMN, never by name.
     */
    private function simulatePartialState(): void
    {
        Schema::table(self::TABLE, function ($table) {
            $table->dropForeign('opra_original_payment_fk');
            $table->dropIndex('opra_original_payment_status_idx');
            $table->dropIndex('opra_refund_payment_idx');
        });

        DB::table('migrations')
            ->where('migration', '2026_07_15_000001_create_order_payment_refund_allocations_table')
            ->delete();
    }

    public function test_repair_adds_missing_pieces_without_touching_the_existing_row(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::create([
            'order_date' => now()->format('Y-m-d'), 'customer_id' => $customer->id,
            'customer_name' => 'Test Customer', 'subtotal' => 500, 'tax_amount' => 0, 'grand_total' => 500,
        ]);
        $original = $order->payments()->create([
            'payment_method' => 'Cash', 'payment_datetime' => now(), 'amount' => 500, 'status' => 'Paid',
        ]);
        $refund = $order->payments()->create([
            'payment_method' => 'Cash', 'payment_datetime' => now(), 'refunded_at' => now(),
            'status' => 'Refunded', 'refund_amount' => 200, 'tax_refunded' => 0,
        ]);

        $existingAllocationId = DB::table(self::TABLE)->insertGetId([
            'refund_order_payment_id' => $refund->id,
            'original_order_payment_id' => $original->id,
            'allocated_amount' => 200, 'allocated_base_amount' => 200, 'allocated_tax_amount' => 0,
            'status' => 'allocated', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->simulatePartialState();

        // Confirm the broken state was actually reproduced before repairing it.
        $this->assertTrue(Schema::hasTable(self::TABLE));
        $this->assertDatabaseHas(self::TABLE, ['id' => $existingAllocationId]);
        $this->assertFalse($this->foreignKeyExists('opra_original_payment_fk'));
        $this->assertFalse($this->indexExists('opra_original_payment_status_idx'));

        $this->migrationInstance()->up();

        // The pre-existing row must survive completely untouched.
        $this->assertDatabaseHas(self::TABLE, [
            'id' => $existingAllocationId, 'refund_order_payment_id' => $refund->id,
            'original_order_payment_id' => $original->id, 'allocated_amount' => 200,
        ]);
        $this->assertSame(1, DB::table(self::TABLE)->count());

        // The missing pieces now exist.
        $this->assertTrue($this->foreignKeyExists('opra_original_payment_fk'));
        $this->assertTrue($this->indexExists('opra_original_payment_status_idx'));
        $this->assertTrue($this->indexExists('opra_refund_payment_idx'));

        // No duplicate foreign key was added on refund_order_payment_id —
        // exactly one, regardless of which name it happens to carry.
        $this->assertSame(1, DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::connection()->getDatabaseName())
            ->where('TABLE_NAME', self::TABLE)
            ->where('COLUMN_NAME', 'refund_order_payment_id')
            ->where('REFERENCED_TABLE_NAME', 'order_payments')
            ->count());

        // The migration must now be recorded — unblocking every migration
        // after it (customer_credits.order_payment_id, Phase 3C's
        // refund_operation_status column, and anything beyond).
        $this->assertDatabaseHas('migrations', [
            'migration' => '2026_07_15_000001_create_order_payment_refund_allocations_table',
        ]);
    }

    public function test_repair_is_a_safe_no_op_once_everything_already_exists(): void
    {
        // RefreshDatabase already ran the fresh-create path — the table is
        // fully correct. Calling up() again (an operator defensively
        // re-running migrate, or Laravel invoking a not-yet-recorded
        // migration that actually already fully succeeded) must not throw
        // and must not create any duplicate constraint.
        $this->migrationInstance()->up();

        $this->assertSame(1, DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::connection()->getDatabaseName())
            ->where('TABLE_NAME', self::TABLE)
            ->where('INDEX_NAME', 'opra_original_payment_status_idx')
            ->where('SEQ_IN_INDEX', 1)
            ->count());

        $this->assertSame(1, DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::connection()->getDatabaseName())
            ->where('CONSTRAINT_NAME', 'opra_original_payment_fk')
            ->count());
    }

    public function test_fresh_create_produces_no_identifiers_over_the_mysql_limit(): void
    {
        $overLength = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::connection()->getDatabaseName())
            ->where('TABLE_NAME', self::TABLE)
            ->pluck('INDEX_NAME')
            ->unique()
            ->filter(fn ($name) => strlen($name) > 64);

        $this->assertTrue($overLength->isEmpty(), 'Index name(s) exceed 64 chars: '.$overLength->implode(', '));

        $overLengthFk = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::connection()->getDatabaseName())
            ->where('TABLE_NAME', self::TABLE)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->pluck('CONSTRAINT_NAME')
            ->filter(fn ($name) => strlen($name) > 64);

        $this->assertTrue($overLengthFk->isEmpty(), 'Foreign key name(s) exceed 64 chars: '.$overLengthFk->implode(', '));
    }

    private function foreignKeyExists(string $name): bool
    {
        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::connection()->getDatabaseName())
            ->where('CONSTRAINT_NAME', $name)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }

    private function indexExists(string $name): bool
    {
        return DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::connection()->getDatabaseName())
            ->where('INDEX_NAME', $name)
            ->exists();
    }
}
