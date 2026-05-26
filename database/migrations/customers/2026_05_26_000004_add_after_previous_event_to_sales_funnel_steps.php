<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Extends timing_reference_type enum to include 'after_previous_event'.
 * This allows a funnel step to be scheduled relative to when the
 * previous step was sent, rather than a fixed date/time reference.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE sales_funnel_steps
            MODIFY COLUMN timing_reference_type
            ENUM(
                'funnel_entry_time',
                'order_created_datetime',
                'order_paid_datetime',
                'rental_delivery_datetime',
                'rental_return_datetime',
                'lead_added_datetime',
                'after_previous_event'
            )
            NOT NULL DEFAULT 'funnel_entry_time'
        ");
    }

    public function down(): void
    {
        // Revert rows that used the new value before removing it
        DB::statement("
            UPDATE sales_funnel_steps
            SET timing_reference_type = 'funnel_entry_time'
            WHERE timing_reference_type = 'after_previous_event'
        ");

        DB::statement("
            ALTER TABLE sales_funnel_steps
            MODIFY COLUMN timing_reference_type
            ENUM(
                'funnel_entry_time',
                'order_created_datetime',
                'order_paid_datetime',
                'rental_delivery_datetime',
                'rental_return_datetime',
                'lead_added_datetime'
            )
            NOT NULL DEFAULT 'funnel_entry_time'
        ");
    }
};
