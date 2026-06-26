<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            // Dispatch-only date overrides — never affect the customer's rental schedule
            $table->date('dispatch_delivery_date')->nullable()->after('delivery_date');
            $table->date('dispatch_return_date')->nullable()->after('pickup_date');
        });
    }

    public function down(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->dropColumn(['dispatch_delivery_date', 'dispatch_return_date']);
        });
    }
};
