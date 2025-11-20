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
            // Rename columns
            $table->renameColumn('delivery_type', 'delivery_transport_mode');
            $table->renameColumn('pickup_type', 'pickup_transport_mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->renameColumn('delivery_transport_mode', 'delivery_type');
            $table->renameColumn('pickup_transport_mode', 'pickup_type');
        });
    }
};
