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
            $table->enum('delivery_status', ['Pending', 'Completed', 'Reschedule', 'Close as Completed'])->default('Pending')->change();
            $table->enum('pickup_status', ['Pending', 'Completed', 'Reschedule', 'Close as Completed'])->default('Pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_products', function (Blueprint $table) {

        });
    }
};
