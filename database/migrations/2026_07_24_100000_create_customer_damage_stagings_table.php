<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer Damage Staging — the review bridge between the Customer
 * Checklist (field observation) and disposition (No Action / Charge
 * Customer / Create Service Ticket).
 *
 * Deliberately a NEW table: the existing "damage alert" is not a table at
 * all (it is a derived view over equipment_soft_assigns + equipment
 * status + customer_accounts rows), and BillingCharge is a billing
 * artifact — extending either would create confused ownership. Canonical
 * entities are referenced by FK; only the original observation text is
 * snapshotted (it must stay historically stable as evidence).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_damage_stagings', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique();

            // 'checklist' | 'manual'. Checklist rows carry the idempotent
            // source key mirroring the mobile damage BillingCharge key
            // (mobile_checklist:{order_product_id}:damage:{cycleKey}) so
            // repeated API submissions/retries can never duplicate a row.
            $table->string('source_type', 20);
            $table->string('source_key')->nullable()->unique();

            // Customer Damage is order-based (approved business rule).
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('order_product_id')->nullable()->constrained('order_products')->nullOnDelete();
            $table->foreignId('equipment_id')->nullable()->constrained('equipment')->nullOnDelete();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();

            // Snapshot: the field observation as recorded (evidence).
            $table->text('observation');

            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reported_at');

            // new | in_review | disposed
            $table->string('status', 20)->default('new');

            // no_action | charge_customer | service_ticket — the primary
            // outcome. Linked outcomes are not mutually exclusive: a record
            // may hold BOTH a service ticket and a billing charge.
            $table->string('disposition', 30)->nullable();
            $table->text('disposition_note')->nullable();
            $table->foreignId('disposed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('disposed_at')->nullable();

            $table->foreignId('service_ticket_id')->nullable()->constrained('service_tickets')->nullOnDelete();
            $table->foreignId('billing_charge_id')->nullable()->constrained('billing_charges')->nullOnDelete();

            $table->timestamps();

            $table->index('status');
            $table->index(['order_id', 'order_product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_damage_stagings');
    }
};
