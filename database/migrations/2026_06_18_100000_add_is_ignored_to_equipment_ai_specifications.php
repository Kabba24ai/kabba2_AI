<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_ai_specifications', function (Blueprint $table) {
            $table->boolean('is_ignored')
                  ->default(false)
                  ->after('is_key_comparison')
                  ->comment('Spec is intentionally excluded from AI payloads (e.g. Warranty, Radio)');
        });
    }

    public function down(): void
    {
        Schema::table('equipment_ai_specifications', function (Blueprint $table) {
            $table->dropColumn('is_ignored');
        });
    }
};
