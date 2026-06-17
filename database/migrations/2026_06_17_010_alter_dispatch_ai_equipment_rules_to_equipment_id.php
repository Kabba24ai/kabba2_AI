<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatch_ai_equipment_rules', function (Blueprint $table) {
            $table->dropForeign(['product_category_id']);
            $table->dropUnique(['product_category_id']);
            $table->dropColumn('product_category_id');
        });

        Schema::table('dispatch_ai_equipment_rules', function (Blueprint $table) {
            $table->unsignedBigInteger('equipment_id')->unique()->after('id');
            $table->foreign('equipment_id')->references('id')->on('equipment')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_ai_equipment_rules', function (Blueprint $table) {
            $table->dropForeign(['equipment_id']);
            $table->dropUnique(['equipment_id']);
            $table->dropColumn('equipment_id');
        });

        Schema::table('dispatch_ai_equipment_rules', function (Blueprint $table) {
            $table->unsignedBigInteger('product_category_id')->unique()->after('id');
            $table->foreign('product_category_id')->references('id')->on('product_categories')->onDelete('cascade');
        });
    }
};
