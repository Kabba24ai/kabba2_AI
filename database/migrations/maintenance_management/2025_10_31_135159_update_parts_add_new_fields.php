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
        Schema::table('parts', function (Blueprint $table) {

            // Add category FK
            $table->foreignId('part_category_id')->nullable()->after('dni')->constrained('part_categories')->nullOnDelete();

        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            

            if (Schema::hasColumn('parts', 'part_category_id')) {
                $table->dropColumn('part_category_id');
            }
        });
    }
};
