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
            $table->after('quantity', function (Blueprint $table) {
                $table->enum('hour_tracking', ['Yes', 'No'])->default('No');
                $table->decimal('hour_rate', 10, 2)->default(0.00);
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->dropColumn(['hour_tracking', 'hour_rate']);
        });
    }
};
