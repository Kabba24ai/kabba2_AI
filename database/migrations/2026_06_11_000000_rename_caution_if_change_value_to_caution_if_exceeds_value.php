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
        Schema::table('equipment_category_comparison_keys', function (Blueprint $table) {
            $table->renameColumn('caution_if_change_value', 'caution_if_exceeds_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipment_category_comparison_keys', function (Blueprint $table) {
            $table->renameColumn('caution_if_exceeds_value', 'caution_if_change_value');
        });
    }
};
