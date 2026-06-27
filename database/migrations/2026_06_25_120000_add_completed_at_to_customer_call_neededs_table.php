<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_call_neededs', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable()->after('due_date');
            $table->unsignedBigInteger('completed_by')->nullable()->after('completed_at');
            $table->foreign('completed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customer_call_neededs', function (Blueprint $table) {
            $table->dropForeign(['completed_by']);
            $table->dropColumn(['completed_at', 'completed_by']);
        });
    }
};
