<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3B — Refund Allocation Foundation.
 *
 * The durable record of which original settled payment funded a given
 * refund, and how much. Today's refund flow (Phase 3A) can only ever
 * target one unambiguous original payment, so one refund produces exactly
 * one allocation row — the schema itself does not enforce that 1:1
 * cardinality (no unique constraint on refund_order_payment_id) because a
 * future multi-source refund is expected to create several allocation rows
 * against the same refund.
 *
 * original_order_payment_id is restrictOnDelete(): an original payment with
 * allocations recorded against it can never be deleted out from under the
 * audit trail. refund_order_payment_id is cascadeOnDelete(): deleting a
 * refund row (soft-delete on order_payments does not fire this — only a
 * genuine hard delete does) takes its own allocation rows with it, since
 * they have no meaning without the refund they describe.
 *
 * IDEMPOTENT / SELF-REPAIRING (added after a real partial-deployment was
 * found in the wild — see below). Every constraint/index name is now
 * explicit and short, and up() branches on whether the table already
 * exists:
 *
 *   - Fresh database: creates the table, both foreign keys, and both
 *     indexes in one Schema::create() call, exactly as originally shipped.
 *
 *   - Table already exists: NEVER re-runs Schema::create() (which would
 *     throw "table already exists" and abort the whole migrate batch,
 *     including every migration after this one). Instead adds only
 *     whichever of the two foreign keys / two indexes is actually missing,
 *     checked individually against information_schema — so a fully
 *     correct table is left untouched (every check is satisfied, nothing
 *     is added), and a partially-created table is topped up to complete
 *     without ever touching existing rows or dropping/recreating anything.
 *
 * Why this matters: MySQL's DDL is not transactional (see
 * Grammar::supportsSchemaTransactions(), which MySqlGrammar leaves false),
 * so Schema::create() with foreign keys/indexes declared inside the same
 * closure still compiles to SEVERAL separate statements — one CREATE
 * TABLE, then one ALTER TABLE per foreign key and per index — each of
 * which commits independently as it runs. Before this file's identifier
 * names were shortened (see the prior commit fixing the >64-character
 * auto-generated names), a real server got exactly this far before
 * failing:
 *   1. CREATE TABLE ... — succeeded (columns only).
 *   2. ADD CONSTRAINT ..._refund_order_payment_id_foreign — succeeded
 *      (64 characters — exactly at MySQL's limit, not over it).
 *   3. ADD CONSTRAINT ..._original_order_payment_id_foreign — FAILED
 *      (66 characters — "Identifier name is too long"), and the migration
 *      threw, so neither the composite index nor the plain
 *      refund_order_payment_id index (both declared after the failing
 *      statement) were ever reached.
 * Because the migration threw, Laravel never recorded it as run — so
 * `migrate` treats it as Pending and WILL invoke up() again on that
 * server, against a table that already exists with one column-level
 * pointer already in place. Shortening the identifiers alone (the prior
 * fix) does not change this: Schema::create() has no "IF NOT EXISTS"
 * guard, so re-running the unmodified fresh-create path against that
 * table would simply fail again immediately, for a different reason
 * ("table already exists"), before ever reaching the missing pieces.
 *
 * The foreign key on refund_order_payment_id is checked BY COLUMN, not by
 * its new explicit name (opra_refund_payment_fk) — on the one confirmed
 * partial server, that constraint already exists under Laravel's OLD
 * auto-generated 64-character name (the only code path that could ever
 * have created it), not the new short name. Checking by column avoids
 * adding a redundant second foreign key on the same column under the new
 * name. original_order_payment_id's foreign key and both indexes never
 * successfully existed under ANY name before this fix (every attempt to
 * create them failed outright), so there is no equivalent naming risk for
 * them — they are checked by column/column-set for consistency and
 * because it costs nothing extra, but this is a defensive symmetry
 * choice, not a response to an observed case.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('order_payment_refund_allocations')) {
            $this->createTable();

            return;
        }

        $this->repairPartialTable();
    }

    public function down(): void
    {
        Schema::dropIfExists('order_payment_refund_allocations');
    }

    private function createTable(): void
    {
        Schema::create('order_payment_refund_allocations', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('refund_order_payment_id');
            $table->unsignedBigInteger('original_order_payment_id');

            $table->decimal('allocated_amount', 10, 2);
            $table->decimal('allocated_base_amount', 10, 2);
            $table->decimal('allocated_tax_amount', 10, 2);
            $table->decimal('processing_fee_retained', 10, 2)->nullable();

            $table->string('gateway_transaction_id')->nullable();
            $table->string('status', 20);
            $table->string('failure_reason')->nullable();

            $table->timestamps();

            // Explicit, short names throughout (not just on the one that
            // happened to exceed 64 characters) — deterministic and safe
            // to check for by name in repairPartialTable() on every future
            // server, rather than relying on Laravel's auto-generated
            // naming convention staying stable.
            $table->foreign('refund_order_payment_id', 'opra_refund_payment_fk')
                ->references('id')->on('order_payments')
                ->cascadeOnDelete();

            $table->foreign('original_order_payment_id', 'opra_original_payment_fk')
                ->references('id')->on('order_payments')
                ->restrictOnDelete();

            $table->index(['original_order_payment_id', 'status'], 'opra_original_payment_status_idx');
            $table->index('refund_order_payment_id', 'opra_refund_payment_idx');
        });
    }

    /**
     * The table already exists — top up only what's missing. Every check
     * below is a pure read (information_schema) before any write, and
     * each piece is added independently, so this is safe to run against:
     * a fully-correct table (every check passes, nothing happens), the
     * exact partial state described in the Phase 3C repair review (FK1
     * present under its old auto-generated name, everything after it
     * missing), or any other partial subset in between.
     */
    private function repairPartialTable(): void
    {
        Schema::table('order_payment_refund_allocations', function (Blueprint $table) {
            if (! $this->foreignKeyExistsOnColumn('order_payment_refund_allocations', 'refund_order_payment_id', 'order_payments')) {
                $table->foreign('refund_order_payment_id', 'opra_refund_payment_fk')
                    ->references('id')->on('order_payments')
                    ->cascadeOnDelete();
            }

            if (! $this->foreignKeyExistsOnColumn('order_payment_refund_allocations', 'original_order_payment_id', 'order_payments')) {
                $table->foreign('original_order_payment_id', 'opra_original_payment_fk')
                    ->references('id')->on('order_payments')
                    ->restrictOnDelete();
            }

            if (! $this->indexExistsOnColumns('order_payment_refund_allocations', ['original_order_payment_id', 'status'])) {
                $table->index(['original_order_payment_id', 'status'], 'opra_original_payment_status_idx');
            }

            if (! $this->indexExistsOnColumns('order_payment_refund_allocations', ['refund_order_payment_id'])) {
                $table->index('refund_order_payment_id', 'opra_refund_payment_idx');
            }
        });
    }

    /** Whether $table.$column already has ANY foreign key (any name) referencing $referencedTable. */
    private function foreignKeyExistsOnColumn(string $table, string $column, string $referencedTable): bool
    {
        return DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::connection()->getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->where('REFERENCED_TABLE_NAME', $referencedTable)
            ->exists();
    }

    /** Whether $table already has an index (any name) whose columns, in order, exactly equal $columns. */
    private function indexExistsOnColumns(string $table, array $columns): bool
    {
        $database = DB::connection()->getDatabaseName();

        $candidateIndexNames = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('SEQ_IN_INDEX', 1)
            ->where('COLUMN_NAME', $columns[0])
            ->pluck('INDEX_NAME');

        foreach ($candidateIndexNames as $indexName) {
            $actualColumns = DB::table('information_schema.STATISTICS')
                ->where('TABLE_SCHEMA', $database)
                ->where('TABLE_NAME', $table)
                ->where('INDEX_NAME', $indexName)
                ->orderBy('SEQ_IN_INDEX')
                ->pluck('COLUMN_NAME')
                ->all();

            if ($actualColumns === $columns) {
                return true;
            }
        }

        return false;
    }
};
