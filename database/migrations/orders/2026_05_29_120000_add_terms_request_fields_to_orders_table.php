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
        Schema::table('orders', function (Blueprint $table) {
            $table->after('terms_status', function (Blueprint $table) {
                $table->dateTime('terms_first_sent_at')->nullable();
                $table->dateTime('terms_second_sent_at')->nullable();
                $table->dateTime('terms_third_sent_at')->nullable();
                $table->string('terms_first_message_id')->nullable();
                $table->string('terms_second_message_id')->nullable();
                $table->string('terms_third_message_id')->nullable();
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'terms_first_sent_at',
                'terms_second_sent_at',
                'terms_third_sent_at',
                'terms_first_message_id',
                'terms_second_message_id',
                'terms_third_message_id',
            ]);
        });
    }
};
