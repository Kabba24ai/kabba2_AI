<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_charges', function (Blueprint $table) {
            $table->id();

            // Unique identifier with prefix BLC-
            $table->string('unique_id')->unique();

            // Charge classification
            $table->string('billing_charge_type');   // BillingChargeType enum value
            $table->string('status')->default('pending'); // BillingChargeStatus enum value

            // ── Order / Customer references ────────────────────────────────
            // Stored as plain unsigned integers during the foundation phase.
            // Hard FK constraints (->constrained()) are added in Phase 2
            // integration migration after production wiring is complete.
            $table->unsignedBigInteger('parent_order_id')->index();
            $table->unsignedBigInteger('child_order_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id')->index();
            $table->unsignedBigInteger('order_product_id')->nullable()->index();

            // ── Financial ─────────────────────────────────────────────────
            $table->decimal('amount', 12, 2);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->string('tax_type')->default('free'); // add | free | reverse

            // ── Responsibility ────────────────────────────────────────────
            $table->unsignedBigInteger('responsible_person_id')->nullable()->index();
            $table->string('responsible_person_name')->nullable();
            $table->text('notes')->nullable();

            // ── Legacy bridge ─────────────────────────────────────────────
            // customer_account_id: set when BillingEngine creates a legacy
            // CustomerAccount row during bridge mode (Phases 2–3).
            $table->unsignedBigInteger('customer_account_id')->nullable()->index();
            $table->unsignedBigInteger('created_by_id')->nullable()->index();

            // ── Source tracking ───────────────────────────────────────────
            // Captures who/what created this charge so the audit log is
            // self-contained and mobile-originated charges can be identified.
            $table->string('source_module')->nullable()->index();   // BillingSourceModule enum value
            $table->string('source_event')->nullable()->index();    // BillingSourceEvent enum value
            $table->string('source_reference_type')->nullable();    // e.g. 'OrderProduct', 'ServiceTicket'
            $table->unsignedBigInteger('source_reference_id')->nullable();
            $table->json('metadata')->nullable();                   // arbitrary JSON context from the source

            // ── Idempotency ───────────────────────────────────────────────
            // Caller-supplied key. BillingEngine returns an existing charge if
            // this key is already present rather than creating a duplicate.
            // Critical for mobile offline sync retry safety.
            $table->string('idempotency_key', 128)->nullable()->unique();

            $table->index(['source_reference_type', 'source_reference_id']);
            $table->index(['billing_charge_type', 'status']);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_charges');
    }
};
