<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Queue Key Confirmation — APPEND-ONLY history of "key is with the machine"
 * sign-offs and their reversals, recorded by the Mark as Staged workflow.
 * Deliberately a byte-for-byte sibling of queue_line_fuel_verifications:
 * rows are never mutated or deleted, current state is always DERIVED
 * (QueueLineStagingService), and every confirmation is bound to the
 * equipment_soft_assigns row live at sign-off time — switching equipment
 * starts a new episode and an old confirmation can never reactivate.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_line_key_confirmations', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('order_product_id')->index();
            $table->unsignedBigInteger('order_id')->index();       // sidecar-pattern denormalization
            $table->unsignedBigInteger('equipment_id')->index();   // the exact unit signed off

            // The assignment EPISODE this sign-off belongs to
            $table->unsignedBigInteger('equipment_soft_assign_id')->index();

            $table->enum('action', ['confirmed', 'reversed']);

            // Reversals point at the confirmation they void (append-only)
            $table->unsignedBigInteger('reversed_confirmation_id')->nullable()->index();

            $table->unsignedBigInteger('performed_by');  // employee physically confirming (self-selected)
            $table->unsignedBigInteger('created_by');    // authenticated application user

            $table->string('source');                    // queue_line_web | queue_line_wall | queue_line_mobile
            $table->string('reason')->nullable();        // required on 'reversed'

            // Retry safety, mirroring the fuel ledger (unused by the web
            // modal today; ready for a future mobile staging endpoint)
            $table->string('idempotency_token')->nullable()->unique();

            $table->timestamps();

            $table->foreign('order_product_id')->references('id')->on('order_products')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_line_key_confirmations');
    }
};
