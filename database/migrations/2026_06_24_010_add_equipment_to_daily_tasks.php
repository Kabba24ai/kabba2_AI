<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_tasks', function (Blueprint $table) {
            $table->unsignedBigInteger('related_equipment_id')->nullable()->after('related_customer_id');
            $table->foreign('related_equipment_id')->references('id')->on('equipment')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('daily_tasks', function (Blueprint $table) {
            $table->dropForeign(['related_equipment_id']);
            $table->dropColumn('related_equipment_id');
        });
    }
};
