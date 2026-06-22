<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_logs', function (Blueprint $table) {
            $table->foreignId('pod_payment_link_id')
                  ->nullable()
                  ->after('order_id')
                  ->constrained('pod_payment_links')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('sms_logs', function (Blueprint $table) {
            $table->dropForeign(['pod_payment_link_id']);
            $table->dropColumn('pod_payment_link_id');
        });
    }
};
