<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive-only change to the Order Extra Payments table so the Service
     * Module can hand settlement charges to the Financial Engine:
     * - 'service' joins the type enum (fuel/damage behavior untouched)
     * - source_type/source_id give drill-down from a charge back to the
     *   originating Service Ticket settlement and prevent duplicates.
     * No Billing Engine code is modified.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE order_extra_charges MODIFY type ENUM('fuel','damage','service') NOT NULL");

        Schema::table('order_extra_charges', function (Blueprint $table) {
            $table->string('source_type')->nullable()->after('notes');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::table('order_extra_charges', function (Blueprint $table) {
            $table->dropIndex(['source_type', 'source_id']);
            $table->dropColumn(['source_type', 'source_id']);
        });

        DB::statement("ALTER TABLE order_extra_charges MODIFY type ENUM('fuel','damage') NOT NULL");
    }
};
