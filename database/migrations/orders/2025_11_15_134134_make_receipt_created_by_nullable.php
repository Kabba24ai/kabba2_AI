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
        Schema::table('receipts', function (Blueprint $table) {
            // Drop existing FK first
            $table->dropForeign(['receipt_created_by']);

            // Make column nullable
            $table->foreignId('receipt_created_by')
                ->nullable()
                ->change();

            // Re-add foreign key with nullOnDelete
            $table->foreign('receipt_created_by')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            // Reverse: drop FK → not nullable → restore FK
            $table->dropForeign(['receipt_created_by']);

            $table->foreignId('receipt_created_by')
                ->nullable(false)
                ->change();

            $table->foreign('receipt_created_by')
                ->references('id')->on('users')
                ->cascadeOnDelete();
        });
    }
};
