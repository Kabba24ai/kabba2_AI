<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2A — Inspection Integrity Foundation.
 *
 * Separate LIFECYCLE (draft/completed/voided/...) from RESULT
 * (rental_ready/maintenance_hold/damaged) so a completed inspection can be
 * frozen and never reused, while the legacy `status` enum stays for
 * backward-compatible reads. Adds completion metadata, void/supersede
 * bookkeeping, and a unique completion idempotency key.
 *
 * The column backfill below is a CONSERVATIVE, reader-continuity mapping of
 * existing rows only (so already-inspected equipment doesn't regress to
 * "Inspection Required" after deploy). It is NOT the legacy QA-log recovery /
 * conversion — that is deliberately deferred (audited separately, not imported
 * in this increment). Where a legacy 'Draft' row is genuinely ambiguous
 * (maintenance-hold vs simply incomplete) it is left as a draft.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_rental_ready_templates', function (Blueprint $table) {
            $table->enum('lifecycle_status', ['draft', 'completed', 'voided', 'superseded', 'abandoned'])
                ->default('draft')->after('status');
            $table->enum('result', ['rental_ready', 'maintenance_hold', 'damaged'])
                ->nullable()->after('lifecycle_status');
            $table->timestamp('completed_at')->nullable()->after('result');
            $table->timestamp('voided_at')->nullable()->after('completed_at');
            $table->unsignedBigInteger('voided_by')->nullable()->after('voided_at');
            $table->string('void_reason')->nullable()->after('voided_by');
            $table->unsignedBigInteger('superseded_by_template_id')->nullable()->after('void_reason');
            // Nullable + unique: MySQL allows many NULLs; a set key blocks a
            // duplicate completion (mobile retry returns the existing row).
            // Explicit short index name (auto name exceeds MySQL's 64-char limit).
            $table->string('completion_idempotency_key')->nullable()->after('superseded_by_template_id');
            $table->unique('completion_idempotency_key', 'errt_completion_idem_key_unique');

            // Reader index: "latest completed inspection for this equipment".
            $table->index(['equipment_id', 'lifecycle_status', 'id'], 'errt_equip_lifecycle_idx');
            // Draft-reuse lookup index.
            $table->index(['equipment_id', 'order_product_id', 'lifecycle_status'], 'errt_equip_op_lifecycle_idx');
        });

        // ── Conservative reader-continuity backfill (existing rows only) ──
        // Damaged wins, then Rental Ready, then Maintenance Hold; anything
        // else stays a draft. completed_at seeded from updated_at. Counts are
        // logged so the deploy output confirms exactly how each bucket resolved.
        $total = DB::table('equipment_rental_ready_templates')->count();

        $damaged = DB::table('equipment_rental_ready_templates')
            ->where(function ($q) {
                $q->where('status', 'Damaged')->orWhere('damaged_items', '>', 0);
            })
            ->update(['lifecycle_status' => 'completed', 'result' => 'damaged']);

        $rentalReady = DB::table('equipment_rental_ready_templates')
            ->where('status', 'Rental Ready')
            ->whereNull('result')
            ->update(['lifecycle_status' => 'completed', 'result' => 'rental_ready']);

        $maintenance = DB::table('equipment_rental_ready_templates')
            ->whereNull('result')
            ->where('items_requiring_maintenance', '>', 0)
            ->update(['lifecycle_status' => 'completed', 'result' => 'maintenance_hold']);

        // Seed completed_at for the rows we just marked completed.
        DB::statement("UPDATE equipment_rental_ready_templates SET completed_at = COALESCE(updated_at, created_at) WHERE lifecycle_status = 'completed' AND completed_at IS NULL");

        $draft = DB::table('equipment_rental_ready_templates')->where('lifecycle_status', 'draft')->count();

        Log::info('Rental Ready Phase 2A backfill', [
            'total_rows' => $total,
            'completed_damaged' => $damaged,
            'completed_rental_ready' => $rentalReady,
            'completed_maintenance_hold' => $maintenance,
            'left_as_draft' => $draft,
        ]);
    }

    public function down(): void
    {
        Schema::table('equipment_rental_ready_templates', function (Blueprint $table) {
            $table->dropIndex('errt_equip_lifecycle_idx');
            $table->dropIndex('errt_equip_op_lifecycle_idx');
            $table->dropUnique('errt_completion_idem_key_unique');
            $table->dropColumn([
                'lifecycle_status', 'result', 'completed_at', 'voided_at', 'voided_by',
                'void_reason', 'superseded_by_template_id', 'completion_idempotency_key',
            ]);
        });
    }
};
