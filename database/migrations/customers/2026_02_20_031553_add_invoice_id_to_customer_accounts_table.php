<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_accounts', function (Blueprint $table) {

            $table->foreignId('invoice_id')
                ->nullable()
                ->constrained('invoices')
                ->cascadeOnDelete();

            $table->foreignId('invoice_item_id')
                ->nullable()
                ->constrained('invoice_items')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customer_accounts', function (Blueprint $table) {

            // Drop foreign keys first
            $table->dropForeign(['invoice_id']);
            $table->dropForeign(['invoice_item_id']);

            // Then drop columns
            $table->dropColumn(['invoice_id', 'invoice_item_id']);
        });
    }
};
