<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Queue Fuel Verification — APPEND-ONLY history of outbound "Fuel Full"
 * sign-offs and their reversals. Rows are never mutated or deleted; the
 * current state is always DERIVED (QueueFuelVerificationService).
 *
 * ASSIGNMENT-EPISODE SAFETY: every verification is bound to the
 * equipment_soft_assigns row (equipment_soft_assign_id) that was live at
 * sign-off time. The canonical Switch Equipment operation delete-then-
 * creates a NEW soft-assign row on every change, so switching ABC → XYZ →
 * back to ABC produces a NEW episode id for the second ABC — the original
 * ABC verification can never silently reactivate.
 *
 * This is distinct from (and never touches): prepaid fuel sold to the
 * customer (product options), return fuel readings
 * (fuel_initial/final_reading), and fuel charges (OrderProductFuelChargeLog).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_line_fuel_verifications', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('order_product_id')->index();
            $table->unsignedBigInteger('order_id')->index();       // sidecar-pattern denormalization
            $table->unsignedBigInteger('equipment_id')->index();   // the exact unit signed off

            // The assignment EPISODE this sign-off belongs to (see header)
            $table->unsignedBigInteger('equipment_soft_assign_id')->index();

            $table->enum('action', ['verified', 'reversed']);

            // Reversals point at the verification they void (append-only)
            $table->unsignedBigInteger('reversed_verification_id')->nullable()->index();

            $table->unsignedBigInteger('performed_by');  // employee physically confirming (self-selected)
            $table->unsignedBigInteger('created_by');    // authenticated application user

            $table->string('source');                    // queue_line_web | queue_line_wall | queue_line_mobile
            $table->string('reason')->nullable();        // required on 'reversed'

            // Mobile/scanner retry safety — a replayed token returns the
            // original row instead of appending a duplicate
            $table->string('idempotency_token')->nullable()->unique();

            $table->timestamps();

            $table->foreign('order_product_id')->references('id')->on('order_products')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_line_fuel_verifications');
    }
};
