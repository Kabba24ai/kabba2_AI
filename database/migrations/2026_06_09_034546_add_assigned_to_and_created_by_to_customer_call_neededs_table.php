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
        Schema::table('customer_call_neededs', function (Blueprint $table) {
              $table->foreignId('auth_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_call_neededs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('auth_by');
        });
    }
};
