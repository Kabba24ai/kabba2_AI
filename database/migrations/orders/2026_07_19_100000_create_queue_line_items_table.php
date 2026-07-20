<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Queue Line state sidecar — one row per order_product, created lazily the
 * first time a human acts on the item (stage / RUSH / suppress). Eligibility
 * itself is COMPUTED live from order_products (QueueLineEligibility); this
 * table stores only human decisions and the completion latch. Absence of a
 * row = default state (eligible, Not Staged).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_line_items', function (Blueprint $table) {
            $table->id();

            // THE item key — one physical equipment item per order product
            $table->unsignedBigInteger('order_product_id')->unique();
            $table->unsignedBigInteger('order_id')->index(); // denormalized for grouping

            // On Queue Line (physically staged) — null = Not Staged
            $table->timestamp('staged_at')->nullable();
            $table->unsignedBigInteger('staged_by')->nullable();

            // RUSH — item-specific manual priority
            $table->timestamp('rush_at')->nullable();
            $table->unsignedBigInteger('rush_by')->nullable();

            // Remove Today — self-expiring: excluded only while this equals
            // the current operational date (no scheduler needed)
            $table->date('suppressed_on')->nullable();
            $table->unsignedBigInteger('suppressed_on_by')->nullable();

            // Remove Forever — survives reschedules; dies naturally with the
            // order_product row (a recreated item has a new id)
            $table->boolean('suppressed_forever')->default(false);
            $table->timestamp('suppressed_forever_at')->nullable();
            $table->unsignedBigInteger('suppressed_forever_by')->nullable();

            // Completion latch — schema hook for Phase 3 (dispatch-start /
            // customer-checklist listeners). One-way null-latch = idempotency.
            $table->timestamp('completed_at')->nullable();
            $table->string('completed_via')->nullable();
            $table->unsignedBigInteger('completed_equipment_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('order_product_id')->references('id')->on('order_products')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_line_items');
    }
};
