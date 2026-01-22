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
        Schema::table('equipment_rental_ready_templates', function (Blueprint $table) {
            $table->softDeletes()->after('updated_by');
        });

        Schema::table('equipment_rental_ready_checklist_questions', function (Blueprint $table) {
            $table->softDeletes()->after('general_notes');
        });

        Schema::table('equipment_rental_ready_checklist_question_logs', function (Blueprint $table) {
            $table->softDeletes()->after('action_user_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipment_rental_ready_templates', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('equipment_rental_ready_checklist_questions', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('equipment_rental_ready_checklist_question_logs', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
