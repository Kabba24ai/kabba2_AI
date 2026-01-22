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
        // Change type column to ENUM
        DB::statement("ALTER TABLE order_media MODIFY COLUMN type ENUM('license','delivery','pickup') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback to string (original state)
        DB::statement("ALTER TABLE order_media MODIFY COLUMN type VARCHAR(255) NOT NULL");
    }
};
