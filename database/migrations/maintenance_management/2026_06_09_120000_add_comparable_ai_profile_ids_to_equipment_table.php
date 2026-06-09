<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            if (! Schema::hasColumn('equipment', 'comparable_ai_profile_ids')) {
                $table->json('comparable_ai_profile_ids')->nullable()->after('similar_equipment_ids');
            }
        });
    }

    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            if (Schema::hasColumn('equipment', 'comparable_ai_profile_ids')) {
                $table->dropColumn('comparable_ai_profile_ids');
            }
        });
    }
};
