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
        Schema::table('customer_accounts', function (Blueprint $table) {
            $table->string('auth_code')->nullable()->after('payment_number_id');
            $table->string('customer_profile_id')->nullable()->after('auth_code');
            $table->string('payment_profile_id')->nullable()->after('customer_profile_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_accounts', function (Blueprint $table) {
            $table->dropColumn(['auth_code', 'customer_profile_id', 'payment_profile_id']);
        });
    }
};
