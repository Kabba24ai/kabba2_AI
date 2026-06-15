<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_category_comparison_keys', function (Blueprint $table) {
            $table->dropColumn(['comparison_type', 'is_required']);
        });
    }

    public function down(): void
    {
        Schema::table('equipment_category_comparison_keys', function (Blueprint $table) {
            $table->enum('comparison_type', [
                'higher_is_better',
                'lower_is_better',
                'must_match',
                'range_acceptable',
                'informational_only',
            ])->default('informational_only')->after('importance_level');

            $table->boolean('is_required')->default(false)->after('sort_order');
        });
    }
};
