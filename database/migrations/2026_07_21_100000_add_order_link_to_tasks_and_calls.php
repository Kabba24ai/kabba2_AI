<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Order-linked tasks. daily_tasks.related_order_id has existed since the
 * table was created but was never constrained or populated; this promotes
 * it to a real FK. Phone call reminders gain the same optional linkage.
 * Orders soft-delete, so nullOnDelete only fires on a hard delete — task
 * history is never removed when an order goes away.
 */
return new class extends Migration
{
    public function up(): void
    {
        // The column predates the FK — null out any orphaned values first
        // so the constraint can be created.
        DB::table('daily_tasks')
            ->whereNotNull('related_order_id')
            ->whereNotIn('related_order_id', DB::table('orders')->select('id'))
            ->update(['related_order_id' => null]);

        Schema::table('daily_tasks', function (Blueprint $table) {
            $table->foreign('related_order_id')->references('id')->on('orders')->nullOnDelete();
        });

        Schema::table('customer_call_neededs', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->after('supplier_id')
                ->constrained('orders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('daily_tasks', function (Blueprint $table) {
            $table->dropForeign(['related_order_id']);
        });

        Schema::table('customer_call_neededs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_id');
        });
    }
};
