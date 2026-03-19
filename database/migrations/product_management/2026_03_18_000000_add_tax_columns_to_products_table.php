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
        Schema::table('products', function (Blueprint $table) {
            $table->after('prepaid_fuel_rate_setting', function (Blueprint $table) {
                $table->boolean('is_tax_free_item')->default(false);
                $table->boolean('apply_special_tax')->default(false);
                $table->boolean('apply_added_fees')->default(false);
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_tax_free_item', 'apply_special_tax', 'apply_added_fees']);
        });
    }
};
