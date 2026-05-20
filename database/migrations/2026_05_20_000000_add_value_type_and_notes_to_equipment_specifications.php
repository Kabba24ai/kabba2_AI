<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_specifications', function (Blueprint $table) {
            $table->string('value_type', 50)->nullable()->after('unit')->comment('decimal, integer, boolean, enum');
            $table->text('notes')->nullable()->after('confidence_score')->comment('Source notes and uncertainty explanations');
        });
    }

    public function down(): void
    {
        Schema::table('equipment_specifications', function (Blueprint $table) {
            $table->dropColumn(['value_type', 'notes']);
        });
    }
};
