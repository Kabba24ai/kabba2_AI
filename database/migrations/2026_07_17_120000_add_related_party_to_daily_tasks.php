<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Operational tasks gain the same related-party linkage the Call Needed
 * records already have: customer OR supplier OR free-text "other".
 * related_customer_id already exists on daily_tasks; this adds the
 * missing two. Nullable + additive — existing rows untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_tasks', function (Blueprint $table) {
            $table->foreignId('related_supplier_id')->nullable()->after('related_customer_id')
                ->constrained('suppliers')->nullOnDelete();
            $table->string('related_other')->nullable()->after('related_supplier_id');
        });
    }

    public function down(): void
    {
        Schema::table('daily_tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('related_supplier_id');
            $table->dropColumn('related_other');
        });
    }
};
