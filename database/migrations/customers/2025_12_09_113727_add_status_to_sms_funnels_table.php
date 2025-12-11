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
        Schema::table('sms_funnels', function (Blueprint $table) {
             // Broadcast status: pending, created, sent
            $table->enum('status', ['pending', 'created', 'sent'])->default('created');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sms_funnels', function (Blueprint $table) {
            //
        });
    }
};
