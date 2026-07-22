<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ST-2a — service settlement now hands off through the canonical Billing
 * Engine, producing a real BillingCharge instead of a raw legacy
 * order_extra_charges row. This links the settlement to that charge. The
 * pre-existing order_extra_charge_id column stays (nullable) so the legacy
 * 'service' extra-charge rows created before this change keep resolving —
 * new settlements populate billing_charge_id instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_ticket_settlements', function (Blueprint $table) {
            $table->foreignId('billing_charge_id')->nullable()->after('order_extra_charge_id')
                ->constrained('billing_charges')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('service_ticket_settlements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('billing_charge_id');
        });
    }
};
