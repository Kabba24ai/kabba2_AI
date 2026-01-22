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
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('payment_number_id')->nullable()->after('is_email_send');
            $table->string('auth_code')->nullable()->after('payment_number_id');
            $table->string('customer_profile_id')->nullable()->after('auth_code');
            $table->string('payment_profile_id')->nullable()->after('customer_profile_id');

            $table->enum('payment_method', ['cash', 'card', 'online'])->nullable()->after('invoice_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'payment_number_id',
                'auth_code',
                'customer_profile_id',
                'payment_profile_id',
                'payment_method',
            ]);
        });
    }
};
