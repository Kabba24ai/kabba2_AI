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
             // Add missing brand fields
            if (!Schema::hasColumn('parts', 'primary_brand_id')) {
                $table->unsignedBigInteger('primary_brand_id')->nullable()->after('primary_part_supplier_id');
            }

            if (!Schema::hasColumn('parts', 'alt_1_brand_id')) {
                $table->unsignedBigInteger('alt_1_brand_id')->nullable()->after('alt_1_part_supplier_id');
            }

            if (!Schema::hasColumn('parts', 'alt_2_brand_id')) {
                $table->unsignedBigInteger('alt_2_brand_id')->nullable()->after('alt_2_part_supplier_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
   public function down(): void
    {
        Schema::table('parts', function (Blueprint $table) {

            if (Schema::hasColumn('parts', 'primary_brand_id')) {
                $table->dropColumn('primary_brand_id');
            }

            if (Schema::hasColumn('parts', 'alt_1_brand_id')) {
                $table->dropColumn('alt_1_brand_id');
            }

            if (Schema::hasColumn('parts', 'alt_2_brand_id')) {
                $table->dropColumn('alt_2_brand_id');
            }

        });
    }
};
