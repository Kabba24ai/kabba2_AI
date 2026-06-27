<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_product_funnel_logs', function (Blueprint $table) {
            $table->string('notes', 1000)->nullable()->after('message');
            $table->string('twilio_sid', 64)->nullable()->after('sent_at');
            $table->string('sms_timezone', 64)->nullable()->after('twilio_sid');
        });

        // Widen status column to include Stopped/Skipped
        DB::statement("ALTER TABLE order_product_funnel_logs MODIFY COLUMN status ENUM('Sent','Failed','Stopped','Skipped') NOT NULL DEFAULT 'Sent'");
    }

    public function down(): void
    {
        Schema::table('order_product_funnel_logs', function (Blueprint $table) {
            $table->dropColumn(['notes', 'twilio_sid', 'sms_timezone']);
        });

        DB::statement("ALTER TABLE order_product_funnel_logs MODIFY COLUMN status ENUM('Sent','Failed') NOT NULL DEFAULT 'Sent'");
    }
};
