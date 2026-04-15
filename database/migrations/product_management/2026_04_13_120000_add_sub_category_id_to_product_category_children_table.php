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
        Schema::table('product_category_children', function (Blueprint $table) {
            if (!Schema::hasColumn('product_category_children', 'sub_category_id')) {
                $table->unsignedBigInteger('sub_category_id')->nullable()->after('product_category_id');
                $table->foreign('sub_category_id')
                    ->references('id')
                    ->on('product_categories')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_category_children', function (Blueprint $table) {
            if (Schema::hasColumn('product_category_children', 'sub_category_id')) {
                $table->dropForeign(['sub_category_id']);
                $table->dropColumn('sub_category_id');
            }
        });
    }
};
