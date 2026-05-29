<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_ai_specifications', function (Blueprint $table) {
            $table->boolean('is_key_comparison')->default(false)->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('equipment_ai_specifications', function (Blueprint $table) {
            $table->dropColumn('is_key_comparison');
        });
    }
};
