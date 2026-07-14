<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Phase 2 refund workflow: distinguishes HOW a refund was calculated
// (proportional / card-fee-retained / tax-only) from WHY it was issued
// (processed_reason_code, unchanged) — both nullable and additive, no
// existing refund row is touched or reinterpreted.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_payments', function (Blueprint $table) {
            $table->string('refund_calculation_type')->nullable()->after('refund_note');
            $table->decimal('cc_fee_retained', 10, 2)->nullable()->after('refund_calculation_type');
        });
    }

    public function down(): void
    {
        Schema::table('order_payments', function (Blueprint $table) {
            $table->dropColumn(['refund_calculation_type', 'cc_fee_retained']);
        });
    }
};
