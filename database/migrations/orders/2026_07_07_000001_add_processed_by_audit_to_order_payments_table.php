<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive "Processed By" audit layer for refunds and voids. Multiple
 * employees share terminals, so alongside the existing logged-in user
 * tracking (created_by morphs / order_histories.user_id — unchanged) we
 * record the verified employee who physically processed the action, plus
 * a structured reason. All columns nullable so historic rows stay valid.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('processed_by_id')->nullable()->after('voided_at')->index();
            $table->string('processed_by_name')->nullable()->after('processed_by_id');
            $table->string('processed_reason_code', 50)->nullable()->after('processed_by_name');
            $table->string('processed_reason_label')->nullable()->after('processed_reason_code');
            $table->string('processed_reason_other')->nullable()->after('processed_reason_label');
        });
    }

    public function down(): void
    {
        Schema::table('order_payments', function (Blueprint $table) {
            $table->dropColumn([
                'processed_by_id', 'processed_by_name',
                'processed_reason_code', 'processed_reason_label', 'processed_reason_other',
            ]);
        });
    }
};
