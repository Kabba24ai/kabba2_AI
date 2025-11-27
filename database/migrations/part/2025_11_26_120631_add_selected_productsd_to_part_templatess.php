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
        Schema::table('parts_lists', function (Blueprint $table) {
             // 1. Drop the old string column if it exists
            if (Schema::hasColumn('parts_lists', 'category')) {
                $table->dropColumn('category');
            }

            // 2. Add the new foreign key column
            $table->unsignedBigInteger('category_id')->nullable()->after('name');

            // 3. Add foreign key constraint to product_categories (or part_categories)
            $table->foreign('category_id')
                ->references('id')
                ->on('product_categories')
                ->onDelete('set null');

            // 4. Add selected_products JSON column
            $table->json('selected_products')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parts_lists', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn(['category_id', 'selected_products']);
            $table->string('category')->nullable(); // revert old column if rolled back
        });
    }
};
