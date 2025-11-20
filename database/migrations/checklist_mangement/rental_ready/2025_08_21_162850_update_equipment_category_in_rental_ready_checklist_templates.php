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
        Schema::table('rental_ready_checklist_templates', function (Blueprint $table) {
            // Drop old string column
            $table->dropColumn('equipment_category_id');
        });

        Schema::table('rental_ready_checklist_templates', function (Blueprint $table) {
            // Add proper foreignId with relationship
            $table->foreignId('equipment_category_id')
                  ->nullable()
                  ->constrained('product_categories')
                  ->nullOnDelete()
                  ->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rental_ready_checklist_templates', function (Blueprint $table) {
            $table->dropForeign(['equipment_category_id']);
            $table->dropColumn('equipment_category_id');

            // Restore old string column if rolled back
            $table->string('equipment_category_id')->nullable();
        });
    }
};
