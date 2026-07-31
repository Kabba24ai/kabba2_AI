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
            $table->string('delivery_clean_option')->nullable()->after('rental_prepaid_cleaning');
            $table->unsignedBigInteger('delivery_clean_id')->nullable()->after('delivery_clean_option');
            $table->string('return_clean_option')->nullable()->after('delivery_clean_id');
            $table->unsignedBigInteger('return_clean_id')->nullable()->after('return_clean_option');
            $table->decimal('total_clean_charge', 10, 2)->default(0)->after('return_clean_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_clean_option',
                'delivery_clean_id',
                'return_clean_option',
                'return_clean_id',
                'total_clean_charge',
            ]);
        });
    }
};
