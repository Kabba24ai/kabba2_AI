<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_templates', function (Blueprint $table) {
            // Add preset_id column
            $table->foreignId('preset_id')->nullable()->after('description')->constrained('interval_presets')->onDelete('set null');
            
            // Drop old category_id if it exists
            if (Schema::hasColumn('service_templates', 'category_id')) {
                $table->dropForeign(['category_id']);
                $table->dropColumn('category_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('service_templates', function (Blueprint $table) {
            $table->dropForeign(['preset_id']);
            $table->dropColumn('preset_id');
            
            // Restore category_id
            $table->foreignId('category_id')->nullable()->after('description')->constrained('service_categories')->onDelete('cascade');
        });
    }
};
