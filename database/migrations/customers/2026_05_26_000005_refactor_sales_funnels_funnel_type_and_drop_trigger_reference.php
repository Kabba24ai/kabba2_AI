<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Refactor sales_funnels table:
 *
 * 1. Rename trigger_type enum value  new_order  →  retail_order
 *    (Retail Order is for retail/product sales with no rental schedule)
 *
 * 2. Drop trigger_reference column entirely.
 *    Timing is now the sole responsibility of sales_funnel_steps (Events).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Step 1: update any existing rows before changing the enum ──────────
        DB::statement("
            UPDATE sales_funnels
            SET trigger_type = 'retail_order'
            WHERE trigger_type = 'new_order'
        ");

        // ── Step 2: modify trigger_type enum (new_order removed, retail_order added) ──
        DB::statement("
            ALTER TABLE sales_funnels
            MODIFY COLUMN trigger_type
            ENUM('retail_order', 'rental_schedule', 'lead_added')
            NOT NULL DEFAULT 'retail_order'
        ");

        // ── Step 3: drop the trigger_reference column ──────────────────────────
        if (Schema::hasColumn('sales_funnels', 'trigger_reference')) {
            Schema::table('sales_funnels', function (Blueprint $table) {
                $table->dropColumn('trigger_reference');
            });
        }
    }

    public function down(): void
    {
        // ── Restore trigger_reference ──────────────────────────────────────────
        Schema::table('sales_funnels', function (Blueprint $table) {
            $table->enum('trigger_reference', [
                'order_created_datetime',
                'order_paid_datetime',
                'delivery_datetime',
                'return_datetime',
                'lead_added_datetime',
            ])->default('order_created_datetime')->after('trigger_type');
        });

        // ── Restore trigger_type enum with new_order ───────────────────────────
        DB::statement("
            UPDATE sales_funnels
            SET trigger_type = 'new_order'
            WHERE trigger_type = 'retail_order'
        ");

        DB::statement("
            ALTER TABLE sales_funnels
            MODIFY COLUMN trigger_type
            ENUM('new_order', 'rental_schedule', 'lead_added')
            NOT NULL DEFAULT 'new_order'
        ");
    }
};
