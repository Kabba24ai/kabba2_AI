<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lifecycle upgrade for order_product_funnel_logs:
 *
 * 1. Add order_id and customer_id for permanent history preservation.
 *    Even when an OrderProduct is deleted, these columns retain the audit trail.
 *
 * 2. Add lifecycle_reason (Order Deleted, Delivery Completed, etc.) and stopped_at timestamp.
 *
 * 3. Change order_product_id FK from CASCADE DELETE → SET NULL.
 *    Previously, hard-deleting an OrderProduct would cascade-delete all funnel logs,
 *    destroying communication history. SET NULL preserves the log while nulling the FK.
 *    (Soft-deletes never triggered CASCADE, so existing rows are unaffected.)
 *
 * 4. Backfill order_id and customer_id from existing rows via a JOIN.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_product_funnel_logs', function (Blueprint $table) {
            // Preserved history columns
            $table->unsignedBigInteger('order_id')->nullable()->after('id');
            $table->unsignedBigInteger('customer_id')->nullable()->after('order_id');

            // Lifecycle audit columns
            $table->string('lifecycle_reason', 100)->nullable()->after('sms_timezone');
            $table->dateTime('stopped_at')->nullable()->after('lifecycle_reason');
        });

        // Backfill order_id and customer_id from existing order_product rows
        DB::statement("
            UPDATE order_product_funnel_logs opfl
            INNER JOIN order_products op ON op.id = opfl.order_product_id
            SET opfl.order_id    = op.order_id,
                opfl.customer_id = (SELECT customer_id FROM orders WHERE id = op.order_id LIMIT 1)
            WHERE opfl.order_id IS NULL
        ");

        // Change order_product_id from NOT NULL → nullable (required for SET NULL FK)
        Schema::table('order_product_funnel_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('order_product_id')->nullable()->change();
        });

        // Drop the CASCADE FK and replace with SET NULL
        // Constraint name follows Laravel convention: {table}_{column}_foreign
        Schema::table('order_product_funnel_logs', function (Blueprint $table) {
            $table->dropForeign(['order_product_id']);
        });

        Schema::table('order_product_funnel_logs', function (Blueprint $table) {
            $table->foreign('order_product_id')
                ->references('id')
                ->on('order_products')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('order_product_funnel_logs', function (Blueprint $table) {
            $table->dropForeign(['order_product_id']);
        });

        // Restore NOT NULL (rows with null order_product_id must be removed first to avoid constraint violation)
        DB::statement("DELETE FROM order_product_funnel_logs WHERE order_product_id IS NULL");

        Schema::table('order_product_funnel_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('order_product_id')->nullable(false)->change();
        });

        Schema::table('order_product_funnel_logs', function (Blueprint $table) {
            $table->foreign('order_product_id')
                ->references('id')
                ->on('order_products')
                ->onDelete('cascade');
        });

        Schema::table('order_product_funnel_logs', function (Blueprint $table) {
            $table->dropColumn(['order_id', 'customer_id', 'lifecycle_reason', 'stopped_at']);
        });
    }
};
