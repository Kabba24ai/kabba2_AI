<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->unsignedSmallInteger('delivery_priority')->nullable()->after('delivery_by');
            $table->unsignedSmallInteger('pickup_priority')->nullable()->after('pickup_by');
        });
    }

    public function down(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->dropColumn(['delivery_priority', 'pickup_priority']);
        });
    }
};
