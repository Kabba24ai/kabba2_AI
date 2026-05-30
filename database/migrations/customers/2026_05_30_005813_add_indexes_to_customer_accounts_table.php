<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
        public function up(): void
    {
        Schema::table('customer_accounts', function (Blueprint $table) {

            // Used in:
            // where('type', 'payment')
            $table->index('type');

            // Used in:
            // whereBetween('date', ...)
            $table->index('date');

            // Used in:
            // where('payment_type', ...)
            $table->index('payment_type');

            // Best combined index for your report
            $table->index(['type', 'date'], 'ca_type_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('customer_accounts', function (Blueprint $table) {

            $table->dropIndex(['type']);
            $table->dropIndex(['date']);
            $table->dropIndex(['payment_type']);

            $table->dropIndex('ca_type_date_idx');
        });
    }
};
