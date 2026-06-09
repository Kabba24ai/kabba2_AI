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
         Schema::table('customer_notes', function (Blueprint $table) {
            $table->foreignId('customer_call_needed_id')
                ->nullable()
                ->after('customer_id')
                ->constrained('customer_call_neededs')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
          Schema::table('customer_notes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_call_needed_id');
        });
    }
};
