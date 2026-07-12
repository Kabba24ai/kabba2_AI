<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dashboard V2 Phase 1B refinement — canonical alert lifecycle log.
 *
 * Records, observationally, when a Fuel or Damage alert leaves the active
 * queue (reaches a terminal status). This is the reliable source for the
 * "Completed Today" donut metric; it does NOT gate or alter any workflow.
 *
 * Additive and reversible. down() drops the table, which discards lifecycle
 * history — "Completed Today" is only accurate from this table's data forward
 * and is never backfilled from unreliable timestamps.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_status_transitions', function (Blueprint $table) {
            $table->id();

            $table->string('alert_type', 16);           // fuel | damage
            $table->string('source_type', 32);          // order_product | customer_account
            $table->unsignedBigInteger('source_id');
            $table->unsignedBigInteger('order_id')->nullable();

            $table->string('previous_status', 32)->nullable();
            $table->string('new_status', 32);           // resolved | completed | uncollectible

            // Deduplicates a single transition OPERATION (one queue-exit within
            // one outstanding cycle), NOT the terminal status forever. Built as
            // {alert_type}:{source_type}:{source_id}:c{cycleSeq}, where cycleSeq
            // is the source's completed-cycle count + 1 — so a reopened alert
            // that later transitions again gets a distinct key and is recorded
            // as a new, legitimate lifecycle event. See AlertLifecycleService.
            $table->string('idempotency_key', 191)->unique('ast_idempotency_key_unique');

            $table->timestamp('transitioned_at')->useCurrent();
            $table->unsignedBigInteger('transitioned_by')->nullable();
            $table->unsignedBigInteger('service_ticket_id')->nullable();

            $table->timestamps();

            // Non-unique reporting/lookup indexes.
            $table->index(['alert_type', 'new_status', 'transitioned_at'], 'ast_completed_today_idx');
            $table->index(['source_type', 'source_id'], 'ast_source_idx');
            $table->index('order_id', 'ast_order_idx');
        });
    }

    public function down(): void
    {
        // WARNING: dropping this table discards all recorded lifecycle history.
        // Rolling back the code does not require rolling back this migration;
        // the table is additive and harmless to leave in place.
        Schema::dropIfExists('alert_status_transitions');
    }
};
