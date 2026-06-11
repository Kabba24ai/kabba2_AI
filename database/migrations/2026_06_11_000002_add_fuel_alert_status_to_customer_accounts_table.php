<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_accounts', function (Blueprint $table) {
            $table->string('fuel_alert_status', 20)->nullable()->after('reason');
        });
    }

    public function down(): void
    {
        Schema::table('customer_accounts', function (Blueprint $table) {
            $table->dropColumn('fuel_alert_status');
        });
    }
};
