<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_call_neededs', function (Blueprint $table) {
            $table->timestamp('follow_up_at')->nullable()->after('is_urgent');
            $table->text('follow_up_note')->nullable()->after('follow_up_at');
            $table->unsignedBigInteger('rescheduled_by')->nullable()->after('follow_up_note');
            $table->timestamp('rescheduled_at')->nullable()->after('rescheduled_by');
        });
    }

    public function down(): void
    {
        Schema::table('customer_call_neededs', function (Blueprint $table) {
            $table->dropColumn(['follow_up_at', 'follow_up_note', 'rescheduled_by', 'rescheduled_at']);
        });
    }
};
