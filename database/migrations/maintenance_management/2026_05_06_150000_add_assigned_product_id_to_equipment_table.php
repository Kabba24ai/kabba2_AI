<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->unsignedBigInteger('assigned_product_id')->nullable()->after('product_category_id');
            $table->foreign('assigned_product_id')->references('id')->on('products')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropForeign(['assigned_product_id']);
            $table->dropColumn('assigned_product_id');
        });
    }
};
