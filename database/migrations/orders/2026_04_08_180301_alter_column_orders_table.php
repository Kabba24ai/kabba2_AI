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
            $table->unsignedBigInteger('auto_inject_by')->nullable()->after('auto_inject'); // New nullable column for auto inject by user ID

            // Optional: Add foreign key constraint if you want to link to users table
            $table->foreign('auto_inject_by')->references('id')->on('users')->nullOnDelete()->cascadeOnUpdate(); // Set to null if the user is deleted
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['auto_inject_by']); // Drop the foreign key constraint
            $table->dropColumn('auto_inject_by'); // Drop the auto_inject_by column
        });
    }
};
