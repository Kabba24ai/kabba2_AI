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
        Schema::table('equipment', function (Blueprint $table) {
            $table->unsignedBigInteger('equipment_service_id')->nullable()->after('checklist_master_id');
            $table->boolean('bring_service_flag')->default(false)->after('equipment_service_id');
            $table->decimal('bring_service_hour', 10, 2)->nullable()->after('bring_service_flag');
            
            // Add foreign key constraint
            $table->foreign('equipment_service_id')
                  ->references('id')
                  ->on('service_templates')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropForeign(['equipment_service_id']);
            $table->dropColumn(['equipment_service_id', 'bring_service_flag', 'bring_service_hour']);
        });
    }
};
